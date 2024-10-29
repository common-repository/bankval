<?php

/**
 * Plugin Name: BankVal - Bank Sort Code & Account Number Validation
 * Description: Save time and money by making sure your payments and Direct Debits work first time.
 * Author:      Unified Software
 * Author URI:  https://www.unifiedsoftware.co.uk
 * Text Domain: bankval
 * Version:     0.1.0
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/*
    Copyright (C) 2021 Unified Software Limited
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

namespace UNIFIED_SOFTWARE\BANKVAL;

if ( ! defined( 'WPINC' ) ) {
    die();
}

foreach( glob( plugin_dir_path( __FILE__ ) . '*.php' ) as $file ) {
    if ( ! strpos( $file, 'uninstall.php' ) ) {
        require_once $file;
    }
}

define( __NAMESPACE__ . '\UNIFIED_SOFTWARE_SETTINGS_URL', esc_url(
    add_query_arg(
        'page',
        'unified_software',
        get_admin_url() . 'admin.php'
    ))
);


function bankval_form() {
    check_ajax_referer( 'bankval_form' );
    $accno = sanitize_text_field($_REQUEST['accno']);
    $sortcode = sanitize_text_field($_REQUEST['sortcode']);

    $crypto = Crypto::getInstance();
    $encryptionKey = $crypto->get_encryption_key();
    if ( ! $encryptionKey ) {
        wp_send_json_error('Missing security keys', 500);
    }

    $creds = get_unified_software_credentials();
    
    if ( ! $creds ) {
        wp_send_json_error('Missing credentials', 500);
    }

    $creds = $crypto->decrypt_arr( $creds, $encryptionKey );
    $pin = $creds['pin']; $uname = $creds['uname'];
    $response = bankval($sortcode, $accno, $uname, $pin);
    $id = is_array( $response ) && array_key_exists( 'validationID', $response )
        ? $response['validationID'] : NULL;

    $option = get_option('unified_software_bankval');
    $should_write = $option['mysql_table'] != '';
    
    if ( $response ) {
        if ( $should_write ) {
            insert_into_bankval_table( $id, $sortcode, $accno, $response['result'] );
        }
        wp_send_json_success( $response, 200 );
    } else {
        if ( $should_write ) {
            insert_into_bankval_table( $id, $sortcode, $accno, 'Server error' );
        }
        wp_send_json_error( $response, 500 );
    }

    die();
}

function render_bankval_form() {
    ob_start();
    include 'template/bankval-form-template.php';
    return ob_get_clean();
}

function register_bankval_scripts() {
    wp_register_script(
        'bankval-form-script',
        plugin_dir_url( __FILE__ ) . 'js/bankval-form.js',
        array( 'jquery' )
    );

    wp_add_inline_script(
        'bankval-form-script',
        'const BANKVAL_DATA = ' . json_encode(
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' )
            )),
        'before'
    );

    wp_enqueue_script( 'bankval-form-script' );
}

function register_bankval_styles() {
    wp_register_style(
        'bankval-form-style',
        plugin_dir_url( __FILE__ ) . 'css/bankval-form.css'
    );

    wp_enqueue_style( 'bankval-form-style' );
}

function on_bankval_activation() {
    if ( ! get_option( 'unified_software_bankval' ) ) {
        if ( ! does_table_exist( 'bankval' ) ) {
            update_option( 'unified_software_bankval', array( 'mysql_table' => 'bankval' ) );
            create_bankval_table( 'bankval' );
        } else if ( is_bankval_table( 'bankval' ) ) {
            update_option( 'unified_software_bankval', array( 'mysql_table' => 'bankval' ) );
        }
    }
}

function bankval_action_links( $links ) {
    $url = constant( __NAMESPACE__ . '\UNIFIED_SOFTWARE_SETTINGS_URL' );
    $settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';

    array_unshift(
        $links,
        $settings_link
    );

    return $links;
}

function bankval_meta_links( $links, $plugin_file_name, $plugin_data, $status ) {
    if ( strpos( $plugin_file_name, basename(__FILE__) ) ) {
        $url = constant( __NAMESPACE__ . '\UNIFIED_SOFTWARE_SETTINGS_URL' );
        $support_link = 'https://www.unifiedsoftware.co.uk/contact/';

        $links[] = "<a href='$url'>" . __( 'Settings' ) . '</a>';
        $links[] = "<a href='$support_link'>" . __( 'Support' ) . '</a>';
    }
  
    return $links;
}

add_shortcode( 'bankval_form', __NAMESPACE__ . '\render_bankval_form' );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\register_bankval_scripts' );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\register_bankval_styles' );
add_action( 'wp_ajax_bankval_form', __NAMESPACE__ . '\bankval_form' );
add_action( 'wp_ajax_nopriv_bankval_form', __NAMESPACE__ . '\bankval_form' );
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), __NAMESPACE__ . '\bankval_action_links' );
add_filter( 'plugin_row_meta', __NAMESPACE__ . '\bankval_meta_links', 10, 4 );

register_activation_hook( __FILE__, __NAMESPACE__ . '\on_bankval_activation' );

?>