<?php
/**
 * Plugin Name: Galeria Multimedia Pro
 * Description: Gestiona carpetas fisicas en /uploads/galerias/ con subidas masivas y shortcodes responsivos (grid/slider).
 * Version: 1.1.0
 * Author: IvanDev
 * Text Domain: galeria-multimedia-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constantes de ruta y URL.
define('GMP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GMP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Directorio base para las carpetas fisicas.
function gmp_get_base_dir() {
    $uploads = wp_upload_dir();
    return trailingslashit($uploads['basedir']) . 'galerias/';
}

function gmp_get_base_url() {
    $uploads = wp_upload_dir();
    return trailingslashit($uploads['baseurl']) . 'galerias/';
}

// Asegura la carpeta base en activacion.
function gmp_activate() {
    $base = gmp_get_base_dir();
    if (!file_exists($base)) {
        wp_mkdir_p($base);
    }
    // Evita listados directos.
    if (is_dir($base)) {
        $index = trailingslashit($base) . 'index.html';
        if (!file_exists($index)) {
            file_put_contents($index, '');
        }
    }
}
register_activation_hook(__FILE__, 'gmp_activate');

// Encola estilos/scripts compartidos para frontend.
function gmp_register_front_assets() {
    wp_register_style('gmp-style', GMP_PLUGIN_URL . 'assets/style.css', [], '1.0.0');
    wp_register_script('gmp-script', GMP_PLUGIN_URL . 'assets/script.js', ['jquery'], '1.0.0', true);

    wp_register_style('gmp-fancybox', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css', [], '5.0.36');
    wp_register_script('gmp-fancybox', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js', [], '5.0.36', true);
    wp_register_style('gmp-swiper', 'https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css', [], '11.0.0');
    wp_register_script('gmp-swiper', 'https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js', [], '11.0.0', true);
}
add_action('wp_enqueue_scripts', 'gmp_register_front_assets');

// Admin scripts.
function gmp_admin_assets($hook) {
    if ($hook !== 'toplevel_page_gmp-galerias') {
        return;
    }
    wp_enqueue_style('gmp-style', GMP_PLUGIN_URL . 'assets/style.css', [], '1.0.0');
    wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', [], '11.12.0');
    wp_enqueue_script('dropzone', 'https://cdn.jsdelivr.net/npm/dropzone@6.0.0-beta.2/dist/dropzone-min.js', [], '6.0.0-beta.2', true);
    wp_enqueue_style('dropzone', 'https://cdn.jsdelivr.net/npm/dropzone@6.0.0-beta.2/dist/dropzone.css', [], '6.0.0-beta.2');
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js', [], '11.12.0', true);
    wp_enqueue_script('gmp-admin', GMP_PLUGIN_URL . 'assets/admin.js', ['jquery', 'dropzone', 'sweetalert2'], '1.0.1', true);

    wp_localize_script('gmp-admin', 'gmpAdmin', [
        'ajax'   => admin_url('admin-ajax.php'),
        'nonce'  => wp_create_nonce('gmp_admin_nonce'),
        'base'   => gmp_get_base_url(),
        'strings' => [
            'creating' => __('Creando carpeta...', 'galeria-multimedia-pro'),
            'uploading' => __('Subiendo archivos...', 'galeria-multimedia-pro'),
            'deleteConfirm' => __('Eliminar este archivo?', 'galeria-multimedia-pro'),
            'deleted' => __('Archivo eliminado.', 'galeria-multimedia-pro'),
            'noFolder' => __('Primero crea o selecciona una carpeta.', 'galeria-multimedia-pro'),
            'uploadOk' => __('Archivos subidos correctamente.', 'galeria-multimedia-pro'),
            'uploadError' => __('No se pudo subir el archivo. Revisa el error y vuelve a intentar.', 'galeria-multimedia-pro'),
            'removeFile' => __('Quitar', 'galeria-multimedia-pro'),
            'server403' => __('403: el servidor rechazo la peticion. Refresca la pagina o revisa tu inicio de sesion.', 'galeria-multimedia-pro'),
            'folderCreated' => __('Carpeta creada.', 'galeria-multimedia-pro'),
        ],
    ]);
}
add_action('admin_enqueue_scripts', 'gmp_admin_assets');

// Pagina de administracion.
function gmp_register_menu() {
    add_menu_page(
        __('Galeria Multimedia', 'galeria-multimedia-pro'),
        __('Galeria Multimedia', 'galeria-multimedia-pro'),
        'upload_files',
        'gmp-galerias',
        'gmp_render_admin_page',
        'dashicons-images-alt2',
        25
    );
}
add_action('admin_menu', 'gmp_register_menu');

require_once GMP_PLUGIN_DIR . 'admin/uploader.php';
require_once GMP_PLUGIN_DIR . 'frontend/galeria-shortcode.php';
require_once GMP_PLUGIN_DIR . 'includes/ajax.php';

// Asegura carpeta base tambien en cada carga.
add_action('init', 'gmp_prepare_base_dir');
