<?php

namespace UNIFIED_SOFTWARE\BANKVAL;

add_action( 'admin_menu', __NAMESPACE__ . '\unified_software_bankval_add_admin_menu' );
add_action( 'admin_init', __NAMESPACE__ . '\unified_software_bankval_init' );

function unified_software_bankval_add_admin_menu() {
    add_submenu_page(
        'unified_software',
        'BankVal Settings',
        'BankVal',
        'manage_options',
        'unified_software_bankval',
        __NAMESPACE__ . '\unified_software_bankval_options_page'
    );
}

function unified_software_bankval_init() {

    register_setting( 'bankval', 'unified_software_bankval' );

    add_settings_section(
        'unified_software_bankval_mysql_section',
        'BankVal MySQL Settings',
        __NAMESPACE__ . '\bankval_mysql_section_callback',
        'bankval'
    );

    add_settings_field(
        'mysql_table',
        __( 'table', 'unified-software' ),
        __NAMESPACE__ . '\bankval_mysql_table_field_render',
        'bankval',
        'unified_software_bankval_mysql_section'
    );

    add_settings_section(
        'unified_software_bankval_misc_section',
        __( 'Miscellaneous', 'unified-software' ),
        __NAMESPACE__ . '\bankval_misc_section_callback',
        'bankval'
    );

    add_settings_field(
        'misc_powered_by',
        __( 'show "powered by" message under BankVal form' ),
        __NAMESPACE__ . '\bankval_misc_powered_by_field_render',
        'bankval',
        'unified_software_bankval_misc_section'
    );
}

function unified_software_bankval_options_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( '<h1>You lack permission to manage_options</h1>' );
    }

    ?>
        <form action="options.php" method="post">
            <?php
                settings_fields( 'bankval' );
                do_settings_sections( 'bankval' );
                submit_button();
            ?>
        </form>
    <?php
}

// Section Callbacks

function bankval_mysql_section_callback() {
    echo 'Set up your preferred MySQL storage location for validated bank accounts';
    $option = get_option( 'unified_software_bankval_mysql_table_error' );
    if ( ! $option ) {
        return;
    }
    ?>
        <p style='color: red'><?php echo esc_html( $option ) ?></p>
    <?php
    delete_option( 'unified_software_bankval_mysql_table_error' ); 
}

function bankval_misc_section_callback() {
    echo 'Configure additional options';  
}

// Field Callbacks

function bankval_mysql_table_field_render() {
    $name = 'mysql_table';
    $option = get_option( 'unified_software_bankval' );
    $value = $option && array_key_exists( $name, $option ) ? $option[$name] : '';
    if ( $value != '' && ( ! does_table_exist( $value ) || ! is_bankval_table( $value ) ) ) {
        $value = '';
        unset( $option[$name] );
        update_option( 'unified_software_bankval', $option );
    }
    ?>
        <input id='<?php echo esc_attr( $name ) ?>' type='text' pattern='[a-z,A-Z,_]+'
            name='<?php echo sprintf( 'unified_software_bankval[%s]', esc_attr( $name ) ) ?>'
            value='<?php echo esc_attr( $value ) ?>' title='letters or underscores'
        >
    <?php
}

function bankval_misc_powered_by_field_render() {
    $name = 'misc_powered_by';
    $option = get_option( 'unified_software_bankval' );
    $value = $option && array_key_exists( $name, $option ) ? $option[$name] : '';
    ?>
        <input id='<?php echo esc_attr( $name ) ?>' type='checkbox' value='1' <?php echo checked( 1, $value, false ) ?>
            name='<?php echo sprintf( 'unified_software_bankval[%s]', esc_attr( $name ) ) ?>'
        >
    <?php
}

add_filter( 'pre_update_option_unified_software_bankval', function( $value, $oldValue ) {

    foreach ( $value as $k => $v ) {
        $v = sanitize_text_field( $v );
        if ( 'mysql_table' == $k && $v != '' ) {
            if ( ! does_table_exist( $v ) ) {
                create_bankval_table( $v );
            } else if ( ! is_bankval_table( $v ) ) {
                $value[$k] = $oldValue[$k];
                add_option(
                    'unified_software_bankval_mysql_table_error',
                    "ERROR: TABLE ${v} ALREADY EXISTS AND DOES NOT MATCH EXPECTED STRUCTURE"
                );
            }
        }
    }

    return $value;
}, 10, 2 );

?>