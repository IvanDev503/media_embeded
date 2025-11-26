<?php
/**
 * Plugin Name: Carpetas Multimedia Pro
 * Description: Plugin para crear carpetas con grupos de imágenes y documentos con vistas frontend responsivas (grid/slider), carga masiva y paginación AJAX.
 * Version: 1.0.0
 * Author: Ivan Rauda
 * Text Domain: carpetas-multimedia-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

// Rutas y constantes del plugin.
define('CMP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CMP_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once CMP_PLUGIN_DIR . 'includes/cpt-carpeta.php';
require_once CMP_PLUGIN_DIR . 'includes/metaboxes.php';
require_once CMP_PLUGIN_DIR . 'includes/ajax-handler.php';
require_once CMP_PLUGIN_DIR . 'includes/shortcode.php';

/**
 * Registra scripts y estilos frontend.
 */
function cmp_enqueue_assets() {
    $post = get_post();
    $has_shortcode = $post ? (has_shortcode($post->post_content, 'cmp_carpeta_grid') || has_shortcode($post->post_content, 'cmp_carpeta_slider')) : false;
    if (!is_singular() && !$has_shortcode) {
        return;
    }

    wp_enqueue_style('cmp-style', CMP_PLUGIN_URL . 'assets/style.css', [], '1.0.0');
    wp_enqueue_script('cmp-script', CMP_PLUGIN_URL . 'assets/script.js', ['jquery'], '1.0.0', true);
    wp_localize_script('cmp-script', 'cmp_ajax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('cmp_nonce'),
    ]);

    wp_enqueue_style('glightbox', 'https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css', [], '3.3.0');
    wp_enqueue_script('glightbox', 'https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js', [], '3.3.0', true);
    wp_enqueue_style('swiper', 'https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css', [], '11.0.0');
    wp_enqueue_script('swiper', 'https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js', [], '11.0.0', true);
}
add_action('wp_enqueue_scripts', 'cmp_enqueue_assets');

/**
 * Activa el plugin y registra CPT.
 */
function cmp_activate_plugin() {
    cmp_register_carpeta_cpt();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'cmp_activate_plugin');

/**
 * Limpia reglas al desactivar.
 */
function cmp_deactivate_plugin() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'cmp_deactivate_plugin');
