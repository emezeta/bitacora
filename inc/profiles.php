<?php
/**
 * Bitácora - Perfiles de instalación.
 *
 * Los perfiles contienen datos iniciales opcionales.
 * No existe un perfil universal ni uno cargado por defecto.
 */

defined( 'ABSPATH' ) || exit;


/**
 * Carga explícitamente un perfil por su identificador.
 *
 * Ejemplo:
 *     bitacora_load_profile( 'construccion' );
 *
 * Devuelve el array del perfil o false si no existe o no es válido.
 */
function bitacora_load_profile( $profile_id ) {

    if ( ! is_string( $profile_id ) || '' === $profile_id ) {
        return false;
    }

    $profile_id = sanitize_key( $profile_id );

    $file = get_stylesheet_directory()
        . '/inc/profiles/'
        . $profile_id
        . '.php';

    if ( ! is_file( $file ) ) {
        return false;
    }

    $profile = require $file;

    if ( ! is_array( $profile ) ) {
        return false;
    }

    if (
        empty( $profile['id'] )
        || $profile_id !== $profile['id']
        || empty( $profile['sections'] )
        || ! is_array( $profile['sections'] )
    ) {
        return false;
    }

    return $profile;
}


/**
 * Convierte la configuración de una sección de perfil
 * en los termmeta utilizados por bitacora_section.
 *
 * name y slug quedan fuera deliberadamente:
 * son propiedades nativas del término de WordPress.
 */
function bitacora_profile_section_to_term_meta( $section ) {

    if ( ! is_array( $section ) ) {
        return array();
    }

    $map = array(
        'singular'          => 'bitacora_section_singular',
        'plural'            => 'bitacora_section_plural',
        'subtitle'          => 'bitacora_section_subtitle',
        'new_label'         => 'bitacora_section_new_label',

        'order'             => 'bitacora_section_order',
        'area'              => 'bitacora_section_area',
        'state'             => 'bitacora_section_state',

        'feature_file'      => 'bitacora_section_feature_file',
        'feature_thumbnail' => 'bitacora_section_feature_thumbnail',
        'feature_location'  => 'bitacora_section_feature_location',
    );

    $boolean_keys = array(
        'feature_file',
        'feature_thumbnail',
        'feature_location',
    );

    $meta = array();

    foreach ( $map as $source_key => $meta_key ) {

        if ( ! array_key_exists( $source_key, $section ) ) {
            continue;
        }

        $value = $section[ $source_key ];

        if ( in_array( $source_key, $boolean_keys, true ) ) {
            $value = $value ? 1 : 0;
        }

        $meta[ $meta_key ] = $value;
    }

    return $meta;
}
