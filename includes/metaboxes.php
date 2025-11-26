<?php
/**
 * Metaboxes para gestionar imágenes y documentos en carpetas.
 */

if (!defined('ABSPATH')) {
    exit;
}

const CMP_IMAGES_META    = '_cmp_images';
const CMP_DOCUMENTS_META = '_cmp_documents';

/**
 * Registra metaboxes para el CPT.
 */
function cmp_register_metaboxes() {
    add_meta_box(
        'cmp_media_metabox',
        __('Archivos de la carpeta', 'carpetas-multimedia-pro'),
        'cmp_render_metabox',
        'carpeta_media',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'cmp_register_metaboxes');

/**
 * Encola scripts de Dropzone y el JS interno del admin.
 */
function cmp_admin_assets($hook) {
    global $post;
    if ($hook !== 'post-new.php' && $hook !== 'post.php') {
        return;
    }
    if (!isset($post) || $post->post_type !== 'carpeta_media') {
        return;
    }

    wp_enqueue_style('cmp-style', CMP_PLUGIN_URL . 'assets/style.css', [], '1.0.0');
    wp_enqueue_script('dropzone', 'https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.js', [], '5.9.3', true);
    wp_enqueue_style('dropzone', 'https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css', [], '5.9.3');
    wp_enqueue_script('cmp-admin', CMP_PLUGIN_URL . 'assets/admin.js', ['jquery', 'dropzone'], '1.0.0', true);
    wp_localize_script('cmp-admin', 'cmp_admin_ajax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('cmp_admin_nonce'),
    ]);
}
add_action('admin_enqueue_scripts', 'cmp_admin_assets');

/**
 * Renderiza los campos de carga.
 */
function cmp_render_metabox($post) {
    $images    = (array) get_post_meta($post->ID, CMP_IMAGES_META, true);
    $documents = (array) get_post_meta($post->ID, CMP_DOCUMENTS_META, true);
    wp_nonce_field('cmp_metabox_nonce', 'cmp_metabox_nonce_field');
    ?>
    <div class="cmp-metabox-wrapper">
        <div class="cmp-dropzone" id="cmp-dropzone-images" data-type="image" data-post="<?php echo esc_attr($post->ID); ?>">
            <h4><?php esc_html_e('Arrastra tus imágenes aquí', 'carpetas-multimedia-pro'); ?></h4>
            <p><?php esc_html_e('Formatos permitidos: JPG, PNG, GIF, WebP.', 'carpetas-multimedia-pro'); ?></p>
        </div>

        <div class="cmp-dropzone" id="cmp-dropzone-documents" data-type="document" data-post="<?php echo esc_attr($post->ID); ?>">
            <h4><?php esc_html_e('Arrastra tus documentos aquí', 'carpetas-multimedia-pro'); ?></h4>
            <p><?php esc_html_e('Formatos permitidos: PDF, DOCX, XLSX, ZIP, PPTX.', 'carpetas-multimedia-pro'); ?></p>
        </div>

        <div class="cmp-file-list">
            <h4><?php esc_html_e('Imágenes cargadas', 'carpetas-multimedia-pro'); ?></h4>
            <div class="cmp-file-grid">
                <?php foreach ($images as $image_id) :
                    $thumb = wp_get_attachment_image_src($image_id, 'thumbnail');
                    ?>
                    <div class="cmp-file-card" data-attachment="<?php echo esc_attr($image_id); ?>">
                        <div class="cmp-thumb" style="background-image:url('<?php echo esc_url($thumb[0] ?? ''); ?>')"></div>
                        <button class="button cmp-remove-file" data-id="<?php echo esc_attr($image_id); ?>" data-type="image"><?php esc_html_e('Eliminar', 'carpetas-multimedia-pro'); ?></button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="cmp-file-list">
            <h4><?php esc_html_e('Documentos cargados', 'carpetas-multimedia-pro'); ?></h4>
            <div class="cmp-file-grid">
                <?php foreach ($documents as $doc_id) :
                    $icon = wp_mime_type_icon($doc_id);
                    $title = get_the_title($doc_id);
                    ?>
                    <div class="cmp-file-card" data-attachment="<?php echo esc_attr($doc_id); ?>">
                        <div class="cmp-thumb cmp-doc-thumb" style="background-image:url('<?php echo esc_url($icon); ?>')"></div>
                        <p><?php echo esc_html($title); ?></p>
                        <button class="button cmp-remove-file" data-id="<?php echo esc_attr($doc_id); ?>" data-type="document"><?php esc_html_e('Eliminar', 'carpetas-multimedia-pro'); ?></button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}
