<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function taisa_tools_disabled_image_sizes() {
    $sizes = get_option( 'taisa_tools_disabled_image_sizes', array() );
    return is_array( $sizes ) ? array_map( 'sanitize_key', $sizes ) : array();
}

add_filter( 'intermediate_image_sizes_advanced', function( $sizes ) {
    foreach ( taisa_tools_disabled_image_sizes() as $disabled ) { unset( $sizes[ $disabled ] ); }
    return $sizes;
}, 999 );

function taisa_tools_media_size_report() {
    $registered = function_exists( 'wp_get_registered_image_subsizes' ) ? wp_get_registered_image_subsizes() : array();
    foreach ( get_intermediate_image_sizes() as $name ) {
        if ( ! isset( $registered[ $name ] ) ) { $registered[ $name ] = array( 'width' => 0, 'height' => 0, 'crop' => false ); }
    }
    $report = array();
    foreach ( $registered as $name => $definition ) {
        $report[ $name ] = array( 'width' => (int) $definition['width'], 'height' => (int) $definition['height'], 'crop' => ! empty( $definition['crop'] ), 'attachments' => 0, 'files' => 0, 'bytes' => 0 );
    }
    $ids = get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'post_status' => 'inherit', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true ) );
    foreach ( $ids as $id ) {
        $meta = wp_get_attachment_metadata( $id );
        if ( empty( $meta['sizes'] ) || ! is_array( $meta['sizes'] ) ) { continue; }
        foreach ( $meta['sizes'] as $name => $data ) {
            if ( ! isset( $report[ $name ] ) ) { $report[ $name ] = array( 'width' => 0, 'height' => 0, 'crop' => false, 'attachments' => 0, 'files' => 0, 'bytes' => 0 ); }
            $report[ $name ]['attachments']++;
            $report[ $name ]['files']++;
            $file = trailingslashit( dirname( get_attached_file( $id ) ) ) . basename( $data['file'] );
            if ( is_file( $file ) ) { $report[ $name ]['bytes'] += (int) filesize( $file ); }
        }
    }
    return $report;
}

function taisa_tools_delete_media_sizes( $requested ) {
    $requested = array_unique( array_filter( array_map( 'sanitize_key', (array) $requested ) ) );
    $deleted = 0; $bytes = 0; $attachments = 0;
    $ids = get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'post_status' => 'inherit', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true ) );
    foreach ( $ids as $id ) {
        $meta = wp_get_attachment_metadata( $id );
        if ( empty( $meta['sizes'] ) || ! is_array( $meta['sizes'] ) ) { continue; }
        $changed = false; $directory = dirname( get_attached_file( $id ) );
        foreach ( $requested as $name ) {
            if ( empty( $meta['sizes'][ $name ]['file'] ) ) { continue; }
            $file = trailingslashit( $directory ) . basename( $meta['sizes'][ $name ]['file'] );
            $original = basename( get_attached_file( $id ) );
            if ( $file !== trailingslashit( $directory ) . $original && is_file( $file ) ) {
                $size = (int) filesize( $file );
                wp_delete_file( $file );
                if ( ! is_file( $file ) ) { $deleted++; $bytes += $size; }
            }
            unset( $meta['sizes'][ $name ] ); $changed = true;
        }
        if ( $changed ) { wp_update_attachment_metadata( $id, $meta ); $attachments++; }
    }
    return array( 'deleted' => $deleted, 'bytes' => $bytes, 'attachments' => $attachments );
}

