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

/**
 * Agrega columna de shortcodes para facilitar su copia.
 */
function cmp_carpeta_columns($columns) {
    $columns['cmp_shortcodes'] = __('Shortcodes', 'carpetas-multimedia-pro');
    return $columns;
}
add_filter('manage_carpeta_media_posts_columns', 'cmp_carpeta_columns');

/**
 * Rellena la columna con los dos shortcodes disponibles.
 */
function cmp_carpeta_columns_content($column, $post_id) {
    if ($column !== 'cmp_shortcodes') {
        return;
    }

    $grid   = sprintf('[cmp_carpeta_grid id="%d"]', $post_id);
    $slider = sprintf('[cmp_carpeta_slider id="%d"]', $post_id);

    echo '<code>' . esc_html($grid) . '</code><br />';
    echo '<code>' . esc_html($slider) . '</code>';
}
add_action('manage_carpeta_media_posts_custom_column', 'cmp_carpeta_columns_content', 10, 2);
