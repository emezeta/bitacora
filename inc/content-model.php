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

            'capability_type' => array(
                'bitacora_content',
                'bitacora_contents',
            ),
            'map_meta_cap'    => true,
            'show_in_rest'    => false,
            /*
             * Se registra como pantalla administrativa independiente.
             * La navegación funcional se realiza desde las secciones,
             * mientras WordPress conserva el registro necesario para
             * resolver correctamente el acceso según capabilities.
             */
            'show_in_menu'  => true,
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
            'meta_box_cb'       => false,

            'capabilities' => array(
                'manage_terms' => 'manage_options',
                'edit_terms'   => 'manage_options',
                'delete_terms' => 'manage_options',
                'assign_terms' => 'edit_bitacora_contents',
            ),
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
            'meta_box_cb'       => false,

            'capabilities' => array(
                'manage_terms' => 'manage_options',
                'edit_terms'   => 'manage_options',
                'delete_terms' => 'manage_options',
                'assign_terms' => 'edit_bitacora_contents',
            ),
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
 * - role:  core | section
 *
 * El orden se toma de bitacora_section_order.
 */
function bitacora_get_sections( $args = array() ) {

    $defaults = array(
        'area'  => '',
        'state' => '',
        'role'  => '',
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

    if ( '' !== $args['role'] ) {
        $meta_query[] = array(
            'key'     => 'bitacora_section_role',
            'value'   => $args['role'],
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
 * Devuelve el único contenedor principal de la instalación.
 *
 * La ausencia de core o la existencia de más de uno son estados
 * inválidos del modelo.
 */
function bitacora_get_core_section() {

    $sections = bitacora_get_sections(
        array(
            'role' => 'core',
        )
    );

    if ( is_wp_error( $sections ) ) {
        return $sections;
    }

    if ( 1 !== count( $sections ) ) {

        return new WP_Error(
            'bitacora_invalid_core_section_count',
            sprintf(
                'Se esperaba exactamente una sección core; se encontraron %d.',
                count( $sections )
            )
        );
    }

    return $sections[0];
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

    if ( ! metadata_exists( 'term', $section->term_id, $key ) ) {
        return $default;
    }

    return get_term_meta( $section->term_id, $key, true );
}


/**
 * Indica si una sección tiene activa una feature.
 *
 * Features soportadas:
 * - file
 * - thumbnail
 * - location
 * - comments
 *
 * $section puede ser un slug o un WP_Term.
 */
function bitacora_section_has_feature( $section, $feature ) {

    $meta_keys = array(
        'file'      => 'bitacora_section_feature_file',
        'thumbnail' => 'bitacora_section_feature_thumbnail',
        'location'  => 'bitacora_section_feature_location',
        'comments'  => 'bitacora_section_feature_comments',
    );

    $feature = sanitize_key( $feature );

    if ( ! isset( $meta_keys[ $feature ] ) ) {
        return false;
    }

    return '1' === (string) bitacora_get_section_meta(
        $section,
        $meta_keys[ $feature ],
        '0'
    );
}


/**
 * Devuelve las clases disponibles.
 *
 * Filtros opcionales:
 * - scope
 * - scope_id
 * - state
 *
 * Los resultados se ordenan por bitacora_class_order.
 */
function bitacora_get_classes( $args = array() ) {

    $defaults = array(
        'scope'    => '',
        'scope_id' => '',
        'state'    => '',
    );

    $args = wp_parse_args( $args, $defaults );

    $meta_query = array();

    if ( '' !== $args['scope'] ) {
        $meta_query[] = array(
            'key'     => 'bitacora_class_scope',
            'value'   => $args['scope'],
            'compare' => '=',
        );
    }

    if ( '' !== $args['scope_id'] ) {
        $meta_query[] = array(
            'key'     => 'bitacora_class_scope_id',
            'value'   => $args['scope_id'],
            'compare' => '=',
        );
    }

    if ( '' !== $args['state'] ) {
        $meta_query[] = array(
            'key'     => 'bitacora_class_state',
            'value'   => $args['state'],
            'compare' => '=',
        );
    }

    $query_args = array(
        'taxonomy'   => 'bitacora_class',
        'hide_empty' => false,
        'meta_key'   => 'bitacora_class_order',
        'orderby'    => 'meta_value_num',
        'order'      => 'ASC',
    );

    if ( ! empty( $meta_query ) ) {
        $query_args['meta_query'] = $meta_query;
    }

    return get_terms( $query_args );
}


/**
 * Devuelve una clase por su slug.
 */
function bitacora_get_class( $slug ) {

    if ( ! is_string( $slug ) || '' === $slug ) {
        return false;
    }

    return get_term_by(
        'slug',
        sanitize_title( $slug ),
        'bitacora_class'
    );
}


/**
 * Devuelve un metadato de configuración de una clase.
 */
function bitacora_get_class_meta( $class, $key, $default = '' ) {

    if ( is_string( $class ) ) {
        $class = bitacora_get_class( $class );
    }

    if ( ! $class || is_wp_error( $class ) ) {
        return $default;
    }

    if ( ! metadata_exists( 'term', $class->term_id, $key ) ) {
        return $default;
    }

    return get_term_meta( $class->term_id, $key, true );
}


/**
 * Devuelve la sección asignada a un ítem.
 *
 * Un bitacora_item debe tener exactamente una sección.
 * Si no existe una asignación válida, devuelve false.
 */
function bitacora_get_item_section( $post_id ) {

    $post_id = (int) $post_id;

    if (
        ! $post_id
        || 'bitacora_item' !== get_post_type( $post_id )
    ) {
        return false;
    }

    $terms = wp_get_object_terms(
        $post_id,
        'bitacora_section'
    );

    if (
        is_wp_error( $terms )
        || 1 !== count( $terms )
    ) {
        return false;
    }

    return $terms[0];
}


/**
 * Asigna exactamente una sección a un ítem.
 *
 * $section puede ser un slug o un WP_Term.
 */
function bitacora_set_item_section( $post_id, $section ) {

    $post_id = (int) $post_id;

    if (
        ! $post_id
        || 'bitacora_item' !== get_post_type( $post_id )
    ) {
        return new WP_Error(
            'bitacora_invalid_item',
            'El contenido no es un bitacora_item válido.'
        );
    }

    if ( is_string( $section ) ) {
        $section = bitacora_get_section( $section );
    }

    if (
        ! $section
        || is_wp_error( $section )
        || ! $section instanceof WP_Term
        || 'bitacora_section' !== $section->taxonomy
    ) {
        return new WP_Error(
            'bitacora_invalid_section',
            'La sección indicada no es válida.'
        );
    }

    /*
     * Si el ítem ya tiene clase, ésta debe pertenecer
     * también a la nueva sección.
     */
    $class = bitacora_get_item_class( $post_id );

    if ( $class ) {

        $scope = bitacora_get_class_meta(
            $class,
            'bitacora_class_scope'
        );

        $scope_id = bitacora_get_class_meta(
            $class,
            'bitacora_class_scope_id'
        );

        if (
            'section' !== $scope
            || $section->slug !== $scope_id
        ) {
            return new WP_Error(
                'bitacora_class_section_mismatch',
                'La clase actual no pertenece a la sección indicada.'
            );
        }
    }

    return wp_set_object_terms(
        $post_id,
        array( (int) $section->term_id ),
        'bitacora_section',
        false
    );
}


/**
 * Devuelve la clase asignada a un ítem.
 *
 * Un bitacora_item puede tener 0..1 clase.
 * Si no tiene clase asignada, devuelve false.
 */
function bitacora_get_item_class( $post_id ) {

    $post_id = (int) $post_id;

    if (
        ! $post_id
        || 'bitacora_item' !== get_post_type( $post_id )
    ) {
        return false;
    }

    $terms = wp_get_object_terms(
        $post_id,
        'bitacora_class'
    );

    if (
        is_wp_error( $terms )
        || 1 !== count( $terms )
    ) {
        return false;
    }

    return $terms[0];
}


/**
 * Asigna 0..1 clase a un ítem.
 *
 * $class puede ser:
 * - un slug;
 * - un WP_Term;
 * - false, null o '' para dejar el ítem sin clasificar.
 */
function bitacora_set_item_class( $post_id, $class = false ) {

    $post_id = (int) $post_id;

    if (
        ! $post_id
        || 'bitacora_item' !== get_post_type( $post_id )
    ) {
        return new WP_Error(
            'bitacora_invalid_item',
            'El contenido no es un bitacora_item válido.'
        );
    }

    if (
        false === $class
        || null === $class
        || '' === $class
    ) {
        return wp_set_object_terms(
            $post_id,
            array(),
            'bitacora_class',
            false
        );
    }

    if ( is_string( $class ) ) {
        $class = bitacora_get_class( $class );
    }

    if (
        ! $class
        || is_wp_error( $class )
        || ! $class instanceof WP_Term
        || 'bitacora_class' !== $class->taxonomy
    ) {
        return new WP_Error(
            'bitacora_invalid_class',
            'La clase indicada no es válida.'
        );
    }

    $section = bitacora_get_item_section( $post_id );

    if ( ! $section ) {
        return new WP_Error(
            'bitacora_item_section_required',
            'El ítem debe tener una sección antes de asignarle una clase.'
        );
    }

    $scope = bitacora_get_class_meta(
        $class,
        'bitacora_class_scope'
    );

    $scope_id = bitacora_get_class_meta(
        $class,
        'bitacora_class_scope_id'
    );

    if (
        'section' !== $scope
        || $section->slug !== $scope_id
    ) {
        return new WP_Error(
            'bitacora_class_section_mismatch',
            'La clase indicada no pertenece a la sección del ítem.'
        );
    }

    return wp_set_object_terms(
        $post_id,
        array( (int) $class->term_id ),
        'bitacora_class',
        false
    );
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
