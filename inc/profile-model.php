<?php
/**
 * Bitácora - Modelo persistente de perfiles.
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Registra la entidad interna utilizada para perfiles editables.
 *
 * No tiene representación pública ni interfaz administrativa propia.
 * La disponibilidad de un perfil no depende del post_status:
 * se determina mediante bitacora_validate_profile().
 */
function bitacora_register_profile_cpt() {

        register_post_type(
                'bitacora_profile',
                array(
                        'labels' => array(
                                'name'          => 'Perfiles',
                                'singular_name' => 'Perfil',
                        ),

                        'public'              => false,
                        'publicly_queryable'  => false,
                        'exclude_from_search' => true,
                        'show_ui'             => false,
                        'show_in_menu'        => false,
                        'show_in_admin_bar'   => false,
                        'show_in_rest'        => false,

                        'has_archive' => false,
                        'rewrite'     => false,
                        'query_var'   => false,

                        'supports' => array(
                                'title',
                                'author',
                        ),

                        /*
                         * Toda operación CRUD sobre perfiles persistentes
                         * requiere el permiso de administración de perfiles.
                         *
                         * use_bitacora_profiles queda reservado para la
                         * operación distinta y sensible de poner un perfil
                         * disponible en uso.
                         */
                        'capabilities' => array(
                                'edit_post'              => 'manage_bitacora_profiles',
                                'read_post'              => 'manage_bitacora_profiles',
                                'delete_post'            => 'manage_bitacora_profiles',
                                'edit_posts'             => 'manage_bitacora_profiles',
                                'edit_others_posts'      => 'manage_bitacora_profiles',
                                'delete_posts'           => 'manage_bitacora_profiles',
                                'publish_posts'          => 'manage_bitacora_profiles',
                                'read_private_posts'     => 'manage_bitacora_profiles',
                                'delete_private_posts'   => 'manage_bitacora_profiles',
                                'delete_published_posts' => 'manage_bitacora_profiles',
                                'delete_others_posts'    => 'manage_bitacora_profiles',
                                'edit_private_posts'     => 'manage_bitacora_profiles',
                                'edit_published_posts'   => 'manage_bitacora_profiles',
                                'create_posts'           => 'manage_bitacora_profiles',
                        ),

                        'map_meta_cap'    => false,
                        'delete_with_user' => false,
                )
        );
}

add_action( 'init', 'bitacora_register_profile_cpt' );


function bitacora_stored_profile_identity_exists( $profile_id ) {

        if ( ! is_string( $profile_id ) ) {
                return false;
        }

        $profile_id = strtolower( trim( $profile_id ) );

        if ( ! wp_is_uuid( $profile_id, 4 ) ) {
                return false;
        }

        $post_ids = get_posts(
                array(
                        'post_type'      => 'bitacora_profile',
                        'post_status'    => 'any',
                        'posts_per_page' => 1,
                        'fields'         => 'ids',
                        'meta_key'       => '_bitacora_profile_id',
                        'meta_value'     => $profile_id,
                )
        );

        return ! empty( $post_ids );
}


/**
 * Obtiene la definición cruda de un perfil persistente por UUID.
 *
 * La identidad y la denominación tienen una única fuente de verdad:
 * - _bitacora_profile_id aporta la identidad técnica;
 * - post_title aporta la denominación humana;
 * - _bitacora_profile_definition aporta core, sections y classes.
 *
 * Devuelve false si el perfil no existe, la identidad no es válida,
 * hay más de un registro con la misma identidad o la definición
 * almacenada no es un array.
 */
function bitacora_get_stored_profile_definition( $profile_id ) {

        if ( ! is_string( $profile_id ) ) {
                return false;
        }

        $profile_id = strtolower( trim( $profile_id ) );

        if ( ! wp_is_uuid( $profile_id, 4 ) ) {
                return false;
        }

        $post_ids = get_posts(
                array(
                        'post_type'      => 'bitacora_profile',
                        'post_status'    => 'any',
                        'posts_per_page' => 2,
                        'fields'         => 'ids',
                        'meta_key'       => '_bitacora_profile_id',
                        'meta_value'     => $profile_id,
                        'orderby'        => 'ID',
                        'order'          => 'ASC',
                )
        );

        /*
         * Una identidad técnica debe resolver exactamente un perfil.
         * Cero coincidencias significa inexistente; más de una significa
         * una colisión de identidad y se rechaza de forma segura.
         */
        if ( 1 !== count( $post_ids ) ) {
                return false;
        }

        $post_id    = (int) $post_ids[0];
        $definition = get_post_meta(
                $post_id,
                '_bitacora_profile_definition',
                true
        );

        if ( ! is_array( $definition ) ) {
                return false;
        }

        /*
         * Aunque definition contuviera accidentalmente id o label,
         * las fuentes canónicas prevalecen siempre.
         */
        $definition['id'] = $profile_id;
        $definition['label'] = (string) get_post_field(
                'post_title',
                $post_id,
                'raw'
        );

        return $definition;
}


/**
 * Indica si un UUID ya identifica algún perfil, incluido o persistente.
 */
function bitacora_profile_identity_exists( $profile_id ) {

        if ( ! is_string( $profile_id ) ) {
                return false;
        }

        $profile_id = strtolower( trim( $profile_id ) );

        if ( ! wp_is_uuid( $profile_id, 4 ) ) {
                return false;
        }

        $bundled_file = get_stylesheet_directory()
                . '/inc/profiles/'
                . $profile_id
                . '.php';

        return is_file( $bundled_file )
                || bitacora_stored_profile_identity_exists( $profile_id );
}


/**
 * Genera una identidad técnica nueva para un perfil.
 *
 * El UUID no contiene información sobre nombre, origen ni comportamiento.
 */
function bitacora_generate_profile_id() {

        do {
                $profile_id = strtolower( wp_generate_uuid4() );
        } while ( bitacora_profile_identity_exists( $profile_id ) );

        return $profile_id;
}


/**
 * Crea un perfil persistente en preparación.
 *
 * La identidad técnica se genera internamente y no puede ser elegida
 * por el operador.
 *
 * La disponibilidad del perfil no depende del post_status.
 * "draft" se utiliza únicamente como estado técnico interno del registro.
 *
 * Devuelve post_id + profile_id o WP_Error.
 */
function bitacora_create_stored_profile( $label, $definition = null ) {

        $label = sanitize_text_field( (string) $label );
        $label = trim( $label );

        if ( '' === $label ) {
                return new WP_Error(
                        'bitacora_profile_label_required',
                        'El perfil necesita un nombre.'
                );
        }

        if ( null === $definition ) {
                $definition = array();
        }

        if ( ! is_array( $definition ) ) {
                return new WP_Error(
                        'bitacora_profile_definition_invalid',
                        'La definición del perfil debe ser un array.'
                );
        }

        /*
         * id y label nunca pertenecen a definition.
         * Sus fuentes canónicas son el UUID persistido y post_title.
         */
        unset(
                $definition['id'],
                $definition['label']
        );

        foreach ( array( 'core', 'sections', 'classes' ) as $key ) {

                if ( ! isset( $definition[ $key ] ) ) {
                        $definition[ $key ] = array();
                        continue;
                }

                if ( ! is_array( $definition[ $key ] ) ) {
                        return new WP_Error(
                                'bitacora_profile_definition_invalid',
                                sprintf(
                                        'La clave "%s" de la definición debe ser un array.',
                                        $key
                                )
                        );
                }
        }

        $profile_id = bitacora_generate_profile_id();

        $post_data = array(
                'post_type'   => 'bitacora_profile',
                'post_status' => 'draft',
                'post_title'  => $label,
        );

        $current_user_id = get_current_user_id();

        if ( $current_user_id > 0 ) {
                $post_data['post_author'] = $current_user_id;
        }

        $post_id = wp_insert_post(
                $post_data,
                true
        );

        if ( is_wp_error( $post_id ) ) {
                return $post_id;
        }

        $identity_saved = add_post_meta(
                $post_id,
                '_bitacora_profile_id',
                $profile_id,
                true
        );

        if ( false === $identity_saved ) {
                wp_delete_post( $post_id, true );

                return new WP_Error(
                        'bitacora_profile_identity_not_saved',
                        'No se pudo guardar la identidad del perfil.'
                );
        }

        $definition_saved = add_post_meta(
                $post_id,
                '_bitacora_profile_definition',
                $definition,
                true
        );

        if ( false === $definition_saved ) {
                wp_delete_post( $post_id, true );

                return new WP_Error(
                        'bitacora_profile_definition_not_saved',
                        'No se pudo guardar la definición del perfil.'
                );
        }

        /*
         * El registro recién creado debe poder resolverse inmediatamente
         * mediante la misma API utilizada para cualquier perfil.
         */
        if ( ! bitacora_load_profile( $profile_id ) ) {
                wp_delete_post( $post_id, true );

                return new WP_Error(
                        'bitacora_profile_not_resolvable',
                        'El perfil fue creado pero no pudo resolverse correctamente.'
                );
        }

        return array(
                'post_id'    => (int) $post_id,
                'profile_id' => $profile_id,
        );
}
