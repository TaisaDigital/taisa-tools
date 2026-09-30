<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Return only locations where temporary/cache files are expected. */
function taisa_tools_storage_roots() {
    $roots = array();
    $uploads = wp_get_upload_dir();
    if ( empty( $uploads['error'] ) && ! empty( $uploads['basedir'] ) ) {
        $roots['uploads'] = untrailingslashit( $uploads['basedir'] );
    }
    if ( defined( 'WP_CONTENT_DIR' ) ) {
        $content = untrailingslashit( WP_CONTENT_DIR );
        foreach ( array( 'cache', 'upgrade', 'languages' ) as $name ) {
            if ( is_dir( $content . '/' . $name ) ) { $roots[ $name ] = $content . '/' . $name; }
        }
    }
    return $roots;
}

function taisa_tools_is_storage_candidate( $path, $in_cache_dir = false ) {
    $name = strtolower( basename( $path ) );
    $dir  = strtolower( basename( dirname( $path ) ) );
    $temporary = (bool) preg_match( '/(?:\.tmp|\.temp|\.part|\.crdownload|\.swp|\.swo|\.bak)$/i', $name );
    $temporary = $temporary || ( 0 === strpos( $name, '~' ) );
    $temporary = $temporary || (bool) preg_match( '/\.(?:mo|po|pot|woff|woff2|ttf|otf)\.(?:tmp|temp|part)$/i', $name );
    $cache_dir = $in_cache_dir || in_array( $dir, array( 'cache', 'caches', 'tmp', 'temp', 'et-cache', 'w3tc-cache' ), true ) || (bool) preg_match( '#[\\/]((?:cache|caches|tmp|temp|et-cache|w3tc-cache))[\\/]#i', wp_normalize_path( $path ) );
    return $temporary || ( $cache_dir && (bool) preg_match( '/\.(?:cache|log)$/i', $name ) );
}

function taisa_tools_scan_storage( $limit = 5000 ) {
    $result = array( 'files' => array(), 'directories' => 0, 'scanned' => 0, 'truncated' => false );
    foreach ( taisa_tools_storage_roots() as $label => $root ) {
        if ( ! is_dir( $root ) || ! is_readable( $root ) ) { continue; }
        try {
            $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
            foreach ( $iterator as $file ) {
                $result['scanned']++;
                if ( $file->isDir() ) { $result['directories']++; continue; }
                if ( taisa_tools_is_storage_candidate( $file->getPathname(), false ) ) {
                    $result['files'][] = array( 'path' => $file->getPathname(), 'label' => $label, 'size' => (int) $file->getSize() );
                }
                if ( $result['scanned'] >= $limit ) { $result['truncated'] = true; break; }
            }
        } catch ( Exception $e ) { /* A missing/unreadable subtree must not break the admin page. */ }
        if ( $result['truncated'] ) { break; }
    }
    return $result;
}

function taisa_tools_delete_storage_candidates( $paths ) {
    $deleted = 0; $bytes = 0;
    $allowed = taisa_tools_storage_roots();
    foreach ( (array) $paths as $path ) {
        $path = wp_normalize_path( (string) $path );
        $safe = false;
        foreach ( $allowed as $root ) {
            $root = trailingslashit( wp_normalize_path( $root ) );
            if ( 0 === strpos( $path, $root ) && is_file( $path ) && taisa_tools_is_storage_candidate( $path ) ) { $safe = true; break; }
        }
        if ( $safe ) {
            $size = (int) @filesize( $path );
            wp_delete_file( $path );
            if ( ! is_file( $path ) ) { $deleted++; $bytes += $size; }
        }
    }
    return array( 'deleted' => $deleted, 'bytes' => $bytes );
}

