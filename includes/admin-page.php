<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'admin_menu', function() {
    add_options_page(
        __('Taisa Tools','taisa-tools'),
        __('Taisa Tools','taisa-tools'),
        'manage_options',
        'taisa-tools',
        'taisa_tools_render_admin_page'
    );
});

function taisa_tools_render_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'No tienes permisos suficientes.', 'taisa-tools' ) );
    }

    $settings = taisa_tools_get_settings();
    $constants = array(
        'maintenance' => defined('TAISA_TOOLS_MAINTENANCE'),
        'head'        => defined('TAISA_TOOLS_HEAD_ENABLED'),
        'perf'        => defined('TAISA_TOOLS_PERF_ENABLED'),
    );

    $storage_scan = null;

    // Handle POST
    if ( isset($_POST['taisa_tools_nonce']) && wp_verify_nonce( sanitize_text_field( wp_unslash($_POST['taisa_tools_nonce']) ), 'taisa_tools_save' ) ) {
        $action = isset( $_POST['taisa_tools_action'] ) ? sanitize_key( wp_unslash( $_POST['taisa_tools_action'] ) ) : 'save_settings';
        if ( 'scan_storage' === $action ) {
            $storage_scan = taisa_tools_scan_storage();
        } elseif ( 'delete_storage' === $action ) {
            $selected = isset( $_POST['storage_files'] ) ? (array) wp_unslash( $_POST['storage_files'] ) : array();
            $result = taisa_tools_delete_storage_candidates( $selected );
            echo '<div class="updated notice"><p>' . esc_html( sprintf( __( 'Eliminados %d ficheros temporales (%s).', 'taisa-tools' ), $result['deleted'], size_format( $result['bytes'] ) ) ) . '</p></div>';
            $storage_scan = taisa_tools_scan_storage();
        } elseif ( 'save_media_settings' === $action ) {
            $enabled_sizes = isset( $_POST['enabled_sizes'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['enabled_sizes'] ) ) : array();
            update_option( 'taisa_tools_disabled_image_sizes', array_values( array_diff( get_intermediate_image_sizes(), $enabled_sizes ) ), false );
            echo '<div class="updated notice"><p>' . esc_html__('Tamaños futuros actualizados.', 'taisa-tools') . '</p></div>';
        }
        // Only save if constants are not overriding
        $new = array(
            'maintenance_enabled' => $constants['maintenance'] ? $settings['maintenance_enabled'] : ! empty($_POST['maintenance_enabled']),
            'custom_head_enabled' => $constants['head'] ? $settings['custom_head_enabled'] : ! empty($_POST['custom_head_enabled']),
            'performance_enabled' => $constants['perf'] ? $settings['performance_enabled'] : ! empty($_POST['performance_enabled']),
        );
        if ( 'save_settings' === $action ) {
            update_option( 'taisa_tools_settings', $new, false );
        }
        $settings = taisa_tools_get_settings();
        if ( 'save_settings' === $action ) { echo '<div class="updated notice"><p>' . esc_html__('Ajustes guardados.', 'taisa-tools') . '</p></div>'; }
    }


    wp_enqueue_style( 'taisa-tools-admin', TAISA_TOOLS_URL . 'assets/admin.css', array(), TAISA_TOOLS_VERSION );
    ?>
    <div class="wrap taisa-tools-wrap">
        <h1><?php echo esc_html__('Taisa Tools – Funciones personalizadas','taisa-tools'); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field( 'taisa_tools_save', 'taisa_tools_nonce' ); ?>

            <div class="taisa-card">
                <h2><?php echo esc_html__('Modo mantenimiento','taisa-tools'); ?></h2>
                <label class="switch">
                    <input type="checkbox" name="maintenance_enabled" value="1" <?php checked( $settings['maintenance_enabled'] ); ?> <?php disabled( $constants['maintenance'] ); ?>>
                    <span class="slider"></span>
                </label>
                <?php if ( $constants['maintenance'] ) : ?>
                    <p class="desc"><?php echo esc_html__('Controlado por constante TAISA_TOOLS_MAINTENANCE en wp-config.php','taisa-tools'); ?></p>
                <?php endif; ?>
                <p class="desc"><?php echo esc_html__('Muestra una página 503 a usuarios no autenticados. Los administradores autenticados ven la web normalmente.','taisa-tools'); ?></p>
            </div>

            <div class="taisa-card">
                <h2><?php echo esc_html__('Actualizaciones desde GitHub','taisa-tools'); ?></h2>
                <p class="desc"><?php echo wp_kses_post( sprintf( __( 'Repositorio de actualizaciones: <a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>. No necesitas configurar wp-config.php.', 'taisa-tools' ), esc_url( 'https://github.com/TaisaDigital/taisa-tools' ), esc_html( taisa_tools_github_repo() ) ) ); ?></p>
            </div>

            <div class="taisa-card">
                <h2><?php echo esc_html__('Código adicional en <head>','taisa-tools'); ?></h2>
                <label class="switch">
                    <input type="checkbox" name="custom_head_enabled" value="1" <?php checked( $settings['custom_head_enabled'] ); ?> <?php disabled( $constants['head'] ); ?>>
                    <span class="slider"></span>
                </label>
                <?php if ( $constants['head'] ) : ?>
                    <p class="desc"><?php echo esc_html__('Controlado por constante TAISA_TOOLS_HEAD_ENABLED en wp-config.php','taisa-tools'); ?></p>
                <?php endif; ?>
                <p class="desc">
                    <?php echo wp_kses_post( sprintf(
                        __('Insertará el contenido del archivo <code>%s</code> si existe y es legible.','taisa-tools'),
                        '/wp-content/uploads/taisa-custom/head-snippet.php'
                    ) ); ?>
                </p>
            </div>

            <div class="taisa-card">
                <h2><?php echo esc_html__('Mejoras de rendimiento','taisa-tools'); ?></h2>
                <label class="switch">
                    <input type="checkbox" name="performance_enabled" value="1" <?php checked( $settings['performance_enabled'] ); ?> <?php disabled( $constants['perf'] ); ?>>
                    <span class="slider"></span>
                </label>
                <?php if ( $constants['perf'] ) : ?>
                    <p class="desc"><?php echo esc_html__('Controlado por constante TAISA_TOOLS_PERF_ENABLED en wp-config.php','taisa-tools'); ?></p>
                <?php endif; ?>
                <ul class="desc">
                    <li><?php echo esc_html__('Desactiva emojis nativos','taisa-tools'); ?></li>
                    <li><?php echo esc_html__('Desactiva embeds oEmbed','taisa-tools'); ?></li>
                    <li><?php echo esc_html__('Limpia cabeceras (wlw, rsd, shortlink, generator)','taisa-tools'); ?></li>
                </ul>
            </div>

            <p><button type="submit" class="button button-primary"><?php echo esc_html__('Guardar cambios','taisa-tools'); ?></button></p>
        </form>

        <div class="taisa-card">
            <h2><?php echo esc_html__('Control de inodos y temporales','taisa-tools'); ?></h2>
            <p class="desc"><?php echo esc_html__('Busca solo ficheros con nombres temporales o dentro de carpetas de caché conocidas. Primero revisa el informe; la eliminación requiere seleccionar los elementos.','taisa-tools'); ?></p>
            <form method="post">
                <?php wp_nonce_field( 'taisa_tools_save', 'taisa_tools_nonce' ); ?><input type="hidden" name="taisa_tools_action" value="scan_storage" />
                <button class="button" type="submit"><?php echo esc_html__('Analizar temporales','taisa-tools'); ?></button>
            </form>
            <?php if ( is_array( $storage_scan ) ) : ?>
                <p><?php echo esc_html( sprintf( __('Candidatos: %d · Elementos revisados: %d%s','taisa-tools'), count($storage_scan['files']), $storage_scan['scanned'], $storage_scan['truncated'] ? __(' · límite alcanzado','taisa-tools') : '' ) ); ?></p>
                <?php if ( $storage_scan['files'] ) : ?><form method="post"><?php wp_nonce_field( 'taisa_tools_save', 'taisa_tools_nonce' ); ?><input type="hidden" name="taisa_tools_action" value="delete_storage" />
                    <?php foreach ( $storage_scan['files'] as $file ) : ?><label style="display:block"><input type="checkbox" name="storage_files[]" value="<?php echo esc_attr( $file['path'] ); ?>" checked /> <?php echo esc_html( $file['label'] . ': ' . str_replace( ABSPATH, '', $file['path'] ) . ' (' . size_format( $file['size'] ) . ')' ); ?></label><?php endforeach; ?>
                    <p><button class="button button-secondary" type="submit" onclick="return confirm('<?php echo esc_js( __('¿Eliminar los temporales seleccionados? Comprueba antes que no hay una tarea en curso.', 'taisa-tools') ); ?>');"><?php echo esc_html__('Eliminar seleccionados','taisa-tools'); ?></button></p>
                </form><?php else : ?><p><?php echo esc_html__('No se han encontrado candidatos.','taisa-tools'); ?></p><?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="taisa-card">
            <h2><?php echo esc_html__('Generación de tamaños de medios','taisa-tools'); ?></h2>
            <p class="desc"><?php echo esc_html__('Desmarca los tamaños que no quieras generar en futuras subidas o regeneraciones. Esta pantalla no borra archivos existentes; para analizarlos y limpiarlos usa Taisa Image Optimizer.','taisa-tools'); ?></p>
            <?php
            $registered_sizes = function_exists( 'wp_get_registered_image_subsizes' ) ? wp_get_registered_image_subsizes() : array();
            foreach ( get_intermediate_image_sizes() as $size_name ) {
                if ( ! isset( $registered_sizes[ $size_name ] ) ) { $registered_sizes[ $size_name ] = array( 'width' => 0, 'height' => 0, 'crop' => false ); }
            }
            $disabled_sizes = taisa_tools_disabled_image_sizes();
            ?>
            <form method="post"><?php wp_nonce_field( 'taisa_tools_save', 'taisa_tools_nonce' ); ?><input type="hidden" name="taisa_tools_action" value="save_media_settings" />
                <table class="widefat striped"><thead><tr><th><?php echo esc_html__('Generar','taisa-tools'); ?></th><th><?php echo esc_html__('Tamaño','taisa-tools'); ?></th><th><?php echo esc_html__('Dimensiones','taisa-tools'); ?></th><th><?php echo esc_html__('Recorte','taisa-tools'); ?></th></tr></thead><tbody>
                <?php foreach ( $registered_sizes as $name => $definition ) : ?><tr><td><input type="checkbox" name="enabled_sizes[]" value="<?php echo esc_attr( $name ); ?>" <?php checked( ! in_array( $name, $disabled_sizes, true ) ); ?> /></td><td><code><?php echo esc_html( $name ); ?></code></td><td><?php echo esc_html( absint( $definition['width'] ) . ' × ' . absint( $definition['height'] ) ); ?></td><td><?php echo esc_html( empty( $definition['crop'] ) ? __('Proporcional','taisa-tools') : __('Recortado','taisa-tools') ); ?></td></tr><?php endforeach; ?></tbody></table>
                <p><button class="button button-primary" type="submit"><?php echo esc_html__('Guardar generación de tamaños','taisa-tools'); ?></button></p>
            </form>
        </div>

        <div class="taisa-help">
            <h2><?php echo esc_html__('Notas rápidas','taisa-tools'); ?></h2>
            <ul>
                <li><?php echo wp_kses_post(__('Para evitar escribir en la base de datos puedes usar constantes en <code>wp-config.php</code>.','taisa-tools')); ?></li>
                <li><?php echo wp_kses_post(__('Para el snippet del &lt;head&gt;, crea el archivo <code>/wp-content/uploads/taisa-custom/head-snippet.php</code>','taisa-tools')); ?></li>
            </ul>
        </div>
    </div>
    <?php
}

