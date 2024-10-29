<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die();
}

if ( ! function_exists( 'get_plugins' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$all_plugins = get_plugins();
$unified_software_plugins_count = 0;
$unified_software_plugins_array = array( 'bankval', 'emailval' );

foreach ( $all_plugins as $plugin_file => $plugin_meta ) {
    if ( in_array( $plugin_meta['TextDomain'], $unified_software_plugins_array ) ) {
        $unified_software_plugins_count++;
    }
}

if ( $unified_software_plugins_count == 1 ) {
    delete_option( 'unified_software_credentials' );
    delete_option( 'unified_software_encryption_key' );
}

delete_option( 'unified_software_bankval' );

?>