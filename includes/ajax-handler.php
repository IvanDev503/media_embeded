<?php
/**
 * Handlers AJAX para cargas, eliminación y descargas seguras.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Carga de archivos vía Dropzone.
 */
function cmp_upload_file() {
    check_ajax_referer('cmp_admin_nonce', 'nonce');

    $post_id  = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $filetype = sanitize_text_field($_POST['file_type'] ?? '');

    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(['message' => __('Permisos insuficientes', 'carpetas-multimedia-pro')], 403);
    }

    if (!isset($_FILES['file'])) {
        wp_send_json_error(['message' => __('No se envió archivo', 'carpetas-multimedia-pro')]);
    }

    $allowed_mimes = $filetype === 'image'
        ? ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
        : ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/zip', 'application/x-zip-compressed', 'application/x-rar-compressed'];

    if (!in_array($_FILES['file']['type'], $allowed_mimes, true)) {
        wp_send_json_error(['message' => __('Tipo de archivo no permitido', 'carpetas-multimedia-pro')]);
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $overrides = ['test_form' => false, 'mimes' => $allowed_mimes];
    $file      = wp_handle_upload($_FILES['file'], $overrides);

    if (isset($file['error'])) {
        wp_send_json_error(['message' => $file['error']]);
    }

    $attachment = [
        'post_mime_type' => $file['type'],
        'post_title'     => sanitize_file_name(pathinfo($file['file'], PATHINFO_FILENAME)),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ];

    $attach_id = wp_insert_attachment($attachment, $file['file'], $post_id);
    if (!$attach_id) {
        wp_send_json_error(['message' => __('No se pudo registrar el archivo', 'carpetas-multimedia-pro')]);
    }

    wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $file['file']));

    $meta_key = $filetype === 'image' ? CMP_IMAGES_META : CMP_DOCUMENTS_META;
    $current  = (array) get_post_meta($post_id, $meta_key, true);
    $current[] = $attach_id;
    update_post_meta($post_id, $meta_key, array_values(array_unique(array_map('absint', $current))));

    $preview = $filetype === 'image' ? wp_get_attachment_image_url($attach_id, 'medium') : wp_mime_type_icon($attach_id);

    wp_send_json_success([
        'attachment_id' => $attach_id,
        'preview'       => $preview,
        'title'         => get_the_title($attach_id),
    ]);
}
add_action('wp_ajax_cmp_upload_file', 'cmp_upload_file');

/**
 * Elimina un archivo del metadato de la carpeta.
 */
function cmp_remove_file() {
    check_ajax_referer('cmp_admin_nonce', 'nonce');

    $attachment_id = absint($_POST['attachment_id'] ?? 0);
    $filetype      = sanitize_text_field($_POST['file_type'] ?? '');
    $post_id       = absint($_POST['post_id'] ?? 0);

    if (!$attachment_id || !$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(['message' => __('Operación no permitida', 'carpetas-multimedia-pro')], 403);
    }

    $meta_key = $filetype === 'image' ? CMP_IMAGES_META : CMP_DOCUMENTS_META;
    $current  = (array) get_post_meta($post_id, $meta_key, true);
    $filtered = array_filter($current, fn($id) => absint($id) !== $attachment_id);
    update_post_meta($post_id, $meta_key, $filtered);

    wp_delete_attachment($attachment_id, true);

    wp_send_json_success(['message' => __('Archivo eliminado', 'carpetas-multimedia-pro')]);
}
add_action('wp_ajax_cmp_remove_file', 'cmp_remove_file');

/**
 * Carga paginada de adjuntos en frontend.
 */
function cmp_load_media_ajax() {
    check_ajax_referer('cmp_nonce', 'nonce');

    $post_id = absint($_GET['post_id'] ?? 0);
    $type    = sanitize_text_field($_GET['file_type'] ?? 'image');
    $offset  = absint($_GET['offset'] ?? 0);
    $limit   = absint($_GET['limit'] ?? 20);

    $meta_key = $type === 'document' ? CMP_DOCUMENTS_META : CMP_IMAGES_META;
    $items    = (array) get_post_meta($post_id, $meta_key, true);
    $slice    = array_slice($items, $offset, $limit);

    $payload = [];
    foreach ($slice as $id) {
        $payload[] = [
            'id'       => $id,
            'title'    => get_the_title($id),
            'url'      => wp_get_attachment_url($id),
            'preview'  => $type === 'document' ? wp_mime_type_icon($id) : wp_get_attachment_image_url($id, 'large'),
            'mime'     => get_post_mime_type($id),
            'download' => wp_nonce_url(admin_url('admin-ajax.php?action=cmp_secure_download&attachment_id=' . $id), 'cmp_download_' . $id),
        ];
    }

    wp_send_json_success([
        'items' => $payload,
        'remaining' => max(0, count($items) - ($offset + $limit)),
    ]);
}
add_action('wp_ajax_cmp_load_media', 'cmp_load_media_ajax');
add_action('wp_ajax_nopriv_cmp_load_media', 'cmp_load_media_ajax');

/**
 * Maneja descargas seguras.
 */
function cmp_secure_download() {
    $attachment_id = absint($_GET['attachment_id'] ?? 0);
    if (!$attachment_id || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'cmp_download_' . $attachment_id)) {
        wp_die(__('Descarga no permitida', 'carpetas-multimedia-pro'));
    }

    $file = get_attached_file($attachment_id);
    if (!$file || !file_exists($file)) {
        wp_die(__('Archivo no encontrado', 'carpetas-multimedia-pro'));
    }

    header('Content-Description: File Transfer');
    header('Content-Type: ' . mime_content_type($file));
    header('Content-Disposition: attachment; filename=' . basename($file));
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}
add_action('wp_ajax_cmp_secure_download', 'cmp_secure_download');
add_action('wp_ajax_nopriv_cmp_secure_download', 'cmp_secure_download');
