<?php
/**
 * Handlers AJAX para cargas, eliminación y descargas seguras.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMP_MAX_UPLOAD_BYTES')) {
    define('CMP_MAX_UPLOAD_BYTES', 1073741824); // 1 GB
}

/**
 * Agrega un adjunto a la metabox correspondiente evitando duplicados.
 */
function cmp_attach_to_meta($post_id, $attachment_id, $type) {
    $meta_key = $type === 'image' ? CMP_IMAGES_META : CMP_DOCUMENTS_META;
    $current  = (array) get_post_meta($post_id, $meta_key, true);
    $current[] = $attachment_id;
    update_post_meta($post_id, $meta_key, array_values(array_unique(array_map('absint', $current))));
}

/**
 * Procesa un ZIP y devuelve los adjuntos creados.
 */
function cmp_process_zip($zip_path, $post_id, $image_mimes, $document_mimes) {
    $zip = new ZipArchive();
    $created = [];

    if ($zip->open($zip_path) !== true) {
        return $created;
    }

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        if (!$stat || substr($stat['name'], -1) === '/') {
            continue;
        }

        $fileinfo = pathinfo($stat['name']);
        $ext      = $fileinfo['extension'] ?? '';
        $filename = $fileinfo['basename'] ?? 'archivo';
        $mime     = wp_check_filetype($filename)['type'];

        $target_type = in_array($mime, $image_mimes, true) ? 'image' : (in_array($mime, $document_mimes, true) ? 'document' : '');
        if (!$target_type) {
            continue;
        }

        $stream = $zip->getStream($stat['name']);
        if (!$stream) {
            continue;
        }

        $temp_file = wp_tempnam($filename);
        if (!$temp_file) {
            fclose($stream);
            continue;
        }

        $dest = fopen($temp_file, 'w');
        if (!$dest) {
            fclose($stream);
            unlink($temp_file);
            continue;
        }

        stream_copy_to_stream($stream, $dest);
        fclose($stream);
        fclose($dest);

        if (filesize($temp_file) > CMP_MAX_UPLOAD_BYTES) {
            unlink($temp_file);
            continue;
        }

        $sideload = [
            'name'     => sanitize_file_name($filename),
            'type'     => $mime,
            'tmp_name' => $temp_file,
            'size'     => filesize($temp_file),
            'error'    => 0,
        ];

        $attach_id = media_handle_sideload($sideload, $post_id, '', ['test_form' => false]);
        if (is_wp_error($attach_id)) {
            unlink($temp_file);
            continue;
        }

        cmp_attach_to_meta($post_id, $attach_id, $target_type);

        $created[] = [
            'attachment_id' => $attach_id,
            'preview'       => $target_type === 'image' ? wp_get_attachment_image_url($attach_id, 'medium') : wp_mime_type_icon($attach_id),
            'title'         => get_the_title($attach_id),
            'type'          => $target_type,
        ];
    }

    $zip->close();

    return $created;
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

    $image_mimes    = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $document_mimes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip',
        'application/x-zip-compressed',
        'application/x-rar-compressed',
    ];

    $allowed_mimes = $filetype === 'image' ? $image_mimes : $document_mimes;

    if (!empty($_FILES['file']['size']) && intval($_FILES['file']['size']) > CMP_MAX_UPLOAD_BYTES) {
        wp_send_json_error(['message' => __('El archivo excede el límite de 1GB', 'carpetas-multimedia-pro')]);
    }

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

    $is_zip = in_array($file['type'], ['application/zip', 'application/x-zip-compressed'], true);

    if ($is_zip) {
        $items = cmp_process_zip($file['file'], $post_id, $image_mimes, $document_mimes);
        @unlink($file['file']);

        if (empty($items)) {
            wp_send_json_error(['message' => __('No se pudo extraer un contenido válido del ZIP', 'carpetas-multimedia-pro')]);
        }

        wp_send_json_success([
            'items' => $items,
        ]);
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

    cmp_attach_to_meta($post_id, $attach_id, $filetype === 'image' ? 'image' : 'document');

    $preview = $filetype === 'image' ? wp_get_attachment_image_url($attach_id, 'medium') : wp_mime_type_icon($attach_id);

    wp_send_json_success([
        'attachment_id' => $attach_id,
        'preview'       => $preview,
        'title'         => get_the_title($attach_id),
        'type'          => $filetype === 'image' ? 'image' : 'document',
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
