<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza la pantalla de gestión de carpetas físicas.
 */
function gmp_render_admin_page() {
    $base_dir = gmp_get_base_dir();
    $folders  = gmp_list_folders();
    ?>
    <div class="gmp-admin-wrap">
        <h1><?php esc_html_e('Galerías de medios', 'galeria-multimedia-pro'); ?></h1>
        <p class="description"><?php esc_html_e('Las carpetas viven en /wp-content/uploads/galerias/. Los archivos se publican al instante y solo pueden eliminarse desde aquí.', 'galeria-multimedia-pro'); ?></p>

        <div class="gmp-grid gmp-admin-panels">
            <div class="gmp-panel">
                <h2><?php esc_html_e('Crear carpeta', 'galeria-multimedia-pro'); ?></h2>
                <form id="gmp-create-folder" class="gmp-inline-form">
                    <input type="text" name="folder_name" placeholder="ej: eventos-2024" required />
                    <button class="button button-primary" type="submit"><?php esc_html_e('Crear', 'galeria-multimedia-pro'); ?></button>
                </form>
                <p class="gmp-small muted"><?php esc_html_e('Se crearán subcarpetas /imagenes y /documentos automáticamente.', 'galeria-multimedia-pro'); ?></p>
            </div>
            <div class="gmp-panel">
                <h2><?php esc_html_e('Seleccionar carpeta', 'galeria-multimedia-pro'); ?></h2>
                <select id="gmp-folder-select">
                    <option value="" disabled selected><?php esc_html_e('Elige o crea una carpeta', 'galeria-multimedia-pro'); ?></option>
                    <?php foreach ($folders as $folder) : ?>
                        <option value="<?php echo esc_attr($folder); ?>"><?php echo esc_html($folder); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="gmp-shortcode-hint">
                    <p><?php esc_html_e('Shortcode', 'galeria-multimedia-pro'); ?>:</p>
                    <code id="gmp-shortcode-example">[galeria carpeta="" vista="grid" por_pagina="20"]</code>
                    <button class="button" id="gmp-copy-shortcode"><?php esc_html_e('Copiar', 'galeria-multimedia-pro'); ?></button>
                </div>
            </div>
        </div>

        <div class="gmp-grid gmp-admin-panels">
            <div class="gmp-panel">
                <h2><?php esc_html_e('Imágenes', 'galeria-multimedia-pro'); ?></h2>
                <form id="gmp-dropzone-images" class="dropzone gmp-dropzone" data-type="imagenes"></form>
                <div class="gmp-progress"><span class="gmp-bar"></span></div>
                <div id="gmp-image-list" class="gmp-file-list"></div>
            </div>
            <div class="gmp-panel">
                <h2><?php esc_html_e('Documentos', 'galeria-multimedia-pro'); ?></h2>
                <form id="gmp-dropzone-docs" class="dropzone gmp-dropzone" data-type="documentos"></form>
                <div class="gmp-progress"><span class="gmp-bar"></span></div>
                <div id="gmp-doc-list" class="gmp-file-list"></div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Devuelve carpetas disponibles.
 */
function gmp_list_folders() {
    $base = gmp_get_base_dir();
    if (!is_dir($base)) {
        return [];
    }
    $items = scandir($base);
    $folders = [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        if (is_dir(trailingslashit($base) . $item)) {
            $folders[] = $item;
        }
    }
    sort($folders);
    return $folders;
}
