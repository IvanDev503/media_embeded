<?php
/**
 * Plugin Name: Carpetas Multimedia Pro
 * Description: Plugin para crear carpetas con grupos de imágenes y documentos con vistas frontend responsivas (grid/slider), carga masiva y paginación AJAX.
 * Version: 1.0
 * Author: Ivan Rauda
 */

// Cargar archivos principales
define('CMP_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once CMP_PLUGIN_DIR . 'includes/cpt-carpeta.php';
require_once CMP_PLUGIN_DIR . 'includes/metaboxes.php';
require_once CMP_PLUGIN_DIR . 'includes/ajax-handler.php';
require_once CMP_PLUGIN_DIR . 'includes/shortcode.php';

function cmp_enqueue_assets() {
    wp_enqueue_style('cmp-style', plugin_dir_url(__FILE__) . 'assets/style.css');
    wp_enqueue_script('cmp-script', plugin_dir_url(__FILE__) . 'assets/script.js', ['jquery'], null, true);
    wp_localize_script('cmp-script', 'cmp_ajax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cmp_nonce')
    ]);
    wp_enqueue_style('glightbox', 'https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css');
    wp_enqueue_script('glightbox', 'https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js', [], null, true);
    wp_enqueue_script('swiper', 'https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js', [], null, true);
    wp_enqueue_style('swiper', 'https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css');
}
add_action('wp_enqueue_scripts', 'cmp_enqueue_assets');
