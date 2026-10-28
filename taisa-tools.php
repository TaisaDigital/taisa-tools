<?php
/**
 * Plugin Name: Taisa Tools – Funciones personalizadas
 * Description: Mini framework seguro para funciones personalizadas: mantenimiento, snippet de <head> y mejoras de rendimiento.
 * Author: Taisa - Raquel Garcia Arevalo
 * Version: 1.3.1
 * Author URI: https://www.taisadigital.com
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: taisa-tools
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'TAISA_TOOLS_VERSION', '1.3.1' );
define( 'TAISA_TOOLS_GITHUB_REPO', 'TaisaDigital/taisa-tools' );
define( 'TAISA_TOOLS_PATH', plugin_dir_path( __FILE__ ) );
define( 'TAISA_TOOLS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Defaults (single option). You can override with constants in wp-config.php:
 *   define('TAISA_TOOLS_MAINTENANCE', true);
 *   define('TAISA_TOOLS_HEAD_ENABLED', true);
 *   define('TAISA_TOOLS_PERF_ENABLED', true);
 */
function taisa_tools_get_settings() {
    $defaults = array(
        'maintenance_enabled' => false,
        'custom_head_enabled' => false,
        'performance_enabled' => false,
    );

    $opt = get_option( 'taisa_tools_settings', array() );
    if ( ! is_array( $opt ) ) { $opt = array(); }
    $settings = wp_parse_args( $opt, $defaults );

    // Constants override (no DB writes needed)
    if ( defined('TAISA_TOOLS_MAINTENANCE') ) {
        $settings['maintenance_enabled'] = (bool) TAISA_TOOLS_MAINTENANCE;
    }
    if ( defined('TAISA_TOOLS_HEAD_ENABLED') ) {
        $settings['custom_head_enabled'] = (bool) TAISA_TOOLS_HEAD_ENABLED;
    }
    if ( defined('TAISA_TOOLS_PERF_ENABLED') ) {
        $settings['performance_enabled'] = (bool) TAISA_TOOLS_PERF_ENABLED;
    }

    return $settings;
}

/**
 * Admin page
 */
require_once TAISA_TOOLS_PATH . 'includes/admin-page.php';
require_once TAISA_TOOLS_PATH . 'includes/storage-tools.php';
require_once TAISA_TOOLS_PATH . 'includes/media-tools.php';
require_once TAISA_TOOLS_PATH . 'includes/github-updater.php';

/**
 * Conditional includes based on settings
 */
add_action( 'plugins_loaded', function() {
    $s = taisa_tools_get_settings();

    if ( ! empty( $s['maintenance_enabled'] ) ) {
        require_once TAISA_TOOLS_PATH . 'includes/maintenance.php';
    }
    if ( ! empty( $s['custom_head_enabled'] ) ) {
        require_once TAISA_TOOLS_PATH . 'includes/head-snippet.php';
    }
    if ( ! empty( $s['performance_enabled'] ) ) {
        require_once TAISA_TOOLS_PATH . 'includes/performance.php';
    }
});
