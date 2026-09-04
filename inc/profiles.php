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
 * El identificador técnico de todo perfil es un UUID v4 estable.
 *
 * Devuelve el array del perfil o false si no existe o no es válido.
 */
/**
 * Configuración mínima del contenedor principal de toda Bitácora.
 *
 * El perfil puede sobrescribir estos valores mediante su clave "core".
 * Las secciones complementarias siguen viviendo en "sections".
 */
function bitacora_get_default_core_section_definition() {

    return array(
        'name'      => 'Notas',
        'slug'      => 'notas',
        'singular'  => 'Nota',
        'plural'    => 'Notas',
        'subtitle'  => '',
        'new_label' => 'Nueva nota',
        'order'     => 0,
        'area'      => 'main',
        'state'     => 'active',

        'feature_file'      => true,
        'feature_thumbnail' => false,
        'feature_location'  => false,
        'feature_comments'  => false,
    );
}


function bitacora_load_profile( $profile_id ) {

    if ( ! is_string( $profile_id ) || '' === $profile_id ) {
        return false;
    }

    $profile_id = strtolower( trim( $profile_id ) );

    if ( ! wp_is_uuid( $profile_id, 4 ) ) {
        return false;
    }

    $file = get_stylesheet_directory()
        . '/inc/profiles/'
        . $profile_id
        . '.php';

    if ( is_file( $file ) ) {

        /*
         * Un UUID identifica exactamente un perfil.
         * Un registro persistente con la misma identidad que un perfil
         * incluido constituye una colisión y no puede resolverse.
         */
        if ( bitacora_stored_profile_identity_exists( $profile_id ) ) {
            return false;
        }

        $profile = require $file;
    } else {
        $profile = bitacora_get_stored_profile_definition( $profile_id );
    }

    if ( ! is_array( $profile ) ) {
        return false;
    }

    if (
        empty( $profile['id'] )
        || $profile_id !== $profile['id']
        || (
            isset( $profile['sections'] )
            && ! is_array( $profile['sections'] )
        )
        || (
            isset( $profile['core'] )
            && ! is_array( $profile['core'] )
        )
    ) {
        return false;
    }

    /*
     * El core pertenece al sistema, no a la lista de secciones
     * opcionales del perfil.
     *
     * Si el perfil no lo configura, se usa Notas.
     */
    $core_overrides = isset( $profile['core'] )
        ? $profile['core']
        : array();

    $profile['core'] = array_replace(
        bitacora_get_default_core_section_definition(),
        $core_overrides
    );

    /*
     * El rol no es configurable libremente:
     * exactamente un término nace como core y todos los demás
     * nacen como secciones complementarias.
     */
    $profile['core']['role'] = 'core';

    if ( ! isset( $profile['sections'] ) ) {
        $profile['sections'] = array();
    }

    foreach ( $profile['sections'] as $section_id => $section ) {

        if ( ! is_array( $section ) ) {
            continue;
        }

        $profile['sections'][ $section_id ]['role'] = 'section';

        if (
            ! array_key_exists(
                'feature_comments',
                $profile['sections'][ $section_id ]
            )
        ) {
            $profile['sections'][ $section_id ]['feature_comments'] = false;
        }
    }

    return $profile;
}


/**
 * Valida si un perfil está completo y puede ponerse en uso.
 *
 * No modifica ni siembra configuración.
 */
function bitacora_validate_profile( $profile_id ) {

    $profile = bitacora_load_profile( $profile_id );

    $report = array(
        'profile'   => sanitize_key( (string) $profile_id ),
        'valid'     => false,
        'complete'  => false,
        'enabled'   => false,
        'errors'    => array(),
    );

    if ( ! $profile ) {
        $report['errors'][] = 'No se pudo cargar el perfil.';
        return $report;
    }

    $report['profile'] = $profile['id'];

    $complete = true;

    /*
     * Secciones válidas a las que podrán referirse las clases.
     * El core forma parte de este conjunto.
     */
    $section_slugs = array();

    $sections = array(
        'core' => $profile['core'],
    );

    foreach ( $profile['sections'] as $section_id => $section ) {
        $sections[ $section_id ] = $section;
    }

    foreach ( $sections as $section_id => $section ) {

        if ( ! is_array( $section ) ) {
            $report['errors'][] = sprintf(
                'Sección "%s": definición inválida.',
                $section_id
            );
            continue;
        }

        if ( empty( $section['name'] ) || empty( $section['slug'] ) ) {
            $report['errors'][] = sprintf(
                'Sección "%s": faltan name o slug.',
                $section_id
            );
            continue;
        }

        $slug = sanitize_title( $section['slug'] );

        if ( '' === $slug ) {
            $report['errors'][] = sprintf(
                'Sección "%s": slug inválido.',
                $section_id
            );
            continue;
        }

        if ( isset( $section_slugs[ $slug ] ) ) {
            $report['errors'][] = sprintf(
                'Sección "%s": slug duplicado "%s".',
                $section_id,
                $slug
            );
            continue;
        }

        $section_slugs[ $slug ] = true;

        /*
         * Notas/core pertenece siempre a main.
         *
         * Una sección complementaria sin "area" es válida durante la
         * construcción del perfil, pero mantiene el perfil incompleto.
         */
        if ( 'core' === $section_id ) {

            if (
                ! array_key_exists( 'area', $section )
                || 'main' !== $section['area']
            ) {
                $report['errors'][] =
                    'Sección "core": area debe ser "main".';
            }

        } elseif ( ! array_key_exists( 'area', $section ) ) {

            $complete = false;

        } elseif (
            ! in_array(
                $section['area'],
                array( 'main', 'more' ),
                true
            )
        ) {
            $report['errors'][] = sprintf(
                'Sección "%s": area debe ser "main" o "more".',
                $section_id
            );
        }
    }

    /*
     * La colección de clases debe ser estructuralmente válida.
     * Un array vacío es válido, pero deja el perfil incompleto.
     */
    if (
        ! isset( $profile['classes'] )
        || ! is_array( $profile['classes'] )
    ) {
        $report['errors'][] = 'La definición de clases no es válida.';
        return $report;
    }

    if ( empty( $profile['classes'] ) ) {
        $complete = false;
    }

    $class_slugs = array();

    foreach ( $profile['classes'] as $class_id => $class ) {

        if ( ! is_array( $class ) ) {
            $report['errors'][] = sprintf(
                'Clase "%s": definición inválida.',
                $class_id
            );
            continue;
        }

        if ( empty( $class['name'] ) || empty( $class['slug'] ) ) {
            $report['errors'][] = sprintf(
                'Clase "%s": faltan name o slug.',
                $class_id
            );
            continue;
        }

        $slug = sanitize_title( $class['slug'] );

        if ( '' === $slug ) {
            $report['errors'][] = sprintf(
                'Clase "%s": slug inválido.',
                $class_id
            );
            continue;
        }

        if ( isset( $class_slugs[ $slug ] ) ) {
            $report['errors'][] = sprintf(
                'Clase "%s": slug duplicado "%s".',
                $class_id,
                $slug
            );
        } else {
            $class_slugs[ $slug ] = true;
        }

        if ( 'section' !== ( $class['scope'] ?? '' ) ) {
            $report['errors'][] = sprintf(
                'Clase "%s": scope debe ser "section".',
                $class_id
            );
            continue;
        }

        $scope_id = sanitize_title(
            (string) ( $class['scope_id'] ?? '' )
        );

        if (
            '' === $scope_id
            || ! isset( $section_slugs[ $scope_id ] )
        ) {
            $report['errors'][] = sprintf(
                'Clase "%s": scope_id "%s" no corresponde a una sección del perfil.',
                $class_id,
                $scope_id ?: '(vacío)'
            );
        }
    }

    $report['valid'] = empty( $report['errors'] );

    $report['complete'] = $report['valid'] && $complete;
    $report['enabled'] = $report['valid'] && $report['complete'];

    return $report;
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
        'role'              => 'bitacora_section_role',

        'feature_file'      => 'bitacora_section_feature_file',
        'feature_thumbnail' => 'bitacora_section_feature_thumbnail',
        'feature_location'  => 'bitacora_section_feature_location',
        'feature_comments'  => 'bitacora_section_feature_comments',
    );

    $boolean_keys = array(
        'feature_file',
        'feature_thumbnail',
        'feature_location',
        'feature_comments',
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

    /*
     * El core se siembra primero; después vienen las secciones
     * complementarias del perfil.
     */
    $sections_to_seed = array(
        array(
            'id'      => 'core',
            'section' => $profile['core'],
        ),
    );

    foreach ( $profile['sections'] as $section_id => $section ) {
        $sections_to_seed[] = array(
            'id'      => $section_id,
            'section' => $section,
        );
    }

    foreach ( $sections_to_seed as $section_entry ) {

        $section_id = $section_entry['id'];
        $section    = $section_entry['section'];

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


/**
 * Convierte la configuración de una clase de perfil
 * en los termmeta utilizados por bitacora_class.
 *
 * name y slug quedan fuera deliberadamente:
 * son propiedades nativas del término de WordPress.
 */
function bitacora_profile_class_to_term_meta( $class ) {

    if ( ! is_array( $class ) ) {
        return array();
    }

    $map = array(
        'scope'    => 'bitacora_class_scope',
        'scope_id' => 'bitacora_class_scope_id',
        'order'    => 'bitacora_class_order',
        'state'    => 'bitacora_class_state',
    );

    $meta = array();

    foreach ( $map as $source_key => $meta_key ) {

        if ( ! array_key_exists( $source_key, $class ) ) {
            continue;
        }

        $meta[ $meta_key ] = $class[ $source_key ];
    }

    return $meta;
}


/**
 * Siembra las clases de un perfil de instalación.
 *
 * Reglas:
 * - crea una clase si todavía no existe;
 * - identifica las clases existentes por slug;
 * - nunca renombra una clase existente;
 * - nunca modifica su slug;
 * - nunca sobrescribe termmeta existente;
 * - sólo agrega metadatos que todavía no existen.
 *
 * El perfil sirve exclusivamente como configuración inicial.
 */
function bitacora_seed_profile_classes( $profile_id ) {

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

    if (
        empty( $profile['classes'] )
        || ! is_array( $profile['classes'] )
    ) {
        return new WP_Error(
            'bitacora_profile_classes_not_found',
            sprintf(
                'El perfil "%s" no contiene clases.',
                $profile['id']
            )
        );
    }

    $report = array(
        'profile'    => $profile['id'],
        'created'    => 0,
        'existing'   => 0,
        'meta_added' => 0,
        'errors'     => array(),
        'classes'    => array(),
    );

    foreach ( $profile['classes'] as $class_id => $class ) {

        if (
            empty( $class['name'] )
            || empty( $class['slug'] )
        ) {
            $report['errors'][] = sprintf(
                'Clase "%s": faltan name o slug.',
                $class_id
            );
            continue;
        }

        $slug = sanitize_title( $class['slug'] );

        $term = get_term_by(
            'slug',
            $slug,
            'bitacora_class'
        );

        $status = 'existing';

        /*
         * Crear el término solamente si no existe.
         */
        if ( ! $term ) {

            $result = wp_insert_term(
                $class['name'],
                'bitacora_class',
                array(
                    'slug' => $slug,
                )
            );

            if ( is_wp_error( $result ) ) {
                $report['errors'][] = sprintf(
                    'Clase "%s": %s',
                    $slug,
                    $result->get_error_message()
                );
                continue;
            }

            $term = get_term(
                $result['term_id'],
                'bitacora_class'
            );

            if ( ! $term || is_wp_error( $term ) ) {
                $report['errors'][] = sprintf(
                    'Clase "%s": no se pudo recuperar el término creado.',
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
        $meta = bitacora_profile_class_to_term_meta( $class );

        $class_meta_added = 0;

        foreach ( $meta as $meta_key => $meta_value ) {

            /*
             * metadata_exists() permite distinguir entre:
             *
             * - metadato inexistente;
             * - metadato existente cuyo valor es ''.
             *
             * Esto es importante para scope_id vacío en las
             * clases cuyo scope es notes.
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
                    'Clase "%s": no se pudo agregar %s.',
                    $slug,
                    $meta_key
                );
                continue;
            }

            $class_meta_added++;
            $report['meta_added']++;
        }

        $report['classes'][] = array(
            'id'         => $class_id,
            'slug'       => $slug,
            'term_id'    => (int) $term->term_id,
            'status'     => $status,
            'meta_added' => $class_meta_added,
        );
    }

    return $report;
}
