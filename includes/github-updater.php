<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function taisa_tools_github_repo() {
    $repo = defined( 'TAISA_TOOLS_GITHUB_REPO' ) ? TAISA_TOOLS_GITHUB_REPO : get_option( 'taisa_tools_github_repo', '' );
    return preg_replace( '/[^A-Za-z0-9_.-]+/', '/', trim( (string) $repo, '/' ) );
}

function taisa_tools_github_release() {
    $repo = taisa_tools_github_repo();
    if ( ! preg_match( '/^[^\/]+\/[^\/]+$/', $repo ) ) { return false; }
    $args = array( 'timeout' => 8, 'headers' => array( 'Accept' => 'application/vnd.github+json', 'User-Agent' => 'Taisa-Tools/' . TAISA_TOOLS_VERSION ) );
    if ( defined( 'TAISA_TOOLS_GITHUB_TOKEN' ) && TAISA_TOOLS_GITHUB_TOKEN ) { $args['headers']['Authorization'] = 'Bearer ' . TAISA_TOOLS_GITHUB_TOKEN; }
    $response = wp_remote_get( 'https://api.github.com/repos/' . $repo . '/releases/latest', $args );
    if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) { return false; }
    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    return is_array( $data ) && ! empty( $data['tag_name'] ) ? $data : false;
}

add_filter( 'pre_set_site_transient_update_plugins', function( $transient ) {
    if ( ! is_object( $transient ) || empty( taisa_tools_github_repo() ) ) { return $transient; }
    $release = get_transient( 'taisa_tools_github_release' );
    if ( false === $release ) { $release = taisa_tools_github_release(); set_transient( 'taisa_tools_github_release', $release ?: array(), 6 * HOUR_IN_SECONDS ); }
    if ( empty( $release['tag_name'] ) ) { return $transient; }
    $version = ltrim( (string) $release['tag_name'], 'vV' );
    if ( version_compare( $version, TAISA_TOOLS_VERSION, '>' ) ) {
        $basename = plugin_basename( dirname( __DIR__ ) . '/taisa-tools.php' );
        $package = ! empty( $release['zipball_url'] ) ? $release['zipball_url'] : '';
        if ( ! empty( $release['assets'] ) ) {
            foreach ( $release['assets'] as $asset ) {
                if ( ! empty( $asset['browser_download_url'] ) && preg_match( '/\.zip$/i', $asset['name'] ) ) { $package = $asset['browser_download_url']; break; }
            }
        }
        $transient->response[ $basename ] = (object) array( 'slug' => dirname( $basename ), 'plugin' => $basename, 'new_version' => $version, 'url' => ! empty( $release['html_url'] ) ? $release['html_url'] : '', 'package' => $package );
    }
    return $transient;
} );

// Also authenticates the ZIP download when a private GitHub repository is used.
add_filter( 'http_request_args', function( $args, $url ) {
    if ( defined( 'TAISA_TOOLS_GITHUB_TOKEN' ) && TAISA_TOOLS_GITHUB_TOKEN && preg_match( '#https://(?:api\.|codeload\.)?github\.com/#i', $url ) ) {
        if ( empty( $args['headers'] ) || ! is_array( $args['headers'] ) ) { $args['headers'] = array(); }
        $args['headers']['Authorization'] = 'Bearer ' . TAISA_TOOLS_GITHUB_TOKEN;
    }
    return $args;
}, 10, 2 );

