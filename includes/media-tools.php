<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function taisa_tools_disabled_image_sizes() {
    $sizes = get_option( 'taisa_tools_disabled_image_sizes', array() );
    return is_array( $sizes ) ? array_values( array_unique( array_map( 'sanitize_key', $sizes ) ) ) : array();
}

/** Preserve existing Image Optimizer selections as this becomes the canonical setting. */
function taisa_tools_migrate_disabled_image_sizes() {
    if ( false !== get_option( 'taisa_tools_disabled_image_sizes', false ) ) { return; }
    $legacy = get_option( 'taisa_image_optimizer_settings', array() );
    if ( is_array( $legacy ) && ! empty( $legacy['disabled_sizes'] ) && is_array( $legacy['disabled_sizes'] ) ) {
        update_option( 'taisa_tools_disabled_image_sizes', array_values( array_unique( array_map( 'sanitize_key', $legacy['disabled_sizes'] ) ) ), false );
    }
}
add_action( 'plugins_loaded', 'taisa_tools_migrate_disabled_image_sizes', 1 );

add_filter( 'intermediate_image_sizes_advanced', function( $sizes ) {
    foreach ( taisa_tools_disabled_image_sizes() as $disabled ) { unset( $sizes[ $disabled ] ); }
    return $sizes;
}, 999 );

add_filter( 'image_size_names_choose', function( $sizes ) {
    foreach ( taisa_tools_disabled_image_sizes() as $disabled ) { unset( $sizes[ $disabled ] ); }
    return $sizes;
}, 999 );

