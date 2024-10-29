<?php

namespace UNIFIED_SOFTWARE\BANKVAL;

const MAIN_SERVER = 'https://www.unifiedservices.co.uk';
const BACKUP_SERVER = 'https://www.unifiedsoftware.co.uk';

const BANKVAL_ENDPOINT = 'services/enhanced/bankvalnew';

define( __NAMESPACE__ . '\MAIN_BANKVAL_URL', sprintf( '%s/%s', MAIN_SERVER, BANKVAL_ENDPOINT ) );
define( __NAMESPACE__ . '\BACKUP_BANKVAL_URL', sprintf( '%s/%s', BACKUP_SERVER, BANKVAL_ENDPOINT ) );


function get_unified_software_credentials() {
    $option = get_option( 'unified_software_credentials', '' );
    if ( '' != $option && ! is_array( $option ) ) {
        return false;
    }

    foreach ( array( 'pin', 'uname' ) as $key ) {
        if ( ! array_key_exists( $key, $option ) || '' == $option[$key] ) {
            return false;
        }
    }

    return $option;
}

function does_table_exist( $name ) {
    global $wpdb;
    return $wpdb->get_var("SHOW TABLES LIKE '${name}'") == $name;
}

function is_bankval_table( $name ) {
    global $wpdb;
    $expectedCols = array(
        'id',
        'timestamp',
        'validation_id',
        'sortcode',
        'accountnumber',
        'status'
    );

    $existingCols = $wpdb->get_col("DESC ${name}", 0);
    if ( count( $expectedCols ) != count( $existingCols ) ) {
        return false;
    }

    foreach ( $expectedCols as $col ) {
        if ( ! in_array( $col, $existingCols ) ) {
            return false;
        }
    }

    return true;
}

function create_bankval_table( $name ) {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE ${name} (
        id BIGINT UNSIGNED AUTO_INCREMENT,
        timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        validation_id CHAR(36),
        sortcode CHAR(6) NOT NULL,
        accountnumber VARCHAR(12) NOT NULL,
        status VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
    ) ${charset_collate};";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

function insert_into_bankval_table( $validation_id, $sortcode, $accno, $status ) {
    global $wpdb;
    $result = $wpdb->insert(
        get_option('unified_software_bankval')['mysql_table'],
        array(
            'status'        => $status,
            'accountnumber' => $accno,
            'sortcode'      => $sortcode,
            'validation_id' => $validation_id
        )
    );

    return $result == 1;
}

function process_bankval_response($response) {
    if (is_wp_error($response)) {
        return false;
    }
    
    if (wp_remote_retrieve_response_code($response) != 200) {
        return false;
    }

    $respBody = wp_remote_retrieve_body($response);
    $json = json_decode($respBody, true);

    if ( array_key_exists( 'BankValUK', $json ) ) {
        if ( is_array( $json['BankValUK'] ) ) {
            return array(
                'status'        => 1,
                'result'        => $json['BankValUK']['result'],
                'validationID'  => $json['validationID']
            );
        } else {
            return array(
                'status'        => 0,
                'result'        => $json['BankValUK'],
                'validationID'  => $json['validationID']
            );
        }

    }

    return array(
        'status'    => 0,
        'result'    => $json['Error']
    );
}

function bankval( $sortcode, $account, $uname, $pin ) {
    $body = array(
        'credentials' => array(
            'pin' => $pin,
            'uname' => $uname
        ),
        'account' => array(
            'account' => $account,
            'sortcode' => $sortcode
        )
    );

    $args = array(
        'data_format' => 'body',
        'body' => json_encode($body)
    );

    $response = wp_remote_post(constant( __NAMESPACE__ . '\MAIN_BANKVAL_URL' ), $args);
    $result = process_bankval_response($response);

    if ( $result ) {
        return $result;
    }

    $response = wp_remote_post(constant( __NAMESPACE__ . '\BACKUP_BANKVAL_URL' ), $args);
    $result = process_bankval_response($response);

    if ( ! $result ) {
        return false;
    }

    return $result;
}

function validate_bank_account( $sortcode, $account, $pin = null, $uname = null ) {

    if ( empty( $pin ) || empty( $uname ) ) {
        $crypto = Crypto::getInstance();
        $encryptionKey = $crypto->get_encryption_key();
        if ( ! $encryptionKey ) {
            throw new Exception( 'Could not find Unified Software encryption key' );
        }
    
        $creds = get_unified_software_credentials();
        
        if ( ! $creds ) {
            throw new Exception( 'Could not find Unified Software credentials' );
        }
    
        $creds = $crypto->decrypt_arr( $creds, $encryptionKey );
        $pin = $creds['pin']; $uname = $creds['uname'];
    }

    $response = bankval( $sortcode, $account, $uname, $pin );

    if ( ! $response ) {
        throw new Exception( 'Could not reach Unified Software API' );
    }

    unset( $response['status'] );
    return $response;
}

?>