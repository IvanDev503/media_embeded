<?php
/**
 * Registro del Custom Post Type "Carpetas".
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registra el CPT principal.
 */
function cmp_register_carpeta_cpt() {
    $labels = [
        'name'               => __('Carpetas', 'carpetas-multimedia-pro'),
        'singular_name'      => __('Carpeta', 'carpetas-multimedia-pro'),
        'add_new'            => __('Agregar nueva', 'carpetas-multimedia-pro'),
        'add_new_item'       => __('Agregar nueva carpeta', 'carpetas-multimedia-pro'),
        'edit_item'          => __('Editar carpeta', 'carpetas-multimedia-pro'),
        'new_item'           => __('Nueva carpeta', 'carpetas-multimedia-pro'),
        'view_item'          => __('Ver carpeta', 'carpetas-multimedia-pro'),
        'search_items'       => __('Buscar carpetas', 'carpetas-multimedia-pro'),
        'not_found'          => __('No se encontraron carpetas', 'carpetas-multimedia-pro'),
        'not_found_in_trash' => __('No hay carpetas en la papelera', 'carpetas-multimedia-pro'),
        'menu_name'          => __('Carpetas', 'carpetas-multimedia-pro'),
    ];

    $args = [
        'labels'       => $labels,
        'public'       => true,
        'has_archive'  => false,
        'show_in_rest' => true,
        'rewrite'      => ['slug' => 'carpeta_media'],
        'supports'     => ['title', 'editor'],
        'menu_icon'    => 'dashicons-portfolio',
    ];

    register_post_type('carpeta_media', $args);
}
add_action('init', 'cmp_register_carpeta_cpt');
