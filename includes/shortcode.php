<?php
/**
 * Shortcodes y helpers de rendering.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Genera HTML para documentos.
 */
function cmp_render_documents($doc_ids) {
    $html = '<div class="cmp-documents">';
    foreach ($doc_ids as $doc_id) {
        $mime     = get_post_mime_type($doc_id);
        $url      = wp_get_attachment_url($doc_id);
        $icon     = wp_mime_type_icon($doc_id);
        $download = wp_nonce_url(admin_url('admin-ajax.php?action=cmp_secure_download&attachment_id=' . $doc_id), 'cmp_download_' . $doc_id);
        $viewer   = strpos($mime, 'pdf') !== false ? esc_url($url) : 'https://docs.google.com/gview?embedded=1&url=' . rawurlencode($url);
        $html    .= '<div class="cmp-doc-card">';
        $html    .= '<div class="cmp-doc-icon" style="background-image:url(' . esc_url($icon) . ');"></div>';
        $html    .= '<div class="cmp-doc-body">';
        $html    .= '<h4>' . esc_html(get_the_title($doc_id)) . '</h4>';
        $html    .= '<div class="cmp-doc-actions">';
        $html    .= '<a class="cmp-btn" href="' . esc_url($viewer) . '" target="_blank" rel="noopener">' . esc_html__('Ver', 'carpetas-multimedia-pro') . '</a>';
        $html    .= '<a class="cmp-btn cmp-secondary" href="' . esc_url($download) . '">' . esc_html__('Descargar', 'carpetas-multimedia-pro') . '</a>';
        $html    .= '</div></div></div>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Shortcode para vista en grid.
 */
function cmp_carpeta_grid_shortcode($atts) {
    $atts = shortcode_atts([
        'id'      => 0,
        'perpage' => 20,
    ], $atts, 'cmp_carpeta_grid');

    $post_id = absint($atts['id']);
    if (!$post_id) {
        return '';
    }

    cmp_enqueue_frontend_assets();

    $images    = (array) get_post_meta($post_id, CMP_IMAGES_META, true);
    $documents = (array) get_post_meta($post_id, CMP_DOCUMENTS_META, true);
    $limit     = absint($atts['perpage']);

    $initial_images = array_slice($images, 0, $limit);
    $remaining      = max(0, count($images) - $limit);

    ob_start();
    ?>
    <div class="cmp-gallery cmp-grid" data-post="<?php echo esc_attr($post_id); ?>" data-type="image" data-limit="<?php echo esc_attr($limit); ?>">
        <div class="cmp-grid-wrapper">
            <?php foreach ($initial_images as $id) : ?>
                <a class="cmp-grid-item" href="<?php echo esc_url(wp_get_attachment_image_url($id, 'large')); ?>" data-gallery="cmp-<?php echo esc_attr($post_id); ?>">
                    <img src="<?php echo esc_url(wp_get_attachment_image_url($id, 'medium_large')); ?>" alt="<?php echo esc_attr(get_the_title($id)); ?>" />
                </a>
            <?php endforeach; ?>
        </div>
        <?php if ($remaining > 0) : ?>
            <button class="cmp-btn cmp-load-more" data-offset="<?php echo esc_attr($limit); ?>"><?php esc_html_e('Cargar más', 'carpetas-multimedia-pro'); ?></button>
        <?php elseif (empty($images)) : ?>
            <p class="cmp-empty"><?php esc_html_e('Esta carpeta aún no tiene imágenes.', 'carpetas-multimedia-pro'); ?></p>
        <?php endif; ?>
    </div>
    <div class="cmp-documents" data-post="<?php echo esc_attr($post_id); ?>" data-limit="<?php echo esc_attr($limit); ?>">
        <?php echo cmp_render_documents(array_slice($documents, 0, $limit)); ?>
    </div>
    <?php if (max(0, count($documents) - $limit) > 0) : ?>
        <button class="cmp-btn cmp-load-more-docs" data-offset="<?php echo esc_attr($limit); ?>" data-post="<?php echo esc_attr($post_id); ?>" data-limit="<?php echo esc_attr($limit); ?>"><?php esc_html_e('Cargar más documentos', 'carpetas-multimedia-pro'); ?></button>
    <?php elseif (empty($documents)) : ?>
        <p class="cmp-empty"><?php esc_html_e('Esta carpeta aún no tiene documentos.', 'carpetas-multimedia-pro'); ?></p>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}
add_shortcode('cmp_carpeta_grid', 'cmp_carpeta_grid_shortcode');

/**
 * Shortcode para vista slider.
 */
function cmp_carpeta_slider_shortcode($atts) {
    $atts = shortcode_atts([
        'id'      => 0,
        'perpage' => 20,
    ], $atts, 'cmp_carpeta_slider');

    $post_id = absint($atts['id']);
    if (!$post_id) {
        return '';
    }

    cmp_enqueue_frontend_assets();

    $images    = (array) get_post_meta($post_id, CMP_IMAGES_META, true);
    $documents = (array) get_post_meta($post_id, CMP_DOCUMENTS_META, true);
    $limit     = absint($atts['perpage']);

    $initial_images = array_slice($images, 0, $limit);
    $remaining      = max(0, count($images) - $limit);

    ob_start();
    ?>
    <div class="cmp-gallery cmp-slider" data-post="<?php echo esc_attr($post_id); ?>" data-type="image" data-limit="<?php echo esc_attr($limit); ?>">
        <div class="swiper cmp-swiper">
            <div class="swiper-wrapper">
                <?php foreach ($initial_images as $id) : ?>
                    <div class="swiper-slide">
                        <a class="cmp-grid-item" href="<?php echo esc_url(wp_get_attachment_image_url($id, 'large')); ?>" data-gallery="cmp-<?php echo esc_attr($post_id); ?>">
                            <img src="<?php echo esc_url(wp_get_attachment_image_url($id, 'large')); ?>" alt="<?php echo esc_attr(get_the_title($id)); ?>" />
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>
        <?php if ($remaining > 0) : ?>
            <button class="cmp-btn cmp-load-more" data-offset="<?php echo esc_attr($limit); ?>"><?php esc_html_e('Cargar más', 'carpetas-multimedia-pro'); ?></button>
        <?php elseif (empty($images)) : ?>
            <p class="cmp-empty"><?php esc_html_e('Esta carpeta aún no tiene imágenes para mostrar en slider.', 'carpetas-multimedia-pro'); ?></p>
        <?php endif; ?>
    </div>
    <div class="cmp-documents" data-post="<?php echo esc_attr($post_id); ?>" data-limit="<?php echo esc_attr($limit); ?>">
        <?php echo cmp_render_documents(array_slice($documents, 0, $limit)); ?>
    </div>
    <?php if (max(0, count($documents) - $limit) > 0) : ?>
        <button class="cmp-btn cmp-load-more-docs" data-offset="<?php echo esc_attr($limit); ?>" data-post="<?php echo esc_attr($post_id); ?>" data-limit="<?php echo esc_attr($limit); ?>"><?php esc_html_e('Cargar más documentos', 'carpetas-multimedia-pro'); ?></button>
    <?php elseif (empty($documents)) : ?>
        <p class="cmp-empty"><?php esc_html_e('Esta carpeta aún no tiene documentos.', 'carpetas-multimedia-pro'); ?></p>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}
add_shortcode('cmp_carpeta_slider', 'cmp_carpeta_slider_shortcode');
