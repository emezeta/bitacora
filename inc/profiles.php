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


/**
 * Siembra las secciones de un perfil de instalación.
 *
 * Reglas:
 * - crea una sección si todavía no existe;
 * - identifica las secciones existentes por slug;
 * - nunca renombra una sección existente;
 * - nunca modifica su slug;
 * - nunca sobrescribe termmeta existente;
 * - sólo agrega metadatos que todavía no existen.
 *
 * El perfil sirve exclusivamente como configuración inicial.
 */
function bitacora_seed_profile_sections( $profile_id ) {

    $profile = bitacora_load_profile( $profile_id );

    if ( ! $profile ) {
        return new WP_Error(
            'bitacora_profile_not_found',
            sprintf(
                'No se pudo cargar el perfil "%s".',
                (string) $profile_id
            )
        );
    }

    $report = array(
        'profile'    => $profile['id'],
        'created'    => 0,
        'existing'   => 0,
        'meta_added' => 0,
        'errors'     => array(),
        'sections'   => array(),
    );

    foreach ( $profile['sections'] as $section_id => $section ) {

        if (
            empty( $section['name'] )
            || empty( $section['slug'] )
        ) {
            $report['errors'][] = sprintf(
                'Sección "%s": faltan name o slug.',
                $section_id
            );
            continue;
        }

        $slug = sanitize_title( $section['slug'] );

        $term = get_term_by(
            'slug',
            $slug,
            'bitacora_section'
        );

        $status = 'existing';

        /*
         * Crear el término solamente si no existe.
         */
        if ( ! $term ) {

            $result = wp_insert_term(
                $section['name'],
                'bitacora_section',
                array(
                    'slug' => $slug,
                )
            );

            if ( is_wp_error( $result ) ) {
                $report['errors'][] = sprintf(
                    'Sección "%s": %s',
                    $slug,
                    $result->get_error_message()
                );
                continue;
            }

            $term = get_term(
                $result['term_id'],
                'bitacora_section'
            );

            if ( ! $term || is_wp_error( $term ) ) {
                $report['errors'][] = sprintf(
                    'Sección "%s": no se pudo recuperar el término creado.',
                    $slug
                );
                continue;
            }

            $status = 'created';
            $report['created']++;

        } else {
            $report['existing']++;
        }

        /*
         * Convertir la definición del perfil a termmeta.
         */
        $meta = bitacora_profile_section_to_term_meta( $section );

        $section_meta_added = 0;

        foreach ( $meta as $meta_key => $meta_value ) {

            /*
             * metadata_exists() permite distinguir entre:
             *
             * - metadato inexistente;
             * - metadato existente cuyo valor es ''.
             *
             * Esto es importante para valores deliberadamente vacíos,
             * como subtitle.
             */
            if (
                metadata_exists(
                    'term',
                    $term->term_id,
                    $meta_key
                )
            ) {
                continue;
            }

            $result = add_term_meta(
                $term->term_id,
                $meta_key,
                $meta_value,
                true
            );

            if ( is_wp_error( $result ) || false === $result ) {
                $report['errors'][] = sprintf(
                    'Sección "%s": no se pudo agregar %s.',
                    $slug,
                    $meta_key
                );
                continue;
            }

            $section_meta_added++;
            $report['meta_added']++;
        }

        $report['sections'][] = array(
            'id'         => $section_id,
            'slug'       => $slug,
            'term_id'    => (int) $term->term_id,
            'status'     => $status,
            'meta_added' => $section_meta_added,
        );
    }

    return $report;
}
