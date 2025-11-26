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
        return '<p class="gmp-empty">' . esc_html__('Esta carpeta no tiene archivos aún.', 'galeria-multimedia-pro') . '</p>';
    }

    wp_enqueue_style('gmp-style');
    wp_enqueue_style('gmp-glightbox');
    wp_enqueue_style('gmp-swiper');
    wp_enqueue_script('gmp-script');
    wp_enqueue_script('gmp-glightbox');
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
            <button class="gmp-load-more" data-page="1"><?php esc_html_e('Cargar más', 'galeria-multimedia-pro'); ?></button>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renderiza un archivo según vista.
 */
function gmp_render_item($item, $view) {
    $ext = strtolower($item['ext']);
    $is_image = in_array($ext, ['jpg','jpeg','png','webp','gif'], true);
    $classes = $is_image ? 'gmp-item gmp-image' : 'gmp-item gmp-doc';
    $wrapper = $view === 'slider' ? 'swiper-slide' : '';
    ?>
    <div class="<?php echo esc_attr(trim($classes . ' ' . $wrapper)); ?>">
        <?php if ($is_image) : ?>
            <a href="<?php echo esc_url($item['url']); ?>" class="gmp-lightbox" data-gallery="<?php echo esc_attr($item['path']); ?>">
                <img src="<?php echo esc_url($item['url']); ?>" alt="<?php echo esc_attr($item['name']); ?>" loading="lazy" />
            </a>
        <?php else : ?>
            <div class="gmp-doc-icon">📄</div>
            <p class="gmp-doc-name"><?php echo esc_html($item['name']); ?></p>
            <div class="gmp-doc-actions">
                <a class="button" href="<?php echo esc_url($item['url']); ?>" target="_blank" rel="noopener">Ver</a>
                <a class="button button-primary" href="<?php echo esc_url($item['url']); ?>" download>Descargar</a>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
