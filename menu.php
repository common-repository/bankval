<?php

namespace UNIFIED_SOFTWARE\BANKVAL;

add_action( 'admin_menu', __NAMESPACE__ . '\unified_software_add_admin_menu' );
add_action( 'admin_init', __NAMESPACE__ . '\unified_software_credentials_init' );

function unified_software_add_admin_menu() {

    if ( ! empty ( $GLOBALS['admin_page_hooks']['unified_software'] ) )
        return;

    add_menu_page(
        'Unified Software Settings',
        'Unified Software',
        'manage_options',
        'unified_software',
        __NAMESPACE__ . '\unified_software_options_page'
    );

    add_submenu_page(
        'unified_software',
        'Unified Software Settings',
        'General',
        'manage_options',
        'unified_software',
        __NAMESPACE__ . '\unified_software_options_page'
    );
}

function unified_software_credentials_init() {
    
    if ( array_key_exists( 'unified_software_credentials', get_registered_settings() ) )
        return;

    register_setting( 'credentials', 'unified_software_credentials' );

    add_settings_section(
        'unified_software_credentials_section', 
        __( 'Unified Software General Settings', 'unified-software' ), 
        __NAMESPACE__ . '\credentials_section_callback',
        'credentials'
    );

    add_settings_field(
        'pin', 
        __( 'pin', 'unified-software' ), 
        __NAMESPACE__ . '\pin_field_render', 
        'credentials', 
        'unified_software_credentials_section'
    );

    add_settings_field(
        'uname', 
        __( 'uname', 'unified-software' ), 
        __NAMESPACE__ . '\uname_field_render', 
        'credentials', 
        'unified_software_credentials_section' 
    );
}

function unified_software_options_page() {
    if ( ! current_user_can( 'manage_options') ) {
        wp_die( '<h1>You lack permission to manage_options</h1>' );
    }

    $crypto = Crypto::getInstance();
    if ( ! $crypto->get_encryption_key() ) {
        wp_die( '<h1>No security keys found in your Wordpress installation</h1>' );
    }

    ?>
        <form action="options.php" method="post">
            <?php
                settings_fields( 'credentials' );
                do_settings_sections( 'credentials' );
                submit_button();
            ?>
        </form>
    <?php
}

// Section Callbacks

function credentials_section_callback() {
    echo __( 'Set up your Unified Software credentials', 'unified-software' );
}

// Field Callbacks

function uname_field_render() {
    $name = 'uname';
    $option = get_option( 'unified_software_credentials' );
    $value = prefill_input( $option, $name );
    ?>
        <input title='alphanumeric' pattern='[a-zA-Z0-9]+' id='<?php echo esc_attr( $name ); ?>'
            name='<?php echo sprintf( 'unified_software_credentials[%s]', esc_attr( $name ) ); ?>'
            type='text' value='<?php echo esc_attr( $value ) ?>'
        >
    <?php
}

function pin_field_render() {
    $name = 'pin';
    $option = get_option( 'unified_software_credentials' );
    $value = prefill_input( $option, $name );
    ?>
        <input title='numeric' pattern='[0-9]+' id='<?php echo esc_attr( $name ) ?>'
            name='<?php echo sprintf( 'unified_software_credentials[%s]', esc_attr( $name ) ) ?>'
            type='text' value='<?php echo esc_attr( $value ) ?>'
        >
    <?php
}

function prefill_input( $option, $key ) {
    if ( ! is_array( $option ) || ! array_key_exists( $key, $option ) || '' == $option[$key] ) {
        return;
    }

    $crypto = Crypto::getInstance();
    $encryptionKey = $crypto->get_encryption_key();
    $decrypted = $crypto->decrypt( $option[$key], $encryptionKey );
    return $decrypted;
}

if ( ! array_key_exists( 'pre_update_option_unified_software_credentials', $GLOBALS['wp_filter'] ) ) {
    add_filter( 'pre_update_option_unified_software_credentials', function( $value, $oldValue )  {
        $crypto = Crypto::getInstance();
        $encryptionKey = $crypto->get_encryption_key();
        foreach( $value as $k => $v ) {
            $value[$k] = sanitize_text_field( $v );
            $value[$k] = $crypto->encrypt( $v, $encryptionKey );
        }
    
        return $value;
    }, 10, 2);
}

?>