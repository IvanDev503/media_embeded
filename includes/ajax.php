<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sanitiza el nombre de carpeta.
 */
function gmp_sanitize_folder($folder) {
    return sanitize_title(str_replace(['/', '\\'], '-', $folder));
}

/**
 * Garantiza que la carpeta base exista e incluya un index de bloqueo.
 */
function gmp_prepare_base_dir() {
    $base = gmp_get_base_dir();
    if (!file_exists($base)) {
        wp_mkdir_p($base);
    }
    if (is_dir($base)) {
        $index = trailingslashit($base) . 'index.html';
        if (!file_exists($index)) {
            file_put_contents($index, '');
        }
    }
    return is_dir($base);
}

/**
 * Garantiza estructura de carpeta con subdirectorios.
 */
function gmp_prepare_folder_structure($folder) {
    if (empty($folder)) {
        return false;
    }
    if (!gmp_prepare_base_dir()) {
        return false;
    }
    $base = gmp_get_base_dir();
    $path = trailingslashit($base) . $folder;
    $img  = trailingslashit($path) . 'imagenes';
    $doc  = trailingslashit($path) . 'documentos';
    if (!file_exists($img)) {
        wp_mkdir_p($img);
    }
    if (!file_exists($doc)) {
        wp_mkdir_p($doc);
    }
    return is_dir($img) && is_dir($doc);
}

/**
 * Crea una nueva carpeta con subdirectorios.
 */
function gmp_ajax_create_folder() {
    check_ajax_referer('gmp_admin_nonce', 'nonce');
    if (!current_user_can('upload_files')) {
        wp_send_json_error(__('Sin permisos.', 'galeria-multimedia-pro'), 403);
    }

    $folder = isset($_POST['folder_name']) ? gmp_sanitize_folder(wp_unslash($_POST['folder_name'])) : '';
    if (empty($folder)) {
        wp_send_json_error(__('Nombre de carpeta inválido.', 'galeria-multimedia-pro'));
    }

    gmp_prepare_folder_structure($folder);

    wp_send_json_success([
        'folder' => $folder,
    ]);
}
add_action('wp_ajax_gmp_create_folder', 'gmp_ajax_create_folder');

/**
 * Listar archivos de una carpeta para el admin.
 */
function gmp_ajax_list_files() {
    check_ajax_referer('gmp_admin_nonce', 'nonce');
    if (!current_user_can('upload_files')) {
        wp_send_json_error(__('Sin permisos.', 'galeria-multimedia-pro'), 403);
    }

    $folder = isset($_GET['folder']) ? gmp_sanitize_folder(wp_unslash($_GET['folder'])) : '';
    $data   = gmp_fetch_files($folder);
    wp_send_json_success($data);
}
add_action('wp_ajax_gmp_list_files', 'gmp_ajax_list_files');

/**
 * Maneja subidas (incluye ZIP).
 */
function gmp_ajax_upload() {
    check_ajax_referer('gmp_admin_nonce', 'nonce');
    if (!current_user_can('upload_files')) {
        wp_send_json_error(__('Sin permisos.', 'galeria-multimedia-pro'), 403);
    }

    $folder = isset($_POST['folder']) ? gmp_sanitize_folder(wp_unslash($_POST['folder'])) : '';
    $type   = isset($_POST['file_type']) ? sanitize_key($_POST['file_type']) : '';

    if (empty($folder) || empty($type) || !isset($_FILES['file'])) {
        wp_send_json_error(__('Datos incompletos.', 'galeria-multimedia-pro'));
    }

    $allowed_images = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $allowed_docs   = ['application/pdf', 'application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/msword', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'];
    $max_size       = 1024 * 1024 * 1024; // 1GB por archivo.

    $file     = $_FILES['file'];
    $tmp_name = $file['tmp_name'];
    $filetype = wp_check_filetype_and_ext($tmp_name, $file['name']);
    $mime     = $filetype['type'] ?: $file['type'];

    if (!gmp_prepare_folder_structure($folder)) {
        wp_send_json_error(__('No se pudo preparar la carpeta en uploads/galerias.', 'galeria-multimedia-pro'));
    }

    if ($file['size'] > $max_size) {
        wp_send_json_error(__('Archivo excede 1GB.', 'galeria-multimedia-pro'));
    }

    if ($mime === 'application/zip') {
        $result = gmp_handle_zip($tmp_name, $folder, $type, $allowed_images, $allowed_docs);
        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        }
        wp_send_json_success($result);
    }

    $is_image = in_array($mime, $allowed_images, true);
    $is_doc   = in_array($mime, $allowed_docs, true);

    if ($type === 'imagenes' && !$is_image) {
        wp_send_json_error(__('Formato de imagen no permitido.', 'galeria-multimedia-pro'));
    }
    if ($type === 'documentos' && !$is_doc && !$is_image) {
        wp_send_json_error(__('Formato de documento no permitido.', 'galeria-multimedia-pro'));
    }

    $target_dir = gmp_target_dir($folder, $type === 'imagenes' && $is_image ? 'imagenes' : 'documentos');
    if (!$target_dir) {
        wp_send_json_error(__('Carpeta no encontrada.', 'galeria-multimedia-pro'));
    }

    $filename = wp_unique_filename($target_dir, sanitize_file_name($file['name']));
    $new_path = trailingslashit($target_dir) . $filename;

    $move = move_uploaded_file($tmp_name, $new_path);
    if (!$move) {
        wp_send_json_error(__('Error al mover archivo.', 'galeria-multimedia-pro'));
    }

    wp_send_json_success([
        'file' => gmp_public_url($new_path),
        'name' => $filename,
        'type' => $type,
    ]);
}
add_action('wp_ajax_gmp_upload', 'gmp_ajax_upload');

/**
 * Elimina archivo.
 */
function gmp_ajax_delete_file() {
    check_ajax_referer('gmp_admin_nonce', 'nonce');
    if (!current_user_can('upload_files')) {
        wp_send_json_error(__('Sin permisos.', 'galeria-multimedia-pro'), 403);
    }
    $folder = isset($_POST['folder']) ? gmp_sanitize_folder(wp_unslash($_POST['folder'])) : '';
    $file   = isset($_POST['file']) ? wp_unslash($_POST['file']) : '';

    $base = gmp_get_base_dir();
    $full = realpath(trailingslashit($base) . $folder . '/' . $file);

    if (!$full || strpos($full, realpath($base)) !== 0 || !file_exists($full)) {
        wp_send_json_error(__('Archivo inválido.', 'galeria-multimedia-pro'));
    }

    unlink($full);
    wp_send_json_success();
}
add_action('wp_ajax_gmp_delete_file', 'gmp_ajax_delete_file');

/**
 * Carga paginada para frontend.
 */
function gmp_ajax_frontend_page() {
    check_ajax_referer('gmp_front_nonce', 'nonce');
    $folder = isset($_GET['folder']) ? gmp_sanitize_folder(wp_unslash($_GET['folder'])) : '';
    $page   = isset($_GET['page']) ? absint($_GET['page']) : 1;
    $per    = isset($_GET['per']) ? absint($_GET['per']) : 20;
    $view   = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'grid';

    $files = gmp_fetch_files($folder);
    $all   = array_merge($files['imagenes'], $files['documentos']);
    $paged = array_slice($all, ($page - 1) * $per, $per);

    ob_start();
    foreach ($paged as $item) {
        gmp_render_item($item, $view);
    }
    $html = ob_get_clean();

    wp_send_json_success([
        'html' => $html,
        'has_more' => count($all) > $page * $per,
    ]);
}
add_action('wp_ajax_gmp_frontend_page', 'gmp_ajax_frontend_page');
add_action('wp_ajax_nopriv_gmp_frontend_page', 'gmp_ajax_frontend_page');

/**
 * Genera listado de archivos.
 */
function gmp_fetch_files($folder) {
    if (!gmp_prepare_base_dir()) {
        return [
            'imagenes'   => [],
            'documentos' => [],
        ];
    }
    $base = gmp_get_base_dir();
    $path = trailingslashit($base) . $folder;
    $out  = [
        'imagenes'   => [],
        'documentos' => [],
    ];

    if (!is_dir($path)) {
        return $out;
    }

    $pairs = [
        'imagenes'   => ['dir' => 'imagenes', 'types' => ['jpg','jpeg','png','webp','gif']],
        'documentos' => ['dir' => 'documentos', 'types' => []],
    ];

    foreach ($pairs as $key => $meta) {
        $dir = trailingslashit($path) . $meta['dir'];
        if (!is_dir($dir)) {
            continue;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $url = gmp_public_url(trailingslashit($dir) . $file);
            $out[$key][] = [
                'name' => $file,
                'ext'  => $ext,
                'path' => $folder . '/' . $meta['dir'] . '/' . $file,
                'url'  => $url,
            ];
        }
    }

    usort($out['imagenes'], 'gmp_sort_by_name');
    usort($out['documentos'], 'gmp_sort_by_name');

    return $out;
}

function gmp_sort_by_name($a, $b) {
    return strcmp($a['name'], $b['name']);
}

/**
 * Devuelve directorio destino seguro.
 */
function gmp_target_dir($folder, $type) {
    if (!gmp_prepare_folder_structure($folder)) {
        return false;
    }
    $base = gmp_get_base_dir();
    $path = trailingslashit($base) . $folder . '/' . $type;
    if (!is_dir($path)) {
        return false;
    }
    return $path;
}

/**
 * URL pública desde ruta absoluta.
 */
function gmp_public_url($path) {
    $path = wp_normalize_path($path);
    $base = wp_normalize_path(gmp_get_base_dir());
    $url  = gmp_get_base_url();
    return str_replace($base, $url, $path);
}

/**
 * Maneja ZIP descomprimiendo según tipo.
 */
function gmp_handle_zip($tmp_name, $folder, $type, $allowed_images, $allowed_docs) {
    if (!class_exists('ZipArchive')) {
        return ['error' => __('ZIP no soportado en el servidor.', 'galeria-multimedia-pro')];
    }
    $zip = new ZipArchive();
    if ($zip->open($tmp_name) !== true) {
        return ['error' => __('No se pudo abrir el ZIP.', 'galeria-multimedia-pro')];
    }
    $added = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (substr($name, -1) === '/') {
            continue;
        }
        $stream = $zip->getStream($name);
        if (!$stream) {
            continue;
        }

        $mime_info = wp_check_filetype($name);
        $mime = $mime_info['type'];
        if (empty($mime)) {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $mimes = wp_get_mime_types();
            $mime = isset($mimes[$ext]) ? $mimes[$ext] : '';
        }
        $is_img = in_array($mime, $allowed_images, true);
        $dest_type = $is_img ? 'imagenes' : 'documentos';
        if ($type === 'imagenes' && !$is_img) {
            continue;
        }
        if ($type === 'documentos' && !$is_img && !in_array($mime, $allowed_docs, true)) {
            continue;
        }

        $dir = gmp_target_dir($folder, $dest_type);
        if (!$dir) {
            continue;
        }
        $filename = wp_unique_filename($dir, sanitize_file_name(basename($name)));
        $dest = trailingslashit($dir) . $filename;
        $temp_file = wp_tempnam($filename);
        $dest_handle = fopen($temp_file, 'w');
        if ($dest_handle) {
            stream_copy_to_stream($stream, $dest_handle);
            fclose($dest_handle);
            rename($temp_file, $dest);
        } else {
            fclose($stream);
            unlink($temp_file);
            continue;
        }
        fclose($stream);
        $added[] = [
            'file' => gmp_public_url($dest),
            'name' => $filename,
            'type' => $dest_type,
            'type_label' => $is_img ? 'imagen' : 'documento',
        ];
    }
    $zip->close();
    return ['files' => $added];
}
