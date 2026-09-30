<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Simple 503 maintenance mode for non-logged-in visitors.
 * Admins (manage_options) can browse the site normally.
 */
add_action( 'template_redirect', function() {
    if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) { return; }

    // Allow REST API and admin-ajax for functionality
    if ( defined('REST_REQUEST') && REST_REQUEST ) { return; }
    if ( defined('DOING_AJAX') && DOING_AJAX ) { return; }
    if ( is_admin() ) { return; }

    // Customizable template path inside plugin
    $template = TAISA_TOOLS_PATH . 'includes/maintenance-template.php';

    // Send 503 headers
    status_header( 503 );
    header( 'Retry-After: 3600' ); // 1 hour

    // Basic HTML fallback if no template file
    if ( ! file_exists( $template ) ) {
        echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>Mantenimiento</title>';
        echo '<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f7f7fb} .box{max-width:640px;background:#fff;padding:32px;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.06);text-align:center} h1{margin:0 0 8px} p{color:#555} .small{font-size:12px;color:#999;margin-top:16px}</style>';
        echo '</head><body><div class="box"><h1>Estamos en mantenimiento</h1><p>Volvemos en breve. Gracias por tu paciencia ✨</p><p class="small">Código por Taisa Tools</p></div></body></html>';
        exit;
    }

    // Use template
    include $template;
    exit;
}, 0 );

