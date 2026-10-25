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

/**
 * Keep disabled derivatives out of front-end size lookup so WordPress falls
 * back to the original attachment file when a theme requests that size.
 * Leave admin metadata intact for media management and cleanup tools.
 */
add_filter( 'wp_get_attachment_metadata', function( $data, $attachment_id ) {
    if ( is_admin() || ! is_array( $data ) || empty( $data['sizes'] ) || ! is_array( $data['sizes'] ) ) {
        return $data;
    }

    foreach ( taisa_tools_disabled_image_sizes() as $disabled ) {
        unset( $data['sizes'][ $disabled ] );
    }

    return $data;
}, 10, 2 );

