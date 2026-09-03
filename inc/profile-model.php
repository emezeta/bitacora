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
 * Prepara la estructura persistente de una definición de perfil.
 *
 * La definición contiene exclusivamente core, sections y classes.
 * id y label tienen fuentes canónicas independientes.
 */
function bitacora_prepare_stored_profile_definition( $definition = null ) {

        if ( null === $definition ) {
                $definition = array();
        }

        if ( ! is_array( $definition ) ) {
                return new WP_Error(
                        'bitacora_profile_definition_invalid',
                        'La definición del perfil debe ser un array.'
                );
        }

        $prepared = array();

        foreach ( array( 'core', 'sections', 'classes' ) as $key ) {

                if ( ! isset( $definition[ $key ] ) ) {
                        $prepared[ $key ] = array();
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

                $prepared[ $key ] = $definition[ $key ];
        }

        return $prepared;
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

        $definition = bitacora_prepare_stored_profile_definition(
                $definition
        );

        if ( is_wp_error( $definition ) ) {
                return $definition;
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


/**
 * Actualiza la definición de un perfil persistente.
 *
 * El guardado reemplaza atómicamente el documento completo de definición.
 * La identidad técnica y la denominación no forman parte de esta operación.
 *
 * Un perfil que está o estuvo en uso no puede modificarse.
 *
 * Devuelve el resultado del guardado junto con su validación actual,
 * o WP_Error.
 */
function bitacora_update_stored_profile_definition(
        $profile_id,
        $definition
) {

        if ( ! is_string( $profile_id ) ) {
                return new WP_Error(
                        'bitacora_profile_id_invalid',
                        'La identidad del perfil no es válida.'
                );
        }

        $profile_id = strtolower( trim( $profile_id ) );

        if ( ! wp_is_uuid( $profile_id, 4 ) ) {
                return new WP_Error(
                        'bitacora_profile_id_invalid',
                        'La identidad del perfil no es válida.'
                );
        }

        /*
         * Esta API administra exclusivamente perfiles persistentes.
         * Un perfil incluido con Bitácora no puede editarse por esta vía.
         */
        $bundled_file = get_stylesheet_directory()
                . '/inc/profiles/'
                . $profile_id
                . '.php';

        if ( is_file( $bundled_file ) ) {
                return new WP_Error(
                        'bitacora_profile_not_stored',
                        'El perfil no es un perfil persistente editable.'
                );
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

        if ( 1 !== count( $post_ids ) ) {
                return new WP_Error(
                        'bitacora_profile_not_resolvable',
                        'No se pudo resolver un único perfil persistente con esa identidad.'
                );
        }

        if ( $profile_id === bitacora_get_configured_profile_id() ) {
                return new WP_Error(
                        'bitacora_profile_in_use',
                        'No se puede modificar la definición del perfil que está en uso.'
                );
        }

        /*
         * Desde su primer uso, un UUID representa una definición histórica.
         * Modificarla posteriormente haría que la misma identidad técnica
         * pasara a describir configuraciones diferentes.
         */
        if ( bitacora_profile_was_used( $profile_id ) ) {
                return new WP_Error(
                        'bitacora_profile_already_used',
                        'No se puede modificar la definición de un perfil que ya fue usado.'
                );
        }

        $definition = bitacora_prepare_stored_profile_definition(
                $definition
        );

        if ( is_wp_error( $definition ) ) {
                return $definition;
        }

        $post_id = (int) $post_ids[0];

        $current_definition = get_post_meta(
                $post_id,
                '_bitacora_profile_definition',
                true
        );

        if ( ! is_array( $current_definition ) ) {
                return new WP_Error(
                        'bitacora_profile_definition_invalid',
                        'La definición persistida del perfil no es válida.'
                );
        }

        /*
         * update_post_meta() devuelve false también cuando el valor nuevo
         * es idéntico. Se distingue explícitamente para conservar
         * idempotencia sin interpretar un no-cambio como error.
         */
        if ( $current_definition === $definition ) {

                return array(
                        'post_id'    => $post_id,
                        'profile_id' => $profile_id,
                        'changed'    => false,
                        'validation' => bitacora_validate_profile(
                                $profile_id
                        ),
                );
        }

        $saved = update_post_meta(
                $post_id,
                '_bitacora_profile_definition',
                $definition
        );

        if ( false === $saved ) {
                return new WP_Error(
                        'bitacora_profile_definition_not_saved',
                        'No se pudo guardar la definición del perfil.'
                );
        }

        /*
         * El perfil debe continuar siendo resoluble después del guardado,
         * aunque todavía pueda estar EN PREPARACIÓN.
         */
        if ( ! bitacora_load_profile( $profile_id ) ) {

                update_post_meta(
                        $post_id,
                        '_bitacora_profile_definition',
                        $current_definition
                );

                return new WP_Error(
                        'bitacora_profile_not_resolvable',
                        'La definición fue rechazada porque el perfil dejó de ser resoluble.'
                );
        }

        return array(
                'post_id'    => $post_id,
                'profile_id' => $profile_id,
                'changed'    => true,
                'validation' => bitacora_validate_profile(
                        $profile_id
                ),
        );
}


/**
 * Devuelve las identidades de los perfiles incluidos con Bitácora.
 *
 * La identidad física del archivo es también la identidad técnica:
 * inc/profiles/<uuid>.php
 */
function bitacora_get_bundled_profile_ids() {

        $directory = get_stylesheet_directory() . '/inc/profiles';

        if ( ! is_dir( $directory ) ) {
                return array();
        }

        $files = glob( $directory . '/*.php' );

        if ( false === $files ) {
                return array();
        }

        $profile_ids = array();

        foreach ( $files as $file ) {

                $profile_id = strtolower(
                        pathinfo( $file, PATHINFO_FILENAME )
                );

                if ( wp_is_uuid( $profile_id, 4 ) ) {
                        $profile_ids[ $profile_id ] = true;
                }
        }

        $profile_ids = array_keys( $profile_ids );

        sort(
                $profile_ids,
                SORT_STRING
        );

        return $profile_ids;
}


/**
 * Devuelve un catálogo uniforme de perfiles conocidos por Bitácora.
 *
 * Cada entrada expone estado de dominio, no estado de interfaz:
 *
 * - id
 * - label
 * - source: bundled | stored | collision
 * - resolvable
 * - available
 * - in_use
 * - was_used
 * - editable
 * - deletable
 * - errors
 *
 * "collision" es un estado diagnóstico excepcional: una misma identidad
 * aparece en más de una fuente y por lo tanto no representa un perfil
 * resoluble.
 */
function bitacora_get_profile_catalog() {

        $configured_profile_id = bitacora_get_configured_profile_id();

        $identities = array();

        /*
         * Perfiles incluidos.
         */
        foreach ( bitacora_get_bundled_profile_ids() as $profile_id ) {

                $identities[ $profile_id ] = array(
                        'bundled'     => true,
                        'stored_ids'  => array(),
                );
        }

        /*
         * Perfiles persistentes.
         *
         * Se conservan todas las coincidencias para detectar identidades
         * duplicadas en lugar de dejar que una gane silenciosamente.
         */
        $stored_posts = get_posts(
                array(
                        'post_type'      => 'bitacora_profile',
                        'post_status'    => 'any',
                        'posts_per_page' => -1,
                        'fields'         => 'ids',
                        'orderby'        => 'ID',
                        'order'          => 'ASC',
                )
        );

        foreach ( $stored_posts as $post_id ) {

                $profile_id = get_post_meta(
                        $post_id,
                        '_bitacora_profile_id',
                        true
                );

                if ( ! is_string( $profile_id ) ) {
                        continue;
                }

                $profile_id = strtolower( trim( $profile_id ) );

                if ( ! wp_is_uuid( $profile_id, 4 ) ) {
                        continue;
                }

                if ( ! isset( $identities[ $profile_id ] ) ) {
                        $identities[ $profile_id ] = array(
                                'bundled'    => false,
                                'stored_ids' => array(),
                        );
                }

                $identities[ $profile_id ]['stored_ids'][] = (int) $post_id;
        }

        $catalog = array();

        foreach ( $identities as $profile_id => $identity ) {

                $has_bundled = ! empty( $identity['bundled'] );
                $stored_ids  = $identity['stored_ids'];
                $stored_count = count( $stored_ids );

                $collision = (
                        $has_bundled
                        && $stored_count > 0
                ) || $stored_count > 1;

                if ( $collision ) {
                        $source = 'collision';
                } elseif ( $has_bundled ) {
                        $source = 'bundled';
                } else {
                        $source = 'stored';
                }

                $profile    = false;
                $resolvable = false;
                $available  = false;
                $errors     = array();

                if ( ! $collision ) {

                        $profile = bitacora_load_profile(
                                $profile_id
                        );

                        $resolvable = is_array( $profile );

                        if ( $resolvable ) {

                                $validation = bitacora_validate_profile(
                                        $profile_id
                                );

                                $available = ! empty(
                                        $validation['available']
                                );

                                $errors = isset( $validation['errors'] )
                                        && is_array( $validation['errors'] )
                                                ? $validation['errors']
                                                : array();
                        } else {
                                $errors[] = 'No se pudo resolver el perfil.';
                        }
                } elseif ( $has_bundled && $stored_count > 0 ) {

                        $errors[] = 'La identidad técnica está duplicada entre un perfil incluido y uno persistente.';
                } else {

                        $errors[] = 'La identidad técnica está duplicada entre perfiles persistentes.';
                }

                /*
                 * Para perfiles resolubles, el nombre procede del loader.
                 * Ante una colisión stored se usa sólo como diagnóstico el
                 * primer título encontrado, sin considerar esa fuente válida.
                 */
                if (
                        $resolvable
                        && isset( $profile['label'] )
                        && '' !== trim( (string) $profile['label'] )
                ) {
                        $label = trim( (string) $profile['label'] );
                } elseif ( ! empty( $stored_ids ) ) {

                        $label = trim(
                                (string) get_post_field(
                                        'post_title',
                                        $stored_ids[0],
                                        'raw'
                                )
                        );

                        if ( '' === $label ) {
                                $label = $profile_id;
                        }
                } else {
                        $label = $profile_id;
                }

                $in_use = (
                        '' !== $configured_profile_id
                        && $profile_id === $configured_profile_id
                );

                $was_used = bitacora_profile_was_used(
                        $profile_id
                );

                /*
                 * Editable no es una decisión de UI.
                 *
                 * Sólo un perfil persistente, resoluble y nunca usado puede
                 * cambiar su definición.
                 */
                $editable = (
                        'stored' === $source
                        && $resolvable
                        && ! $in_use
                        && ! $was_used
                );

                /*
                 * Eliminable es una propiedad separada aunque hoy comparta
                 * las mismas condiciones que editable.
                 *
                 * Mantener ambas semánticas explícitas permite que las reglas
                 * diverjan en el futuro sin trasladar decisiones a la UI.
                 */
                $deletable = (
                        'stored' === $source
                        && $resolvable
                        && ! $in_use
                        && ! $was_used
                );

                $catalog[] = array(
                        'id'         => $profile_id,
                        'label'      => $label,
                        'source'     => $source,
                        'resolvable' => $resolvable,
                        'available'  => $available,
                        'in_use'     => $in_use,
                        'was_used'   => $was_used,
                        'editable'   => $editable,
                        'deletable'  => $deletable,
                        'errors'     => $errors,
                );
        }

        /*
         * Orden estable y humano.
         * La identidad técnica resuelve empates de denominación.
         */
        usort(
                $catalog,
                static function ( $a, $b ) {

                        $label_compare = strcasecmp(
                                $a['label'],
                                $b['label']
                        );

                        if ( 0 !== $label_compare ) {
                                return $label_compare;
                        }

                        return strcmp(
                                $a['id'],
                                $b['id']
                        );
                }
        );

        return $catalog;
}


/**
 * Elimina definitivamente un perfil persistente que nunca estuvo en uso.
 *
 * Los perfiles incluidos, actualmente en uso, previamente usados o con una
 * identidad ambigua no pueden eliminarse mediante esta API.
 *
 * Devuelve post_id + profile_id o WP_Error.
 */
function bitacora_delete_stored_profile( $profile_id ) {

        if ( ! is_string( $profile_id ) ) {
                return new WP_Error(
                        'bitacora_profile_id_invalid',
                        'La identidad del perfil no es válida.'
                );
        }

        $profile_id = strtolower( trim( $profile_id ) );

        if ( ! wp_is_uuid( $profile_id, 4 ) ) {
                return new WP_Error(
                        'bitacora_profile_id_invalid',
                        'La identidad del perfil no es válida.'
                );
        }

        /*
         * Un perfil incluido forma parte del producto y no puede
         * eliminarse mediante la administración de perfiles persistentes.
         */
        $bundled_file = get_stylesheet_directory()
                . '/inc/profiles/'
                . $profile_id
                . '.php';

        if ( is_file( $bundled_file ) ) {
                return new WP_Error(
                        'bitacora_profile_not_stored',
                        'El perfil no es un perfil persistente eliminable.'
                );
        }

        /*
         * Resolver exactamente un registro persistente.
         *
         * Cero coincidencias significa inexistente y más de una constituye
         * una colisión que debe resolverse fuera de la operación normal.
         */
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

        if ( 1 !== count( $post_ids ) ) {
                return new WP_Error(
                        'bitacora_profile_not_resolvable',
                        'No se pudo resolver un único perfil persistente con esa identidad.'
                );
        }

        if ( $profile_id === bitacora_get_configured_profile_id() ) {
                return new WP_Error(
                        'bitacora_profile_in_use',
                        'No se puede eliminar el perfil que está en uso.'
                );
        }

        /*
         * Desde su primer uso, el UUID adquiere significado histórico.
         * El registro debe conservarse aunque el perfil ya no esté en uso.
         */
        if ( bitacora_profile_was_used( $profile_id ) ) {
                return new WP_Error(
                        'bitacora_profile_already_used',
                        'No se puede eliminar un perfil que ya fue usado.'
                );
        }

        $post_id = (int) $post_ids[0];

        /*
         * Eliminación definitiva deliberada:
         * un perfil nunca usado es una configuración de trabajo sin
         * significado histórico que justifique conservarla en Papelera.
         */
        $deleted = wp_delete_post(
                $post_id,
                true
        );

        if ( ! $deleted ) {
                return new WP_Error(
                        'bitacora_profile_not_deleted',
                        'No se pudo eliminar el perfil.'
                );
        }

        if ( bitacora_stored_profile_identity_exists( $profile_id ) ) {
                return new WP_Error(
                        'bitacora_profile_delete_not_confirmed',
                        'La eliminación del perfil no pudo confirmarse.'
                );
        }

        return array(
                'post_id'    => $post_id,
                'profile_id' => $profile_id,
                'deleted'    => true,
        );
}
