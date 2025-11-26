<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode [galeria carpeta="" vista="grid|slider" por_pagina="20"]
 */
function gmp_register_shortcode() {
    add_shortcode('galeria', 'gmp_render_shortcode');
}
add_action('init', 'gmp_register_shortcode');

function gmp_render_shortcode($atts) {
    $atts = shortcode_atts([
        'carpeta'    => '',
        'vista'      => 'grid',
        'por_pagina' => 20,
    ], $atts, 'galeria');

    $folder = gmp_sanitize_folder($atts['carpeta']);
    if (empty($folder)) {
        return '<p class="gmp-empty">' . esc_html__('Especifica la carpeta a mostrar.', 'galeria-multimedia-pro') . '</p>';
    }

    $view = $atts['vista'] === 'slider' ? 'slider' : 'grid';
    $per  = absint($atts['por_pagina']);
    $files = gmp_fetch_files($folder);
    $combined = array_merge($files['imagenes'], $files['documentos']);

    if (empty($combined)) {
        return '<p class="gmp-empty">' . esc_html__('Esta carpeta no tiene archivos aun.', 'galeria-multimedia-pro') . '</p>';
    }

    wp_enqueue_style('gmp-style');
    wp_enqueue_style('gmp-fancybox');
    wp_enqueue_style('gmp-swiper');
    wp_enqueue_script('gmp-script');
    wp_enqueue_script('gmp-fancybox');
    wp_enqueue_script('gmp-swiper');

    wp_localize_script('gmp-script', 'gmpFront', [
        'ajax'  => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gmp_front_nonce'),
    ]);

    ob_start();
    ?>
    <div class="gmp-gallery" data-folder="<?php echo esc_attr($folder); ?>" data-view="<?php echo esc_attr($view); ?>" data-per="<?php echo esc_attr($per); ?>">
        <?php if ($view === 'slider') : ?>
            <div class="swiper gmp-swiper">
                <div class="swiper-wrapper">
                    <?php foreach (array_slice($combined, 0, $per) as $item) { gmp_render_item($item, 'slider'); } ?>
                </div>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        <?php else : ?>
            <div class="gmp-grid-wrap">
                <?php foreach (array_slice($combined, 0, $per) as $item) { gmp_render_item($item, 'grid'); } ?>
            </div>
        <?php endif; ?>

        <?php if (count($combined) > $per) : ?>
            <button class="gmp-load-more" data-page="1"><?php esc_html_e('Cargar mas', 'galeria-multimedia-pro'); ?></button>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renderiza un archivo segun vista.
 */
function gmp_render_item($item, $view) {
    $ext = strtolower($item['ext']);
    $is_image = in_array($ext, ['jpg','jpeg','png','webp','gif'], true);
    $classes = $is_image ? 'gmp-item gmp-image' : 'gmp-item gmp-doc';
    $wrapper = $view === 'slider' ? 'swiper-slide' : '';
    $previews = isset($item['previews']) && is_array($item['previews']) ? $item['previews'] : [];
    $thumb_src = gmp_resolve_thumb($item, $is_image, $previews, $ext);
    $doc_viewer = $is_image ? '' : gmp_document_view_url($item['url'], $ext);
    $viewer = $is_image ? $thumb_src : ($doc_viewer ?: $thumb_src);
    $viewer_type = (!$is_image && $doc_viewer) ? 'iframe' : 'image';
    ?>
    <div class="<?php echo esc_attr(trim($classes . ' ' . $wrapper)); ?>">
        <div class="gmp-thumb-wrap">
            <div class="gmp-thumb-actions">
                <?php if (!empty($viewer)) : ?>
                    <a class="gmp-thumb-icon gmp-thumb-view" href="<?php echo esc_url($viewer); ?>" data-fancybox="gmp-modal" data-type="<?php echo esc_attr($viewer_type); ?>" aria-label="<?php esc_attr_e('Ver', 'galeria-multimedia-pro'); ?>" data-tip="<?php esc_attr_e('Ver vista previa', 'galeria-multimedia-pro'); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 5C6.5 5 2.5 9.5 2 12c.5 2.5 4.5 7 10 7s9.5-4.5 10-7c-.5-2.5-4.5-7-10-7Z" stroke="currentColor" stroke-width="2" fill="none"/>
                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/>
                        </svg>
                    </a>
                <?php endif; ?>
                <a class="gmp-thumb-icon gmp-thumb-download" href="<?php echo esc_url($item['url']); ?>" download aria-label="<?php esc_attr_e('Descargar', 'galeria-multimedia-pro'); ?>" data-tip="<?php esc_attr_e('Descargar archivo', 'galeria-multimedia-pro'); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M7 12l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M5 19h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </a>
            </div>

            <?php if ($is_image) : ?>
                <img src="<?php echo esc_url($item['url']); ?>" alt="<?php echo esc_attr($item['name']); ?>" loading="lazy" />
            <?php else : ?>
                <div class="gmp-doc-single" style="background-image:url('<?php echo esc_url($thumb_src); ?>');">
                    <?php if ($doc_viewer) : ?>
                        <iframe class="gmp-doc-iframe-thumb" src="<?php echo esc_url($doc_viewer); ?>" loading="lazy" allowfullscreen></iframe>
                    <?php else : ?>
                        <div class="gmp-doc-fallback"><?php echo esc_html(strtoupper($ext)); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Devuelve URL para el visor embebido (iframe) de documentos.
 */
function gmp_document_view_url($url, $ext) {
    $ext = strtolower($ext);
    $office = ['doc','docx','xls','xlsx','ppt','pptx'];
    if ($ext === 'pdf') {
        return $url;
    }
    if (in_array($ext, $office, true)) {
        return 'https://view.officeapps.live.com/op/embed.aspx?src=' . rawurlencode($url);
    }
    return '';
}

/**
 * Devuelve la miniatura a usar (imagen o fallback).
 */
function gmp_resolve_thumb($item, $is_image, $previews, $ext) {
    if ($is_image) {
        return $item['url'];
    }
    foreach ($previews as $p) {
        if (!empty($p)) {
            return $p;
        }
    }
    return gmp_fallback_preview($ext);
}

/**
 * Miniatura de respaldo en SVG base64 (data URI) para documentos sin preview.
 */
function gmp_fallback_preview($ext) {
    $label = strtoupper($ext);
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="450"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#0b1222" offset="0"/><stop stop-color="#0d182f" offset="1"/></linearGradient></defs><rect width="800" height="450" fill="url(#g)"/><text x="50%" y="55%" fill="#e2e8f0" font-family="Arial, sans-serif" font-size="72" font-weight="700" text-anchor="middle">' . $label . '</text></svg>';
    $encoded = base64_encode($svg);
    return 'data:image/svg+xml;base64,' . $encoded;
}
