<?php
/**
 * Bitácora - Modelo de contenido 0.2.0
 *
 * Modelo nuevo, coexistiendo temporalmente con los CPT legacy.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * CPT físico común para todas las secciones configurables.
 */
function bitacora_register_item_cpt() {

    register_post_type(
        'bitacora_item',
        array(
            'labels' => array(
                'name'               => 'Ítems de sección',
                'singular_name'      => 'Ítem de sección',
                'menu_name'          => 'Ítems de sección',
                'name_admin_bar'     => 'Ítem de sección',
                'add_new'            => 'Nuevo ítem',
                'add_new_item'       => 'Agregar ítem',
                'new_item'           => 'Nuevo ítem',
                'edit_item'          => 'Editar ítem',
                'view_item'          => 'Ver ítem',
                'all_items'          => 'Ítems de sección',
                'search_items'       => 'Buscar ítems',
                'not_found'          => 'No se encontraron ítems',
                'not_found_in_trash' => 'No se encontraron ítems en la papelera',
            ),

            'public'        => true,
            'has_archive'   => false,

            /*
             * El permalink definitivo se resolverá después,
             * cuando implementemos las páginas de sección.
             */
            'rewrite'       => false,

            /*
             * thumbnail queda disponible a nivel físico.
             * Cada sección decidirá posteriormente si lo utiliza.
             */
            'supports'      => array(
                'title',
                'editor',
                'author',
                'thumbnail',
            ),

            'show_in_rest'  => false,

            /*
             * Durante el desarrollo lo mostramos dentro del menú
             * principal de Bitácora para facilitar las pruebas.
             */
            'show_in_menu'  => 'edit.php?post_type=bitacora',
        )
    );
}


/**
 * Taxonomía de secciones.
 *
 * Cada bitacora_item deberá pertenecer exactamente a una sección.
 * WordPress permite técnicamente más de una; la aplicación impondrá
 * posteriormente la cardinalidad 1.
 */
function bitacora_register_section_taxonomy() {

    register_taxonomy(
        'bitacora_section',
        array( 'bitacora_item' ),
        array(
            'labels' => array(
                'name'                       => 'Secciones',
                'singular_name'              => 'Sección',
                'search_items'               => 'Buscar secciones',
                'popular_items'              => 'Secciones frecuentes',
                'all_items'                  => 'Todas las secciones',
                'edit_item'                  => 'Editar sección',
                'update_item'                => 'Actualizar sección',
                'add_new_item'               => 'Agregar sección',
                'new_item_name'              => 'Nombre de la nueva sección',
                'separate_items_with_commas' => 'Separar secciones con comas',
                'add_or_remove_items'         => 'Agregar o quitar secciones',
                'choose_from_most_used'       => 'Elegir entre las más usadas',
                'not_found'                  => 'No se encontraron secciones',
                'menu_name'                  => 'Secciones',
            ),

            'hierarchical'      => false,

            /*
             * Es estructura interna de Bitácora:
             * no tendrá archivos públicos propios.
             */
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => false,
            'query_var'         => false,
            'rewrite'           => false,
        )
    );
}


/**
 * Taxonomía de clases de contenido.
 *
 * Puede aplicarse tanto a Notas como a los ítems de sección.
 * Cada contenido admitirá 0..1 clase a nivel de aplicación.
 */
function bitacora_register_class_taxonomy() {

    register_taxonomy(
        'bitacora_class',
        array(
            'bitacora',
            'bitacora_item',
        ),
        array(
            'labels' => array(
                'name'                       => 'Clases del contenido',
                'singular_name'              => 'Clase del contenido',
                'search_items'               => 'Buscar clases',
                'popular_items'              => 'Clases frecuentes',
                'all_items'                  => 'Todas las clases',
                'edit_item'                  => 'Editar clase',
                'update_item'                => 'Actualizar clase',
                'add_new_item'               => 'Agregar clase',
                'new_item_name'              => 'Nombre de la nueva clase',
                'separate_items_with_commas' => 'Separar clases con comas',
                'add_or_remove_items'         => 'Agregar o quitar clases',
                'choose_from_most_used'       => 'Elegir entre las más usadas',
                'not_found'                  => 'No se encontraron clases',
                'menu_name'                  => 'Clases',
            ),

            'hierarchical'      => false,

            /*
             * También es vocabulario interno:
             * no genera archivos públicos.
             */
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => false,
            'query_var'         => false,
            'rewrite'           => false,
        )
    );
}


/**
 * Devuelve las secciones configuradas.
 *
 * Argumentos opcionales:
 * - area:  main | more
 * - state: active | hidden | archived
 *
 * El orden se toma de bitacora_section_order.
 */
function bitacora_get_sections( $args = array() ) {

    $defaults = array(
        'area'  => '',
        'state' => '',
    );

    $args = wp_parse_args( $args, $defaults );

    $meta_query = array();

    if ( '' !== $args['area'] ) {
        $meta_query[] = array(
            'key'     => 'bitacora_section_area',
            'value'   => $args['area'],
            'compare' => '=',
        );
    }

    if ( '' !== $args['state'] ) {
        $meta_query[] = array(
            'key'     => 'bitacora_section_state',
            'value'   => $args['state'],
            'compare' => '=',
        );
    }

    $query_args = array(
        'taxonomy'   => 'bitacora_section',
        'hide_empty' => false,
        'meta_key'   => 'bitacora_section_order',
        'orderby'    => 'meta_value_num',
        'order'      => 'ASC',
    );

    if ( ! empty( $meta_query ) ) {
        $query_args['meta_query'] = $meta_query;
    }

    return get_terms( $query_args );
}


/**
 * Devuelve una sección por su slug.
 */
function bitacora_get_section( $slug ) {

    if ( ! is_string( $slug ) || '' === $slug ) {
        return false;
    }

    return get_term_by(
        'slug',
        sanitize_title( $slug ),
        'bitacora_section'
    );
}


/**
 * Devuelve un metadato de configuración de una sección.
 */
function bitacora_get_section_meta( $section, $key, $default = '' ) {

    if ( is_string( $section ) ) {
        $section = bitacora_get_section( $section );
    }

    if ( ! $section || is_wp_error( $section ) ) {
        return $default;
    }

    $value = get_term_meta( $section->term_id, $key, true );

    return '' === $value ? $default : $value;
}


/**
 * Registro del modelo nuevo.
 */
function bitacora_register_content_model() {

    bitacora_register_item_cpt();
    bitacora_register_section_taxonomy();
    bitacora_register_class_taxonomy();
}

add_action( 'init', 'bitacora_register_content_model' );
