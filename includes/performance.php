<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }


/* EXTRA FOR BIO CON HTML */
remove_filter('pre_user_description', 'wp_filter_kses');
add_filter( 'pre_user_description', 'wp_filter_post_kses' );


/*
|--------------------------------------------------------------------------
| LIMPIEZA HEAD Y METADATOS
|--------------------------------------------------------------------------
*/

// RSD
remove_action( 'wp_head', 'rsd_link' );

// Windows Live Writer
remove_action( 'wp_head', 'wlwmanifest_link' );

// Meta generator
remove_action( 'wp_head', 'wp_generator' );

// Versión en feeds
add_filter( 'the_generator', '__return_empty_string' );

// Shortlink
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'template_redirect', 'wp_shortlink_header', 11 );

// REST link en head
remove_action( 'wp_head', 'rest_output_link_wp_head' );
remove_action( 'template_redirect', 'rest_output_link_header', 11 );

// Rel prev/next
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );

// Feeds en head
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'feed_links_extra', 3 );


/*
|--------------------------------------------------------------------------
| EMOJIS (versión completa optimizada)
|--------------------------------------------------------------------------
*/

function taisa_disable_emojis() {

    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );

    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

    add_filter( 'wp_resource_hints', function( $urls, $relation_type ) {
        if ( 'dns-prefetch' === $relation_type ) {
            return array_filter( $urls, function ( $url ) {
                return false === strpos( $url, 's.w.org' );
            } );
        }
        return $urls;
    }, 10, 2 );

    add_filter( 'tiny_mce_plugins', function( $plugins ) {
        return is_array($plugins) ? array_diff( $plugins, array( 'wpemoji' ) ) : $plugins;
    });

}
add_action( 'init', 'taisa_disable_emojis' );


/*
|--------------------------------------------------------------------------
| PINGBACKS + X-PINGBACK
|--------------------------------------------------------------------------
*/

add_filter( 'pings_open', '__return_false' );

add_filter( 'xmlrpc_methods', function( $methods ) {
    unset( $methods['pingback.ping'] );
    unset( $methods['pingback.extensions.getPingbacks'] );
    return $methods;
});

add_filter( 'wp_headers', function( $headers ) {
    unset( $headers['X-Pingback'] );
    return $headers;
});


/*
|--------------------------------------------------------------------------
| JQUERY MIGRATE (solo frontend)
|--------------------------------------------------------------------------
*/

add_action( 'wp_default_scripts', function( $scripts ) {
    if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
        $script = $scripts->registered['jquery'];
        if ( $script->deps ) {
            $script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
        }
    }
});


/*
|--------------------------------------------------------------------------
| DASHICONS (solo visitantes)
|--------------------------------------------------------------------------
*/

add_action( 'wp_enqueue_scripts', function() {
    if ( ! is_user_logged_in() ) {
        wp_deregister_style( 'dashicons' );
    }
});


/*
|--------------------------------------------------------------------------
| REST API – Bloquear enumeración de usuarios
|--------------------------------------------------------------------------
*/

add_filter( 'rest_pre_dispatch', function( $result, $server, $request ) {

    $route = $request->get_route();

    if ( strpos( $route, '/wp/v2/users' ) !== false && ! is_user_logged_in() ) {
        return new WP_Error(
            'rest_forbidden',
            'Acceso no permitido.',
            array( 'status' => 403 )
        );
    }

    return $result;

}, 10, 3 );


/*
|--------------------------------------------------------------------------
| ELIMINAR ?ver= DE SCRIPTS Y CSS
|--------------------------------------------------------------------------
*/

function taisa_remove_version_query( $src ) {
    if ( strpos( $src, '?ver=' ) !== false ) {
        $src = remove_query_arg( 'ver', $src );
    }
    return $src;
}
add_filter( 'script_loader_src', 'taisa_remove_version_query', 15 );
add_filter( 'style_loader_src', 'taisa_remove_version_query', 15 );


/*
|--------------------------------------------------------------------------
| EDITOR – BLOQUES REMOTOS Y PATRONES
|--------------------------------------------------------------------------
*/

remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
add_filter( 'should_load_remote_block_patterns', '__return_false' );


/*
|--------------------------------------------------------------------------
| HEARTBEAT FRONTEND
|--------------------------------------------------------------------------
*/

add_action( 'wp_enqueue_scripts', function() {
    if ( ! is_admin() ) {
        wp_deregister_script( 'heartbeat' );
    }
}, 1 );


/*
|--------------------------------------------------------------------------
| RECORDATORIO – AJUSTES RECOMENDADOS EN WP-CONFIG.PHP
|--------------------------------------------------------------------------
|
| Estos NO se activan desde el plugin.
| Añadir manualmente en wp-config.php según proyecto.
|
| define( 'DISALLOW_FILE_EDIT', true );
| define( 'WP_POST_REVISIONS', 5 );
| define( 'DISABLE_WP_CRON', true ); (solo si configuras cron real)
| define( 'CORE_UPGRADE_SKIP_NEW_BUNDLED', true );
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| OPCIONALES (DESCOMENTAR SEGÚN PROYECTO)
|--------------------------------------------------------------------------
*/

// 1️⃣ Desactivar XML-RPC completamente (NO si usan Jetpack)
// add_filter( 'xmlrpc_enabled', '__return_false' );

// 2️⃣ Desactivar lazy loading nativo (NO si no usas WP Rocket u otro)
// add_filter( 'wp_lazy_loading_enabled', '__return_false' );

// 3️⃣ Desactivar sitemaps nativos (solo si hay plugin SEO)
// add_filter( 'wp_sitemaps_enabled', '__return_false' );

// 4️⃣ Desactivar comentarios globalmente (solo webs corporativas)
// — Código omitido aquí a propósito para evitar errores en WooCommerce.

