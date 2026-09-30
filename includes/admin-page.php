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
    $media_report = null;

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
        } elseif ( 'delete_media_sizes' === $action ) {
            $selected = isset( $_POST['media_sizes'] ) ? (array) wp_unslash( $_POST['media_sizes'] ) : array();
            $result = taisa_tools_delete_media_sizes( $selected );
            echo '<div class="updated notice"><p>' . esc_html( sprintf( __( 'Eliminados %d ficheros de variantes en %d adjuntos (%s).', 'taisa-tools' ), $result['deleted'], $result['attachments'], size_format( $result['bytes'] ) ) ) . '</p></div>';
            $media_report = taisa_tools_media_size_report();
        } elseif ( 'scan_media' === $action ) {
            $media_report = taisa_tools_media_size_report();
        } elseif ( 'save_media_settings' === $action ) {
            update_option( 'taisa_tools_disabled_image_sizes', isset( $_POST['disabled_sizes'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['disabled_sizes'] ) ) : array(), false );
            $media_report = taisa_tools_media_size_report();
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
            if ( ! defined( 'TAISA_TOOLS_GITHUB_REPO' ) ) {
                update_option( 'taisa_tools_github_repo', isset( $_POST['github_repo'] ) ? sanitize_text_field( wp_unslash( $_POST['github_repo'] ) ) : '', false );
            }
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
                <p class="desc"><?php echo esc_html__('Usa el repositorio público o privado que contenga las releases del plugin. Para repositorios privados define TAISA_TOOLS_GITHUB_TOKEN en wp-config.php.','taisa-tools'); ?></p>
                <label for="github_repo"><strong><?php echo esc_html__('Repositorio (owner/repo)','taisa-tools'); ?></strong></label>
                <input class="regular-text" type="text" id="github_repo" name="github_repo" value="<?php echo esc_attr( taisa_tools_github_repo() ); ?>" placeholder="taisa/taisa-tools" <?php disabled( defined('TAISA_TOOLS_GITHUB_REPO') ); ?> />
                <?php if ( defined('TAISA_TOOLS_GITHUB_REPO') ) : ?><p class="desc"><?php echo esc_html__('Controlado por TAISA_TOOLS_GITHUB_REPO en wp-config.php.','taisa-tools'); ?></p><?php endif; ?>
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
            <h2><?php echo esc_html__('Tamaños de medios','taisa-tools'); ?></h2>
            <p class="desc"><?php echo esc_html__('Las casillas desactivan la generación futura. El botón de borrado elimina únicamente las variantes registradas en los metadatos de los adjuntos; nunca borra el original. Haz una copia antes de borrar.','taisa-tools'); ?></p>
            <?php if ( $media_report ) : ?><form method="post"><?php wp_nonce_field( 'taisa_tools_save', 'taisa_tools_nonce' ); ?><input type="hidden" name="taisa_tools_action" value="save_media_settings" />
                <table class="widefat striped"><thead><tr><th></th><th><?php echo esc_html__('Tamaño','taisa-tools'); ?></th><th><?php echo esc_html__('Dimensiones','taisa-tools'); ?></th><th><?php echo esc_html__('Adjuntos con variante','taisa-tools'); ?></th><th><?php echo esc_html__('Espacio aproximado','taisa-tools'); ?></th></tr></thead><tbody>
                <?php foreach ( $media_report as $name => $row ) : ?><tr><td><input type="checkbox" name="disabled_sizes[]" value="<?php echo esc_attr( $name ); ?>" <?php checked( in_array( $name, taisa_tools_disabled_image_sizes(), true ) ); ?> /></td><td><code><?php echo esc_html( $name ); ?></code><?php if ( in_array( $name, taisa_tools_disabled_image_sizes(), true ) ) : ?> <em>(<?php echo esc_html__('desactivado','taisa-tools'); ?>)</em><?php endif; ?></td><td><?php echo esc_html( $row['width'] . '×' . $row['height'] . ( $row['crop'] ? ' · crop' : '' ) ); ?></td><td><?php echo esc_html( number_format_i18n( $row['attachments'] ) ); ?></td><td><?php echo esc_html( size_format( $row['bytes'] ) ); ?></td></tr><?php endforeach; ?></tbody></table>
                <p><button class="button button-primary" type="submit"><?php echo esc_html__('Guardar tamaños desactivados','taisa-tools'); ?></button></p>
            </form><?php endif; ?>
            <?php if ( $media_report ) : ?><form method="post"><?php wp_nonce_field( 'taisa_tools_save', 'taisa_tools_nonce' ); ?><input type="hidden" name="taisa_tools_action" value="delete_media_sizes" />
                <p class="desc"><?php echo esc_html__('Selecciona las variantes a borrar:', 'taisa-tools'); ?></p>
                <?php foreach ( $media_report as $name => $row ) : ?><label style="display:inline-block;margin-right:1em"><input type="checkbox" name="media_sizes[]" value="<?php echo esc_attr( $name ); ?>" /> <code><?php echo esc_html( $name ); ?></code></label><?php endforeach; ?>
                <p><button class="button button-secondary" type="submit" onclick="return confirm('<?php echo esc_js( __('¿Borrar las variantes seleccionadas? Esta acción no se puede deshacer sin una copia.', 'taisa-tools') ); ?>');"><?php echo esc_html__('Borrar variantes seleccionadas','taisa-tools'); ?></button></p>
            </form><?php endif; ?>
            <form method="post"><?php wp_nonce_field( 'taisa_tools_save', 'taisa_tools_nonce' ); ?><input type="hidden" name="taisa_tools_action" value="scan_media" /><button class="button" type="submit"><?php echo esc_html__('Actualizar informe de tamaños','taisa-tools'); ?></button></form>
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

