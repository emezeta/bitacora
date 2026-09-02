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
