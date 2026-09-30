<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Inserts custom head snippet from uploads directory if present:
 *   /wp-content/uploads/taisa-custom/head-snippet.php
 * This file is NOT editable from WP Admin for safety.
 */
add_action( 'wp_head', function() {
    $uploads = wp_get_upload_dir();
    if ( empty( $uploads['basedir'] ) ) { return; }
    $file = trailingslashit( $uploads['basedir'] ) . 'taisa-custom/head-snippet.php';

    if ( file_exists( $file ) && is_readable( $file ) ) {
        // Isolate scope to avoid leaking variables
        try {
            include $file;
        } catch ( \Throwable $e ) {
            if ( current_user_can('manage_options') ) {
                echo '<!-- Taisa Tools head-snippet error: ' . esc_html( $e->getMessage() ) . ' -->';
            }
        }
    }
}, 5 );

