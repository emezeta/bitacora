<?php
/**
 * Bitácora - Modelo persistente de perfiles.
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Registra la entidad interna utilizada para Profiles persistidos.
 *
 * No tiene representación pública ni interfaz administrativa propia.
 * Los Profiles persistidos aceptados se almacenan con post_status=publish.
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
                         * El acceso técnico a Profiles persistidos requiere
                         * el permiso de administración de perfiles.
                         *
                         * use_bitacora_profiles queda reservado para la
                         * operación distinta y sensible de poner un Profile
                         * en uso.
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
                        'post_status'    => 'publish',
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
 * Prepara la estructura de una definición de perfil.
 *
 * La definición contiene exclusivamente core, sections y classes.
 * id y label tienen fuentes canónicas independientes.
 */
function bitacora_prepare_profile_definition( $definition = null ) {

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
 * Lee un Template externo de Perfil y construye un candidato transitorio.
 *
 * Los Templates viven fuera de la biblioteca de Perfiles y no poseen UUID
 * ni estado de dominio Bitácora.
 */
function bitacora_load_profile_template( $template_name ) {

        if ( ! is_string( $template_name ) ) {
                return new WP_Error(
                        'bitacora_profile_template_name_invalid',
                        'El nombre del Template no es válido.'
                );
        }

        $template_name = sanitize_file_name(
                trim( $template_name )
        );

        if ( '' === $template_name ) {
                return new WP_Error(
                        'bitacora_profile_template_name_invalid',
                        'El nombre del Template no es válido.'
                );
        }

        if ( '.php' !== substr( $template_name, -4 ) ) {
                $template_name .= '.php';
        }

        $directory = get_stylesheet_directory()
                . '/inc/profile-templates';

        $file = $directory . '/' . $template_name;

        if ( ! is_file( $file ) ) {
                return new WP_Error(
                        'bitacora_profile_template_not_found',
                        'No se encontró el Template solicitado.'
                );
        }

        $template = require $file;

        if ( ! is_array( $template ) ) {
                return new WP_Error(
                        'bitacora_profile_template_invalid',
                        'El Template debe devolver un array.'
                );
        }

        foreach ( array_keys( $template ) as $key ) {

                if (
                        ! in_array(
                                $key,
                                array(
                                        'label',
                                        'core',
                                        'sections',
                                        'classes',
                                ),
                                true
                        )
                ) {
                        return new WP_Error(
                                'bitacora_profile_template_invalid',
                                sprintf(
                                        'Template: atributo no permitido "%s".',
                                        $key
                                )
                        );
                }
        }

        if ( ! array_key_exists( 'label', $template ) ) {
                return new WP_Error(
                        'bitacora_profile_template_invalid',
                        'Template: label es obligatorio.'
                );
        }

        $definition = array(
                'core'     => isset( $template['core'] )
                        ? $template['core']
                        : array(),
                'sections' => isset( $template['sections'] )
                        ? $template['sections']
                        : array(),
                'classes'  => isset( $template['classes'] )
                        ? $template['classes']
                        : array(),
        );

        $candidate = array(
                'label'      => $template['label'],
                'definition' => $definition,
        );

        $validation = bitacora_validate_profile_candidate(
                $candidate
        );

        if ( empty( $validation['valid'] ) ) {
                return new WP_Error(
                        'bitacora_profile_template_invalid',
                        'El Template no puede producir un candidato válido.',
                        $validation
                );
        }

        return $candidate;
}


/**
 * Crea un Perfil a partir de un Template externo.
 *
 * El Template se limita a producir un candidato transitorio. La decisión
 * autoritativa pertenece a la adopción del Engine.
 *
 * $options puede contener allow_similar=true cuando el operador ya confirmó
 * explícitamente que desea continuar ante una advertencia de similitud.
 */
function bitacora_create_profile_from_template(
        $template_name,
        $options = array()
) {

        $candidate = bitacora_load_profile_template(
                $template_name
        );

        if ( is_wp_error( $candidate ) ) {
                return array(
                        'accepted' => false,
                        'errors'   => array(
                                array(
                                        'code'    => $candidate->get_error_code(),
                                        'message' => $candidate->get_error_message(),
                                        'data'    => $candidate->get_error_data(),
                                ),
                        ),
                );
        }

        return bitacora_adopt_profile_candidate(
                $candidate,
                $options
        );
}


/**
 * Promueve un candidato presentado por un productor externo.
 *
 * PROMOCIÓN sólo define el origen de la operación. La decisión autoritativa
 * pertenece exclusivamente al Engine.
 *
 * $options puede contener allow_similar=true cuando el operador ya confirmó
 * explícitamente que desea continuar ante una advertencia de similitud.
 */
function bitacora_promote_profile_candidate(
        $candidate,
        $options = array()
) {

        return bitacora_adopt_profile_candidate(
                $candidate,
                $options
        );
}


/**
 * Adopta autoritativamente un candidato y, si corresponde,
 * crea un Profile aceptado en la biblioteca.
 *
 * El candidato no puede imponer identidad técnica ni estado persistente.
 * Un rechazo no deja Profile observable ni modifica la instancia runtime.
 *
 * La similitud puede requerir confirmación operacional antes de ACCEPT,
 * sin crear estado persistente intermedio.
 */
function bitacora_adopt_profile_candidate(
        $candidate,
        $options = array()
) {

        global $wpdb;

        if ( ! is_array( $options ) ) {
                $options = array();
        }

        /*
         * La confirmación de similitud pertenece a la operación de adopción,
         * nunca al candidato ni a la definición persistida.
         */
        $allow_similar = ! empty( $options['allow_similar'] );

        $reject = static function ( $errors ) {

                if ( ! is_array( $errors ) ) {
                        $errors = array( (string) $errors );
                }

                return array(
                        'accepted' => false,
                        'errors'   => array_values( $errors ),
                );
        };

        /*
         * Toda comprobación puramente lógica ocurre antes de entrar
         * en la sección crítica de persistencia.
         */
        $validation = bitacora_validate_profile_candidate( $candidate );

        if ( empty( $validation['valid'] ) ) {

                return $reject(
                        isset( $validation['errors'] )
                        && is_array( $validation['errors'] )
                                ? $validation['errors']
                                : array( 'El candidato no es válido.' )
                );
        }

        $label = trim(
                sanitize_text_field(
                        (string) $candidate['label']
                )
        );

        $definition = bitacora_prepare_profile_definition(
                $candidate['definition']
        );

        if ( is_wp_error( $definition ) ) {

                return $reject(
                        array(
                                array(
                                        'code'    => $definition->get_error_code(),
                                        'message' => $definition->get_error_message(),
                                ),
                        )
                );
        }

        /*
         * Todas las adopciones del Engine se serializan.
         *
         * Esto convierte la comprobación de duplicados y la posterior
         * aceptación en una única sección crítica.
         */
        $lock_name = 'bitacora_profile_adoption';

        $lock_acquired = $wpdb->get_var(
                $wpdb->prepare(
                        'SELECT GET_LOCK(%s, %d)',
                        $lock_name,
                        10
                )
        );

        if ( '1' !== (string) $lock_acquired ) {

                return $reject(
                        array(
                                array(
                                        'code'    => 'bitacora_profile_adoption_lock_unavailable',
                                        'message' => 'No se pudo adquirir el bloqueo de adopción de perfiles.',
                                ),
                        )
                );
        }

        $release_lock = static function () use ( $wpdb, $lock_name ) {

                return $wpdb->get_var(
                        $wpdb->prepare(
                                'SELECT RELEASE_LOCK(%s)',
                                $lock_name
                        )
                );
        };

        $transaction_started = false;
        $post_id             = 0;

        $rollback = static function ( $error ) use (
                $wpdb,
                $reject,
                $release_lock,
                &$transaction_started,
                &$post_id
        ) {

                if ( $transaction_started ) {
                        $wpdb->query( 'ROLLBACK' );
                        $transaction_started = false;
                }

                if ( $post_id > 0 ) {
                        clean_post_cache( $post_id );
                }

                $release_lock();

                return $reject(
                        array(
                                $error,
                        )
                );
        };

        /*
         * posts y postmeta son InnoDB en los entornos soportados.
         * Desde aquí, REJECT revierte físicamente toda persistencia
         * realizada por esta adopción.
         */
        $transaction_result = $wpdb->query(
                'START TRANSACTION'
        );

        if ( false === $transaction_result ) {

                $release_lock();

                return $reject(
                        array(
                                array(
                                        'code'    => 'bitacora_profile_transaction_not_started',
                                        'message' => 'No se pudo iniciar la transacción de adopción.',
                                ),
                        )
                );
        }

        $transaction_started = true;

        /*
         * Debe comprobarse dentro del lock.
         *
         * Una comprobación anterior permitiría que dos adopciones
         * concurrentes aceptaran candidatos funcionalmente duplicados.
         */
        $inspection = bitacora_inspect_profile_candidate(
                $candidate
        );

        if ( ! empty( $inspection['errors'] ) ) {

                return $rollback(
                        array(
                                'code'    => 'bitacora_profile_library_check_failed',
                                'message' => 'No se pudo comparar el candidato con la biblioteca.',
                                'data'    => $inspection['errors'],
                        )
                );
        }

        if ( ! empty( $inspection['duplicate'] ) ) {

                return $rollback(
                        array(
                                'code'    => 'bitacora_profile_duplicate',
                                'matches' => $inspection['duplicate_matches'],
                        )
                );
        }

        if ( ! empty( $inspection['name_collision'] ) ) {

                return $rollback(
                        array(
                                'code'    => 'bitacora_profile_label_exists',
                                'message' => 'Ya existe un Profile con ese nombre.',
                                'matches' => $inspection['name_collision_matches'],
                        )
                );
        }

        /*
         * Similaridad no es error ni duplicación.
         *
         * Sin autorización explícita se interrumpe la operación antes de
         * generar UUID o persistir cualquier Profile. El productor puede
         * mostrar la advertencia y, si el operador decide continuar, repetir
         * la adopción con allow_similar=true.
         */
        if (
                ! empty( $inspection['similar'] )
                && ! $allow_similar
        ) {

                if ( $transaction_started ) {
                        $wpdb->query( 'ROLLBACK' );
                        $transaction_started = false;
                }

                $release_lock();

                return array(
                        'accepted'             => false,
                        'confirmation_required' => true,
                        'warnings'             => array(
                                array(
                                        'code'    => 'bitacora_profile_similar',
                                        'message' => 'Existe al menos un Profile estructuralmente similar.',
                                        'matches' => $inspection['similar_matches'],
                                ),
                        ),
                );
        }

        /*
         * La identidad técnica nace únicamente dentro de la adopción
         * ya serializada y transaccional.
         */
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

        if ( is_wp_error( $post_id ) || ! $post_id ) {

                return $rollback(
                        array(
                                'code'    => is_wp_error( $post_id )
                                        ? $post_id->get_error_code()
                                        : 'bitacora_profile_post_not_created',
                                'message' => is_wp_error( $post_id )
                                        ? $post_id->get_error_message()
                                        : 'No se pudo crear el registro transitorio del perfil.',
                        )
                );
        }

        $post_id = (int) $post_id;

        $identity_saved = add_post_meta(
                $post_id,
                '_bitacora_profile_id',
                $profile_id,
                true
        );

        if ( false === $identity_saved ) {

                return $rollback(
                        array(
                                'code'    => 'bitacora_profile_identity_not_saved',
                                'message' => 'No se pudo guardar la identidad del perfil.',
                        )
                );
        }

        $definition_saved = add_post_meta(
                $post_id,
                '_bitacora_profile_definition',
                $definition,
                true
        );

        if ( false === $definition_saved ) {

                return $rollback(
                        array(
                                'code'    => 'bitacora_profile_definition_not_saved',
                                'message' => 'No se pudo guardar la definición del perfil.',
                        )
                );
        }

        $published = wp_update_post(
                array(
                        'ID'          => $post_id,
                        'post_status' => 'publish',
                ),
                true
        );

        if ( is_wp_error( $published ) ) {

                return $rollback(
                        array(
                                'code'    => $published->get_error_code(),
                                'message' => $published->get_error_message(),
                        )
                );
        }

        if ( (int) $published !== $post_id ) {

                return $rollback(
                        array(
                                'code'    => 'bitacora_profile_not_accepted',
                                'message' => 'No se pudo aceptar el perfil.',
                        )
                );
        }

        /*
         * Verificación final desde la biblioteca aceptada.
         * La conexión actual ve sus propias escrituras todavía no
         * confirmadas por COMMIT.
         */
        $accepted_profile = false;

        foreach ( bitacora_get_profile_library() as $profile ) {

                if ( $profile_id === $profile['id'] ) {
                        $accepted_profile = $profile;
                        break;
                }
        }

        if ( ! is_array( $accepted_profile ) ) {

                return $rollback(
                        array(
                                'code'    => 'bitacora_profile_not_in_library',
                                'message' => 'El perfil aceptado no pudo resolverse desde la biblioteca.',
                        )
                );
        }

        $committed = $wpdb->query(
                'COMMIT'
        );

        if ( false === $committed ) {

                return $rollback(
                        array(
                                'code'    => 'bitacora_profile_commit_failed',
                                'message' => 'No se pudo confirmar la adopción del perfil.',
                        )
                );
        }

        $transaction_started = false;

        /*
         * Una vez confirmado COMMIT, el Perfil ya existe.
         * RELEASE_LOCK no forma parte de su identidad ni aceptación.
         * La conexión también liberará el advisory lock al cerrarse.
         */
        $release_lock();

        $result = array(
                'accepted' => true,
                'profile'  => array(
                        'id'    => $profile_id,
                        'label' => $label,
                ),
        );

        if ( ! empty( $inspection['similar_matches'] ) ) {

                $result['warnings'] = array(
                        array(
                                'code'    => 'bitacora_profile_similar',
                                'message' => 'Existe al menos un Profile estructuralmente similar.',
                                'matches' => $inspection['similar_matches'],
                        ),
                );
        }

        return $result;
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
 * - in_use
 * - deletable
 * - errors
 *
 * "collision" es un estado diagnóstico excepcional: una misma identidad
 * aparece en más de una fuente y por lo tanto no representa un perfil
 * resoluble.
 */
/**
 * Devuelve la biblioteca resoluble de Perfiles conocida por Bitácora.
 *
 * Esta vista contiene sólo identidad, denominación humana y secciones
 * complementarias. No incorpora estado de instancia ni propiedades de UI.
 *
 * Durante la transición incluye tanto perfiles bundled como stored
 * actualmente resolubles.
 */
function bitacora_get_profile_library() {

        $identities = array();

        foreach ( bitacora_get_bundled_profile_ids() as $profile_id ) {
                $identities[ $profile_id ] = true;
        }

        $stored_posts = get_posts(
                array(
                        'post_type'      => 'bitacora_profile',
                        'post_status'    => 'publish',
                        'posts_per_page' => -1,
                        'fields'         => 'ids',
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

                $identities[ $profile_id ] = true;
        }

        $library = array();

        foreach ( array_keys( $identities ) as $profile_id ) {

                $profile = bitacora_load_profile( $profile_id );

                if ( ! is_array( $profile ) ) {
                        continue;
                }

                $label = isset( $profile['label'] )
                        ? trim( (string) $profile['label'] )
                        : '';

                $sections = isset( $profile['sections'] )
                        && is_array( $profile['sections'] )
                                ? $profile['sections']
                                : array();

                $library[] = array(
                        'id'       => $profile_id,
                        'label'    => $label,
                        'sections' => $sections,
                );
        }

        usort(
                $library,
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

        return $library;
}

/**
 * Indica si una identidad pertenece a la biblioteca de Profiles aceptados.
 */
function bitacora_profile_library_contains( $profile_id ) {

        if ( ! is_string( $profile_id ) ) {
                return false;
        }

        $profile_id = strtolower( trim( $profile_id ) );

        if ( ! wp_is_uuid( $profile_id, 4 ) ) {
                return false;
        }

        foreach ( bitacora_get_profile_library() as $profile ) {

                if (
                        isset( $profile['id'] )
                        && $profile_id === $profile['id']
                ) {
                        return true;
                }
        }

        return false;
}



/**
 * Normaliza texto para comparaciones de identidad humana/similitud.
 *
 * Conserva acentos. Sólo elimina espacios exteriores y neutraliza case.
 */
function bitacora_normalize_profile_comparison_text( $value ) {

        $value = trim( (string) $value );

        if ( function_exists( 'mb_strtolower' ) ) {
                return mb_strtolower( $value, 'UTF-8' );
        }

        return strtolower( $value );
}


/**
 * Construye la representación canónica comparable de una definición.
 *
 * Incluye exclusivamente propiedades pertenecientes al contrato Profile.
 * Excluye id, label, role y cualquier dato runtime/WordPress.
 *
 * La clave técnica de sections/classes se deriva del slug, ya que el slug
 * constituye la identidad estructural materializada.
 */
function bitacora_get_canonical_profile_definition( $definition ) {

        $prepared = bitacora_prepare_profile_definition( $definition );

        if ( is_wp_error( $prepared ) ) {
                return $prepared;
        }

        $core_keys = array(
                'name',
                'singular',
                'plural',
                'subtitle',
                'new_label',
                'feature_file',
                'feature_location',
                'feature_comments',
        );

        $section_keys = array(
                'name',
                'slug',
                'singular',
                'plural',
                'subtitle',
                'new_label',
                'order',
                'area',
                'state',
                'feature_file',
                'feature_location',
                'feature_comments',
        );

        $class_keys = array(
                'name',
                'slug',
                'scope',
                'scope_id',
                'order',
                'state',
        );

        /*
         * Core: comparar valores efectivos.
         *
         * Un override omitido y el mismo valor expresado explícitamente
         * representan la misma estructura funcional.
         */
        $core_effective = array_replace(
                bitacora_get_default_core_section_definition(),
                $prepared['core']
        );

        $core = array();

        foreach ( $core_keys as $key ) {

                if ( ! array_key_exists( $key, $core_effective ) ) {
                        continue;
                }

                $value = $core_effective[ $key ];

                if (
                        in_array(
                                $key,
                                array(
                                        'feature_file',
                                        'feature_location',
                                        'feature_comments',
                                ),
                                true
                        )
                ) {
                        $value = (bool) $value;
                } else {
                        $value = trim( (string) $value );
                }

                $core[ $key ] = $value;
        }

        $sections = array();

        foreach ( $prepared['sections'] as $section ) {

                if (
                        ! is_array( $section )
                        || empty( $section['slug'] )
                ) {
                        continue;
                }

                $canonical = array();

                foreach ( $section_keys as $key ) {

                        if ( ! array_key_exists( $key, $section ) ) {

                                /*
                                 * En runtime una feature sin termmeta se
                                 * interpreta como desactivada.
                                 *
                                 * Por lo tanto omitir false y declararlo
                                 * explícitamente representan la misma
                                 * estructura funcional.
                                 */
                                if (
                                        in_array(
                                                $key,
                                                array(
                                                        'feature_file',
                                                        'feature_location',
                                                        'feature_comments',
                                                ),
                                                true
                                        )
                                ) {
                                        $canonical[ $key ] = false;
                                }

                                continue;
                        }

                        $value = $section[ $key ];

                        if (
                                in_array(
                                        $key,
                                        array(
                                                'feature_file',
                                                'feature_location',
                                                'feature_comments',
                                        ),
                                        true
                                )
                        ) {
                                $value = (bool) $value;
                        } elseif ( 'order' === $key ) {
                                $value = (int) $value;
                        } elseif ( 'slug' === $key ) {
                                $value = sanitize_title( (string) $value );
                        } else {
                                $value = trim( (string) $value );
                        }

                        $canonical[ $key ] = $value;
                }

                $slug = sanitize_title(
                        (string) $section['slug']
                );

                $sections[ $slug ] = $canonical;
        }

        ksort( $sections, SORT_STRING );

        $classes = array();

        foreach ( $prepared['classes'] as $class ) {

                if (
                        ! is_array( $class )
                        || empty( $class['slug'] )
                ) {
                        continue;
                }

                $canonical = array();

                foreach ( $class_keys as $key ) {

                        if ( ! array_key_exists( $key, $class ) ) {
                                continue;
                        }

                        $value = $class[ $key ];

                        if ( 'order' === $key ) {
                                $value = (int) $value;
                        } elseif (
                                'slug' === $key
                                || 'scope_id' === $key
                        ) {
                                $value = sanitize_title(
                                        (string) $value
                                );
                        } else {
                                $value = trim( (string) $value );
                        }

                        $canonical[ $key ] = $value;
                }

                $slug = sanitize_title(
                        (string) $class['slug']
                );

                $classes[ $slug ] = $canonical;
        }

        ksort( $classes, SORT_STRING );

        return array(
                'core'     => $core,
                'sections' => $sections,
                'classes'  => $classes,
        );
}


/**
 * Calcula una huella determinista de una definición estructural.
 *
 * La huella se obtiene exclusivamente desde la representación canónica del
 * contrato Profile. No incorpora label, UUID, procedencia ni datos runtime.
 */
function bitacora_get_profile_definition_fingerprint( $definition ) {

        $canonical =
                bitacora_get_canonical_profile_definition(
                        $definition
                );

        if ( is_wp_error( $canonical ) ) {
                return $canonical;
        }

        $encoded = wp_json_encode(
                $canonical,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
        );

        if ( ! is_string( $encoded ) ) {
                return new WP_Error(
                        'bitacora_profile_definition_fingerprint_failed',
                        'No se pudo serializar la definición canónica.'
                );
        }

        return hash(
                'sha256',
                $encoded
        );
}


/**
 * Compara dos definiciones estructurales válidas.
 *
 * La comparación es puramente descriptiva:
 * no consulta runtime, uso histórico ni permisos y no modifica nada.
 *
 * Identidad técnica:
 * - sección: slug;
 * - Tipo: slug.
 *
 * Por eso un cambio de slug aparece como removed + added, mientras que
 * cambios de nombre, orden, área, estado o features aparecen como modified.
 */
function bitacora_compare_profile_definitions(
        $before_definition,
        $after_definition
) {

        $before_validation =
                bitacora_validate_profile_definition(
                        $before_definition
                );

        if ( empty( $before_validation['valid'] ) ) {
                return new WP_Error(
                        'bitacora_structure_before_invalid',
                        'La definición estructural anterior no es válida.',
                        isset( $before_validation['errors'] )
                                ? $before_validation['errors']
                                : array()
                );
        }

        $after_validation =
                bitacora_validate_profile_definition(
                        $after_definition
                );

        if ( empty( $after_validation['valid'] ) ) {
                return new WP_Error(
                        'bitacora_structure_after_invalid',
                        'La definición estructural propuesta no es válida.',
                        isset( $after_validation['errors'] )
                                ? $after_validation['errors']
                                : array()
                );
        }

        $before =
                bitacora_get_canonical_profile_definition(
                        $before_definition
                );

        if ( is_wp_error( $before ) ) {
                return $before;
        }

        $after =
                bitacora_get_canonical_profile_definition(
                        $after_definition
                );

        if ( is_wp_error( $after ) ) {
                return $after;
        }

        $before_fingerprint =
                bitacora_get_profile_definition_fingerprint(
                        $before
                );

        if ( is_wp_error( $before_fingerprint ) ) {
                return $before_fingerprint;
        }

        $after_fingerprint =
                bitacora_get_profile_definition_fingerprint(
                        $after
                );

        if ( is_wp_error( $after_fingerprint ) ) {
                return $after_fingerprint;
        }

        /*
         * Diferencias de propiedades de una misma entidad.
         *
         * exists permite distinguir una propiedad ausente de otra
         * explícitamente presente con null, false, 0 o cadena vacía.
         */
        $diff_properties = static function (
                $before_entity,
                $after_entity
        ) {

                $keys = array_unique(
                        array_merge(
                                array_keys( $before_entity ),
                                array_keys( $after_entity )
                        )
                );

                sort( $keys, SORT_STRING );

                $changes = array();

                foreach ( $keys as $key ) {

                        $before_exists =
                                array_key_exists(
                                        $key,
                                        $before_entity
                                );

                        $after_exists =
                                array_key_exists(
                                        $key,
                                        $after_entity
                                );

                        $before_value = $before_exists
                                ? $before_entity[ $key ]
                                : null;

                        $after_value = $after_exists
                                ? $after_entity[ $key ]
                                : null;

                        if (
                                $before_exists === $after_exists
                                && $before_value === $after_value
                        ) {
                                continue;
                        }

                        $changes[ $key ] = array(
                                'before_exists' => $before_exists,
                                'after_exists'  => $after_exists,
                                'before'        => $before_value,
                                'after'         => $after_value,
                        );
                }

                return $changes;
        };

        $compare_entities = static function (
                $before_entities,
                $after_entities
        ) use ( $diff_properties ) {

                $result = array(
                        'added'    => array(),
                        'removed'  => array(),
                        'modified' => array(),
                );

                foreach ( $after_entities as $slug => $entity ) {

                        if ( ! array_key_exists( $slug, $before_entities ) ) {
                                $result['added'][ $slug ] = $entity;
                                continue;
                        }

                        $changes = $diff_properties(
                                $before_entities[ $slug ],
                                $entity
                        );

                        if ( ! empty( $changes ) ) {
                                $result['modified'][ $slug ] = array(
                                        'changes' => $changes,
                                        'before'  => $before_entities[ $slug ],
                                        'after'   => $entity,
                                );
                        }
                }

                foreach ( $before_entities as $slug => $entity ) {

                        if ( ! array_key_exists( $slug, $after_entities ) ) {
                                $result['removed'][ $slug ] = $entity;
                        }
                }

                ksort( $result['added'], SORT_STRING );
                ksort( $result['removed'], SORT_STRING );
                ksort( $result['modified'], SORT_STRING );

                return $result;
        };

        $core_changes =
                $diff_properties(
                        $before['core'],
                        $after['core']
                );

        $sections =
                $compare_entities(
                        $before['sections'],
                        $after['sections']
                );

        $classes =
                $compare_entities(
                        $before['classes'],
                        $after['classes']
                );

        return array(
                'equal' => (
                        $before === $after
                        && hash_equals(
                                $before_fingerprint,
                                $after_fingerprint
                        )
                ),

                'before_fingerprint' => $before_fingerprint,
                'after_fingerprint'  => $after_fingerprint,

                'core' => array(
                        'modified' => ! empty( $core_changes ),
                        'changes'  => $core_changes,
                ),

                'sections' => $sections,
                'classes'  => $classes,
        );
}


/**
 * Devuelve el uso histórico actual de la estructura materializada.
 *
 * No interpreta política. Sólo observa referencias persistidas:
 * - ítems relacionados con secciones;
 * - ítems relacionados con Tipos;
 * - uso efectivo de features documentales por sección.
 */
function bitacora_get_instance_structure_usage() {

        $sections = get_terms(
                array(
                        'taxonomy'   => 'bitacora_section',
                        'hide_empty' => false,
                )
        );

        if ( is_wp_error( $sections ) ) {
                return $sections;
        }

        $classes = get_terms(
                array(
                        'taxonomy'   => 'bitacora_class',
                        'hide_empty' => false,
                )
        );

        if ( is_wp_error( $classes ) ) {
                return $classes;
        }

        $usage = array(
                'sections' => array(),
                'classes'  => array(),
        );

        foreach ( $sections as $term ) {

                $item_ids = get_objects_in_term(
                        $term->term_id,
                        'bitacora_section'
                );

                if ( is_wp_error( $item_ids ) ) {
                        return $item_ids;
                }

                $item_ids = array_values(
                        array_unique(
                                array_map(
                                        'intval',
                                        $item_ids
                                )
                        )
                );

                sort( $item_ids, SORT_NUMERIC );

                $file_items     = array();
                $location_items = array();
                $comment_items  = array();
                $comment_count  = 0;

                foreach ( $item_ids as $item_id ) {

                        if (
                                absint(
                                        get_post_meta(
                                                $item_id,
                                                'bitacora_item_file',
                                                true
                                        )
                                ) > 0
                        ) {
                                $file_items[] = $item_id;
                        }

                        if (
                                '' !== trim(
                                        (string) get_post_meta(
                                                $item_id,
                                                'bitacora_item_location',
                                                true
                                        )
                                )
                        ) {
                                $location_items[] = $item_id;
                        }

                        $item_comments = (int) get_comments(
                                array(
                                        'post_id' => $item_id,
                                        'status'  => 'all',
                                        'count'   => true,
                                )
                        );

                        if ( $item_comments > 0 ) {
                                $comment_items[] = $item_id;
                                $comment_count  += $item_comments;
                        }
                }

                $usage['sections'][ $term->slug ] = array(
                        'term_id'    => (int) $term->term_id,
                        'item_count' => count( $item_ids ),
                        'item_ids'   => $item_ids,

                        'features' => array(
                                'file' => array(
                                        'count'    => count( $file_items ),
                                        'item_ids' => $file_items,
                                ),

                                'location' => array(
                                        'count'    => count( $location_items ),
                                        'item_ids' => $location_items,
                                ),

                                'comments' => array(
                                        'count'         => count( $comment_items ),
                                        'item_ids'      => $comment_items,
                                        'comment_count' => $comment_count,
                                ),
                        ),
                );
        }

        foreach ( $classes as $term ) {

                $item_ids = get_objects_in_term(
                        $term->term_id,
                        'bitacora_class'
                );

                if ( is_wp_error( $item_ids ) ) {
                        return $item_ids;
                }

                $item_ids = array_values(
                        array_unique(
                                array_map(
                                        'intval',
                                        $item_ids
                                )
                        )
                );

                sort( $item_ids, SORT_NUMERIC );

                $usage['classes'][ $term->slug ] = array(
                        'term_id'    => (int) $term->term_id,
                        'item_count' => count( $item_ids ),
                        'item_ids'   => $item_ids,
                );
        }

        ksort( $usage['sections'], SORT_STRING );
        ksort( $usage['classes'], SORT_STRING );

        return $usage;
}


/**
 * Valida una PROPUESTA de evolución sobre una instancia viva.
 *
 * La función no aplica cambios.
 *
 * Política:
 * - PRE debe seguir describiendo CURRENT;
 * - PROPUESTA debe ser una definición Profile válida;
 * - agregar estructura está permitido;
 * - renombrar, reordenar, cambiar área/estado y presentation está permitido;
 * - una sección o Tipo que ya existía en PRE no puede eliminarse;
 * - para retirarlo debe conservarse su identidad y cambiar su state;
 * - un Tipo referenciado no puede cambiar de scope_id;
 * - una feature puede activarse libremente;
 * - una feature sólo puede desactivarse si no existe contenido histórico que
 *   dependa de ella en esa sección.
 *
 * La política se recalcula contra uso runtime fresco para que una propuesta
 * pueda volver a comprobarse justo antes de aplicar.
 */
function bitacora_validate_instance_structure_proposal(
        $pre,
        $proposal_definition
) {

        $report = array(
                'valid'                => false,
                'errors'               => array(),
                'comparison'           => null,
                'proposal'             => null,
                'proposal_fingerprint' => '',
        );

        $precondition =
                bitacora_check_instance_structure_precondition(
                        $pre
                );

        if ( is_wp_error( $precondition ) ) {

                $report['errors'][] = array(
                        'code'    => $precondition->get_error_code(),
                        'message' => $precondition->get_error_message(),
                        'data'    => $precondition->get_error_data(),
                );

                return $report;
        }

        if ( empty( $precondition['matches'] ) ) {

                $report['errors'][] = array(
                        'code'    => 'bitacora_instance_structure_drift',
                        'message' => 'La estructura de la instancia cambió desde PRE.',
                        'data'    => array(
                                'expected_fingerprint' =>
                                        $precondition['expected']['fingerprint'],

                                'current_fingerprint' =>
                                        $precondition['current']['fingerprint'],
                        ),
                );

                return $report;
        }

        $validation =
                bitacora_validate_profile_definition(
                        $proposal_definition
                );

        if ( empty( $validation['valid'] ) ) {

                $report['errors'][] = array(
                        'code'    => 'bitacora_instance_proposal_invalid',
                        'message' => 'La PROPUESTA estructural no es válida.',
                        'data'    => isset( $validation['errors'] )
                                ? $validation['errors']
                                : array(),
                );

                return $report;
        }

        $proposal =
                bitacora_get_canonical_profile_definition(
                        $proposal_definition
                );

        if ( is_wp_error( $proposal ) ) {

                $report['errors'][] = array(
                        'code'    => $proposal->get_error_code(),
                        'message' => $proposal->get_error_message(),
                        'data'    => $proposal->get_error_data(),
                );

                return $report;
        }

        $comparison =
                bitacora_compare_profile_definitions(
                        $precondition['expected']['definition'],
                        $proposal
                );

        if ( is_wp_error( $comparison ) ) {

                $report['errors'][] = array(
                        'code'    => $comparison->get_error_code(),
                        'message' => $comparison->get_error_message(),
                        'data'    => $comparison->get_error_data(),
                );

                return $report;
        }

        $usage = bitacora_get_instance_structure_usage();

        if ( is_wp_error( $usage ) ) {

                $report['errors'][] = array(
                        'code'    => $usage->get_error_code(),
                        'message' => $usage->get_error_message(),
                        'data'    => $usage->get_error_data(),
                );

                return $report;
        }

        $report['comparison'] = $comparison;
        $report['proposal']   = $proposal;

        $proposal_fingerprint =
                bitacora_get_profile_definition_fingerprint(
                        $proposal
                );

        if ( is_wp_error( $proposal_fingerprint ) ) {

                $report['errors'][] = array(
                        'code'    => $proposal_fingerprint->get_error_code(),
                        'message' => $proposal_fingerprint->get_error_message(),
                        'data'    => $proposal_fingerprint->get_error_data(),
                );

                return $report;
        }

        $report['proposal_fingerprint'] =
                $proposal_fingerprint;

        /*
         * Eliminación de estructura PRE.
         *
         * Aunque una entidad no tenga contenido asociado, eliminarla
         * destruiría su identidad física WordPress (term_id) y volvería
         * imposible garantizar un rollback exacto.
         *
         * Para retirar estructura existente debe conservarse la entidad
         * y cambiar su state. Las únicas entidades que podrán eliminarse
         * durante rollback serán las creadas por ese mismo apply.
         */
        foreach (
                $comparison['sections']['removed']
                as $slug => $section
        ) {

                $section_usage =
                        isset( $usage['sections'][ $slug ] )
                                ? $usage['sections'][ $slug ]
                                : array(
                                        'item_count' => 0,
                                        'item_ids'   => array(),
                                );

                if ( $section_usage['item_count'] > 0 ) {

                        $report['errors'][] = array(
                                'code'    => 'bitacora_instance_section_in_use',
                                'message' => sprintf(
                                        'La sección "%s" no puede eliminarse porque está usada.',
                                        $section['name']
                                ),
                                'data'    => array(
                                        'slug'       => $slug,
                                        'item_count' => $section_usage['item_count'],
                                        'item_ids'   => $section_usage['item_ids'],
                                ),
                        );

                        continue;
                }

                $report['errors'][] = array(
                        'code'    => 'bitacora_instance_section_removal_not_supported',
                        'message' => sprintf(
                                'La sección "%s" ya pertenece a PRE y no puede eliminarse; debe conservarse y deshabilitarse.',
                                $section['name']
                        ),
                        'data'    => array(
                                'slug'       => $slug,
                                'item_count' => 0,
                                'item_ids'   => array(),
                        ),
                );
        }

        foreach (
                $comparison['classes']['removed']
                as $slug => $class
        ) {

                $class_usage =
                        isset( $usage['classes'][ $slug ] )
                                ? $usage['classes'][ $slug ]
                                : array(
                                        'item_count' => 0,
                                        'item_ids'   => array(),
                                );

                if ( $class_usage['item_count'] > 0 ) {

                        $report['errors'][] = array(
                                'code'    => 'bitacora_instance_class_in_use',
                                'message' => sprintf(
                                        'El Tipo "%s" no puede eliminarse porque está usado.',
                                        $class['name']
                                ),
                                'data'    => array(
                                        'slug'       => $slug,
                                        'item_count' => $class_usage['item_count'],
                                        'item_ids'   => $class_usage['item_ids'],
                                ),
                        );

                        continue;
                }

                $report['errors'][] = array(
                        'code'    => 'bitacora_instance_class_removal_not_supported',
                        'message' => sprintf(
                                'El Tipo "%s" ya pertenece a PRE y no puede eliminarse; debe conservarse y deshabilitarse.',
                                $class['name']
                        ),
                        'data'    => array(
                                'slug'       => $slug,
                                'item_count' => 0,
                                'item_ids'   => array(),
                        ),
                );
        }

        /*
         * Un Tipo usado puede cambiar nombre, orden o estado, pero no puede
         * pasar a otra sección porque rompería la relación histórica
         * sección + Tipo de los ítems existentes.
         */
        foreach (
                $comparison['classes']['modified']
                as $slug => $modified
        ) {

                if (
                        ! isset(
                                $modified['changes']['scope_id']
                        )
                ) {
                        continue;
                }

                $class_usage =
                        isset( $usage['classes'][ $slug ] )
                                ? $usage['classes'][ $slug ]
                                : array(
                                        'item_count' => 0,
                                        'item_ids'   => array(),
                                );

                if ( $class_usage['item_count'] > 0 ) {

                        $report['errors'][] = array(
                                'code'    => 'bitacora_instance_class_scope_in_use',
                                'message' => sprintf(
                                        'El Tipo "%s" no puede cambiar de sección porque está usado.',
                                        $modified['before']['name']
                                ),
                                'data'    => array(
                                        'slug'       => $slug,
                                        'before'     =>
                                                $modified['changes']['scope_id']['before'],
                                        'after'      =>
                                                $modified['changes']['scope_id']['after'],
                                        'item_count' => $class_usage['item_count'],
                                        'item_ids'   => $class_usage['item_ids'],
                                ),
                        );
                }
        }

        /*
         * Desactivación de features.
         *
         * También se aplica al core, cuya sección materializada es "notas".
         */
        $feature_map = array(
                'feature_file'     => 'file',
                'feature_location' => 'location',
                'feature_comments' => 'comments',
        );

        $check_feature_changes = static function (
                $entity_slug,
                $changes,
                $entity_label
        ) use (
                &$report,
                $usage,
                $feature_map
        ) {

                if (
                        ! isset(
                                $usage['sections'][ $entity_slug ]
                        )
                ) {
                        return;
                }

                foreach (
                        $feature_map
                        as $property => $feature
                ) {

                        if (
                                ! isset( $changes[ $property ] )
                        ) {
                                continue;
                        }

                        $before =
                                ! empty(
                                        $changes[ $property ]['before']
                                );

                        $after =
                                ! empty(
                                        $changes[ $property ]['after']
                                );

                        /*
                         * Activar siempre es aditivo.
                         * Mantener false tampoco requiere política.
                         */
                        if ( ! $before || $after ) {
                                continue;
                        }

                        $feature_usage =
                                $usage['sections']
                                      [ $entity_slug ]
                                      ['features']
                                      [ $feature ];

                        if ( $feature_usage['count'] <= 0 ) {
                                continue;
                        }

                        $data = array(
                                'slug'       => $entity_slug,
                                'feature'    => $feature,
                                'item_count' => $feature_usage['count'],
                                'item_ids'   => $feature_usage['item_ids'],
                        );

                        if (
                                'comments' === $feature
                                && isset(
                                        $feature_usage['comment_count']
                                )
                        ) {
                                $data['comment_count'] =
                                        $feature_usage['comment_count'];
                        }

                        $report['errors'][] = array(
                                'code'    => 'bitacora_instance_feature_in_use',
                                'message' => sprintf(
                                        'No puede desactivarse "%s" en "%s" porque existe contenido histórico que la utiliza.',
                                        $feature,
                                        $entity_label
                                ),
                                'data'    => $data,
                        );
                }
        };

        if ( ! empty( $comparison['core']['modified'] ) ) {

                $check_feature_changes(
                        'notas',
                        $comparison['core']['changes'],
                        'Bitácora'
                );
        }

        foreach (
                $comparison['sections']['modified']
                as $slug => $modified
        ) {

                $check_feature_changes(
                        $slug,
                        $modified['changes'],
                        isset( $modified['before']['name'] )
                                ? $modified['before']['name']
                                : $slug
                );
        }

        $report['valid'] =
                empty( $report['errors'] );

        return $report;
}


/**
 * Construye un plan físico de reconciliación PRE -> PROPUESTA.
 *
 * El plan es descriptivo y no modifica runtime.
 *
 * Principios:
 * - vuelve a validar PRE y PROPUESTA contra CURRENT;
 * - preserva term_id y page_id de toda identidad ya existente;
 * - sólo genera create para identidades nuevas;
 * - nunca genera delete para estructura perteneciente a PRE;
 * - cada update incluye estado físico previo para rollback;
 * - cada create declara rollback=delete_created.
 *
 * Orden:
 * 1. actualizar secciones existentes;
 * 2. crear secciones nuevas;
 * 3. actualizar Tipos existentes;
 * 4. crear Tipos nuevos;
 * 5. sincronizar páginas derivadas de secciones.
 */
function bitacora_build_instance_structure_reconciliation_plan(
        $pre,
        $proposal_definition
) {

        $validation =
                bitacora_validate_instance_structure_proposal(
                        $pre,
                        $proposal_definition
                );

        if ( empty( $validation['valid'] ) ) {
                return new WP_Error(
                        'bitacora_instance_structure_proposal_rejected',
                        'La PROPUESTA estructural no puede reconciliarse.',
                        $validation
                );
        }

        $proposal   = $validation['proposal'];
        $comparison = $validation['comparison'];

        $actions = array();

        /*
         * Devuelve los termmeta físicos administrados por la definición
         * estructural de una sección.
         */
        $section_meta_keys = array(
                'singular' =>
                        'bitacora_section_singular',
                'plural' =>
                        'bitacora_section_plural',
                'subtitle' =>
                        'bitacora_section_subtitle',
                'new_label' =>
                        'bitacora_section_new_label',
                'order' =>
                        'bitacora_section_order',
                'area' =>
                        'bitacora_section_area',
                'state' =>
                        'bitacora_section_state',
                'feature_file' =>
                        'bitacora_section_feature_file',
                'feature_location' =>
                        'bitacora_section_feature_location',
                'feature_comments' =>
                        'bitacora_section_feature_comments',
        );

        $class_meta_keys = array(
                'scope' =>
                        'bitacora_class_scope',
                'scope_id' =>
                        'bitacora_class_scope_id',
                'order' =>
                        'bitacora_class_order',
                'state' =>
                        'bitacora_class_state',
        );

        $boolean_section_keys = array(
                'feature_file',
                'feature_location',
                'feature_comments',
        );

        /*
         * Convierte una entidad canónica a los termmeta físicos que deben
         * existir después del apply.
         *
         * role no pertenece al contrato Profile, pero sí a la
         * materialización y se agrega explícitamente.
         */
        $section_target_meta = static function (
                $section,
                $role
        ) use (
                $section_meta_keys,
                $boolean_section_keys
        ) {

                $meta = array(
                        'bitacora_section_role' => $role,
                );

                foreach (
                        $section_meta_keys
                        as $property => $meta_key
                ) {

                        if (
                                ! array_key_exists(
                                        $property,
                                        $section
                                )
                        ) {
                                continue;
                        }

                        $value = $section[ $property ];

                        if (
                                in_array(
                                        $property,
                                        $boolean_section_keys,
                                        true
                                )
                        ) {
                                $value = $value ? '1' : '0';
                        } else {
                                $value = (string) $value;
                        }

                        $meta[ $meta_key ] = $value;
                }

                ksort( $meta, SORT_STRING );

                return $meta;
        };

        $class_target_meta = static function (
                $class
        ) use ( $class_meta_keys ) {

                $meta = array();

                foreach (
                        $class_meta_keys
                        as $property => $meta_key
                ) {

                        if (
                                ! array_key_exists(
                                        $property,
                                        $class
                                )
                        ) {
                                continue;
                        }

                        $meta[ $meta_key ] =
                                (string) $class[ $property ];
                }

                ksort( $meta, SORT_STRING );

                return $meta;
        };

        /*
         * Snapshot físico mínimo de un término existente.
         *
         * Sólo captura los termmeta que administra el reconciliador.
         */
        $get_term_state = static function (
                $term,
                $managed_meta_keys
        ) {

                $state = array(
                        'term_id' => (int) $term->term_id,
                        'name'    => (string) $term->name,
                        'slug'    => (string) $term->slug,
                        'meta'    => array(),
                );

                foreach (
                        $managed_meta_keys
                        as $meta_key
                ) {

                        $exists =
                                metadata_exists(
                                        'term',
                                        $term->term_id,
                                        $meta_key
                                );

                        $state['meta'][ $meta_key ] = array(
                                'exists' => $exists,
                                'value'  => $exists
                                        ? (string) get_term_meta(
                                                $term->term_id,
                                                $meta_key,
                                                true
                                        )
                                        : null,
                        );
                }

                ksort( $state['meta'], SORT_STRING );

                return $state;
        };

        $section_managed_meta_keys =
                array_values(
                        array_unique(
                                array_merge(
                                        array_values(
                                                $section_meta_keys
                                        ),
                                        array(
                                                'bitacora_section_role',
                                        )
                                )
                        )
                );

        sort(
                $section_managed_meta_keys,
                SORT_STRING
        );

        $class_managed_meta_keys =
                array_values(
                        $class_meta_keys
                );

        sort(
                $class_managed_meta_keys,
                SORT_STRING
        );

        /*
         * Core materializado: identidad fija "notas".
         */
        if ( ! empty( $comparison['core']['modified'] ) ) {

                $term = get_term_by(
                        'slug',
                        'notas',
                        'bitacora_section'
                );

                if (
                        ! $term
                        || is_wp_error( $term )
                ) {
                        return new WP_Error(
                                'bitacora_instance_core_term_missing',
                                'No se encontró el término core materializado.'
                        );
                }

                $before =
                        $get_term_state(
                                $term,
                                $section_managed_meta_keys
                        );

                $after = array(
                        'term_id' => (int) $term->term_id,
                        'name'    => (string) $proposal['core']['name'],
                        'slug'    => 'notas',
                        'meta'    =>
                                $section_target_meta(
                                        $proposal['core'],
                                        'core'
                                ),
                );

                $actions[] = array(
                        'type'     => 'section.update',
                        'identity' => 'notas',
                        'target'   => array(
                                'taxonomy' => 'bitacora_section',
                                'term_id'  => (int) $term->term_id,
                        ),
                        'before'   => $before,
                        'after'    => $after,
                        'rollback' => array(
                                'type'  => 'section.restore',
                                'state' => $before,
                        ),
                );
        }

        /*
         * Secciones complementarias existentes.
         */
        foreach (
                $comparison['sections']['modified']
                as $slug => $modified
        ) {

                $term = get_term_by(
                        'slug',
                        $slug,
                        'bitacora_section'
                );

                if (
                        ! $term
                        || is_wp_error( $term )
                ) {
                        return new WP_Error(
                                'bitacora_instance_section_term_missing',
                                sprintf(
                                        'No se encontró la sección materializada "%s".',
                                        $slug
                                )
                        );
                }

                $before =
                        $get_term_state(
                                $term,
                                $section_managed_meta_keys
                        );

                $after = array(
                        'term_id' => (int) $term->term_id,
                        'name'    =>
                                (string) $modified['after']['name'],
                        'slug'    => $slug,
                        'meta'    =>
                                $section_target_meta(
                                        $modified['after'],
                                        'section'
                                ),
                );

                $actions[] = array(
                        'type'     => 'section.update',
                        'identity' => $slug,
                        'target'   => array(
                                'taxonomy' => 'bitacora_section',
                                'term_id'  => (int) $term->term_id,
                        ),
                        'before'   => $before,
                        'after'    => $after,
                        'rollback' => array(
                                'type'  => 'section.restore',
                                'state' => $before,
                        ),
                );
        }

        /*
         * Secciones nuevas.
         */
        foreach (
                $comparison['sections']['added']
                as $slug => $section
        ) {

                $existing = get_term_by(
                        'slug',
                        $slug,
                        'bitacora_section'
                );

                if ( $existing && ! is_wp_error( $existing ) ) {
                        return new WP_Error(
                                'bitacora_instance_section_create_conflict',
                                sprintf(
                                        'La nueva sección "%s" ya existe físicamente.',
                                        $slug
                                ),
                                array(
                                        'slug'    => $slug,
                                        'term_id' =>
                                                (int) $existing->term_id,
                                )
                        );
                }

                $actions[] = array(
                        'type'     => 'section.create',
                        'identity' => $slug,
                        'target'   => array(
                                'taxonomy' => 'bitacora_section',
                                'term_id'  => null,
                        ),
                        'before'   => null,
                        'after'    => array(
                                'term_id' => null,
                                'name'    =>
                                        (string) $section['name'],
                                'slug'    => $slug,
                                'meta'    =>
                                        $section_target_meta(
                                                $section,
                                                'section'
                                        ),
                        ),
                        'rollback' => array(
                                'type' =>
                                        'section.delete_created',
                        ),
                );
        }

        /*
         * Tipos existentes.
         */
        foreach (
                $comparison['classes']['modified']
                as $slug => $modified
        ) {

                $term = get_term_by(
                        'slug',
                        $slug,
                        'bitacora_class'
                );

                if (
                        ! $term
                        || is_wp_error( $term )
                ) {
                        return new WP_Error(
                                'bitacora_instance_class_term_missing',
                                sprintf(
                                        'No se encontró el Tipo materializado "%s".',
                                        $slug
                                )
                        );
                }

                $before =
                        $get_term_state(
                                $term,
                                $class_managed_meta_keys
                        );

                $after = array(
                        'term_id' => (int) $term->term_id,
                        'name'    =>
                                (string) $modified['after']['name'],
                        'slug'    => $slug,
                        'meta'    =>
                                $class_target_meta(
                                        $modified['after']
                                ),
                );

                $actions[] = array(
                        'type'     => 'class.update',
                        'identity' => $slug,
                        'target'   => array(
                                'taxonomy' => 'bitacora_class',
                                'term_id'  => (int) $term->term_id,
                        ),
                        'before'   => $before,
                        'after'    => $after,
                        'rollback' => array(
                                'type'  => 'class.restore',
                                'state' => $before,
                        ),
                );
        }

        /*
         * Tipos nuevos.
         */
        foreach (
                $comparison['classes']['added']
                as $slug => $class
        ) {

                $existing = get_term_by(
                        'slug',
                        $slug,
                        'bitacora_class'
                );

                if ( $existing && ! is_wp_error( $existing ) ) {
                        return new WP_Error(
                                'bitacora_instance_class_create_conflict',
                                sprintf(
                                        'El nuevo Tipo "%s" ya existe físicamente.',
                                        $slug
                                ),
                                array(
                                        'slug'    => $slug,
                                        'term_id' =>
                                                (int) $existing->term_id,
                                )
                        );
                }

                $actions[] = array(
                        'type'     => 'class.create',
                        'identity' => $slug,
                        'target'   => array(
                                'taxonomy' => 'bitacora_class',
                                'term_id'  => null,
                        ),
                        'before'   => null,
                        'after'    => array(
                                'term_id' => null,
                                'name'    =>
                                        (string) $class['name'],
                                'slug'    => $slug,
                                'meta'    =>
                                        $class_target_meta(
                                                $class
                                        ),
                        ),
                        'rollback' => array(
                                'type' =>
                                        'class.delete_created',
                        ),
                );
        }

        /*
         * Páginas derivadas.
         *
         * Sólo se consideran core + secciones presentes en PROPUESTA.
         * Una página ya existente nunca se elimina:
         * - state=active  -> publish;
         * - state=hidden  -> publish si la página ya existe;
         * - state=archived -> draft.
         *
         * hidden retira navegación y creación, pero conserva la URL
         * histórica de una sección que ya fue materializada.
         *
         * Si una sección no activa nunca tuvo página, no se crea una
         * página innecesaria.
         */
        $proposal_sections = array(
                'notas' => array_merge(
                        $proposal['core'],
                        array(
                                'slug'  => 'notas',
                                'state' => 'active',
                        )
                ),
        );

        foreach (
                $proposal['sections']
                as $slug => $section
        ) {
                $proposal_sections[ $slug ] =
                        $section;
        }

        ksort(
                $proposal_sections,
                SORT_STRING
        );

        foreach (
                $proposal_sections
                as $slug => $section
        ) {

                $state = isset( $section['state'] )
                        ? (string) $section['state']
                        : 'active';

                $status = in_array(
                        $state,
                        array(
                                'active',
                                'hidden',
                        ),
                        true
                )
                        ? 'publish'
                        : 'draft';

                $title =
                        array_key_exists(
                                'plural',
                                $section
                        )
                                ? (string) $section['plural']
                                : (string) $section['name'];

                $content = sprintf(
                        '[bitacora_section slug="%s"]',
                        $slug
                );

                $page = get_page_by_path(
                        $slug,
                        OBJECT,
                        'page'
                );

                /*
                 * Una sección nueva no puede apropiarse de una página
                 * WordPress preexistente sólo porque comparte su slug.
                 *
                 * Para las secciones que ya pertenecían a PRE, la página
                 * homónima forma parte de la materialización conocida y
                 * puede reconciliarse conservando su post_id.
                 */
                $is_new_section =
                        isset(
                                $comparison['sections']['added'][ $slug ]
                        );

                if (
                        $page
                        && $is_new_section
                ) {
                        return new WP_Error(
                                'bitacora_instance_page_create_conflict',
                                sprintf(
                                        'La nueva sección "%s" requiere una página cuyo slug ya está ocupado.',
                                        $slug
                                ),
                                array(
                                        'slug'    => $slug,
                                        'post_id' => (int) $page->ID,
                                )
                        );
                }

                /*
                 * Sección no activa sin página previa: no crear nada.
                 *
                 * El publish de hidden sólo preserva una página histórica
                 * ya existente; no materializa una página para una sección
                 * que nunca estuvo activa.
                 */
                if (
                        ! $page
                        && 'active' !== $state
                ) {
                        continue;
                }

                if ( ! $page ) {

                        $actions[] = array(
                                'type'     => 'page.create',
                                'identity' => $slug,
                                'target'   => array(
                                        'post_type' => 'page',
                                        'post_id'   => null,
                                ),
                                'before'   => null,
                                'after'    => array(
                                        'post_id'      => null,
                                        'post_name'    => $slug,
                                        'post_title'   => $title,
                                        'post_content' => $content,
                                        'post_status'  => $status,
                                ),
                                'rollback' => array(
                                        'type' =>
                                                'page.delete_created',
                                ),
                        );

                        continue;
                }

                $page_before = array(
                        'post_id'      => (int) $page->ID,
                        'post_name'    => (string) $page->post_name,
                        'post_title'   => (string) $page->post_title,
                        'post_content' => (string) $page->post_content,
                        'post_status'  => (string) $page->post_status,
                );

                $page_after = array(
                        'post_id'      => (int) $page->ID,
                        'post_name'    => $slug,
                        'post_title'   => $title,
                        'post_content' => $content,
                        'post_status'  => $status,
                );

                if ( $page_before === $page_after ) {
                        continue;
                }

                $actions[] = array(
                        'type'     => 'page.update',
                        'identity' => $slug,
                        'target'   => array(
                                'post_type' => 'page',
                                'post_id'   => (int) $page->ID,
                        ),
                        'before'   => $page_before,
                        'after'    => $page_after,
                        'rollback' => array(
                                'type'  => 'page.restore',
                                'state' => $page_before,
                        ),
                );
        }

        /*
         * Numeración estable útil para journal y diagnóstico.
         */
        foreach ( $actions as $index => &$action ) {
                $action['sequence'] = $index + 1;
        }

        unset( $action );

        return array(
                'pre_fingerprint' =>
                        $pre['fingerprint'],

                'proposal_fingerprint' =>
                        $validation['proposal_fingerprint'],

                'comparison' =>
                        $comparison,

                'actions' =>
                        $actions,

                'action_count' =>
                        count( $actions ),
        );
}


/**
 * Verifica que el caller se encuentre dentro de la sección crítica
 * de mantenimiento de la instancia.
 *
 * @return true|WP_Error
 */
function bitacora_check_instance_structure_execution_context(
        $owner_user_id
) {

        $owner_user_id = absint(
                $owner_user_id
        );

        if ( ! bitacora_is_configured() ) {
                return new WP_Error(
                        'bitacora_instance_not_configured',
                        'Bitácora no tiene una instancia configurada.'
                );
        }

        if (
                'paused'
                !== bitacora_get_instance_runtime_state()
        ) {
                return new WP_Error(
                        'bitacora_instance_structure_not_paused',
                        'La reconciliación estructural requiere que la instancia esté en PAUSA.'
                );
        }

        $current_owner =
                bitacora_get_instance_maintenance_owner();

        if (
                ! $owner_user_id
                || $owner_user_id !== $current_owner
        ) {
                return new WP_Error(
                        'bitacora_instance_maintenance_owner_mismatch',
                        'El usuario no es el responsable actual del mantenimiento.',
                        array(
                                'owner_user_id' =>
                                        $current_owner,
                        )
                );
        }

        $lock_owned =
                bitacora_instance_maintenance_lock_is_owned();

        if ( is_wp_error( $lock_owned ) ) {
                return $lock_owned;
        }

        if ( ! $lock_owned ) {
                return new WP_Error(
                        'bitacora_instance_maintenance_lock_required',
                        'La reconciliación requiere poseer el bloqueo de mantenimiento.'
                );
        }

        return true;
}


/**
 * Escribe y verifica el estado físico de un término existente.
 *
 * El campo meta utiliza la forma:
 *
 * meta_key => array(
 *     'exists' => bool,
 *     'value'  => string|null,
 * )
 *
 * Esto permite restaurar con precisión tanto valores como ausencia de meta.
 *
 * @return true|WP_Error
 */
function bitacora_write_instance_structure_term_state(
        $taxonomy,
        $term_id,
        $state
) {

        $term_id = absint(
                $term_id
        );

        if (
                ! $term_id
                || ! is_array( $state )
                || ! isset(
                        $state['name'],
                        $state['slug'],
                        $state['meta']
                )
                || ! is_array( $state['meta'] )
        ) {
                return new WP_Error(
                        'bitacora_instance_term_state_invalid',
                        'El estado físico del término no es válido.'
                );
        }

        $existing = get_term(
                $term_id,
                $taxonomy
        );

        if (
                ! $existing
                || is_wp_error( $existing )
        ) {
                return new WP_Error(
                        'bitacora_instance_term_missing',
                        'No se encontró el término estructural esperado.',
                        array(
                                'taxonomy' => $taxonomy,
                                'term_id'  => $term_id,
                        )
                );
        }

        $updated = wp_update_term(
                $term_id,
                $taxonomy,
                array(
                        'name' =>
                                (string) $state['name'],
                        'slug' =>
                                (string) $state['slug'],
                )
        );

        if ( is_wp_error( $updated ) ) {
                return $updated;
        }

        foreach (
                $state['meta']
                as $meta_key => $meta_state
        ) {

                if (
                        ! is_array( $meta_state )
                        || ! array_key_exists(
                                'exists',
                                $meta_state
                        )
                ) {
                        return new WP_Error(
                                'bitacora_instance_term_meta_state_invalid',
                                'El estado de termmeta no es válido.',
                                array(
                                        'taxonomy' => $taxonomy,
                                        'term_id'  => $term_id,
                                        'meta_key' => $meta_key,
                                )
                        );
                }

                if ( $meta_state['exists'] ) {

                        update_term_meta(
                                $term_id,
                                $meta_key,
                                (string) $meta_state['value']
                        );

                        if (
                                ! metadata_exists(
                                        'term',
                                        $term_id,
                                        $meta_key
                                )
                                || (string) get_term_meta(
                                        $term_id,
                                        $meta_key,
                                        true
                                )
                                        !== (string) $meta_state['value']
                        ) {
                                return new WP_Error(
                                        'bitacora_instance_term_meta_not_written',
                                        'No se pudo escribir termmeta estructural.',
                                        array(
                                                'taxonomy' => $taxonomy,
                                                'term_id'  => $term_id,
                                                'meta_key' => $meta_key,
                                        )
                                );
                        }

                } else {

                        delete_term_meta(
                                $term_id,
                                $meta_key
                        );

                        if (
                                metadata_exists(
                                        'term',
                                        $term_id,
                                        $meta_key
                                )
                        ) {
                                return new WP_Error(
                                        'bitacora_instance_term_meta_not_deleted',
                                        'No se pudo retirar termmeta estructural.',
                                        array(
                                                'taxonomy' => $taxonomy,
                                                'term_id'  => $term_id,
                                                'meta_key' => $meta_key,
                                        )
                                );
                        }
                }
        }

        clean_term_cache(
                $term_id,
                $taxonomy
        );

        $verified = get_term(
                $term_id,
                $taxonomy
        );

        if (
                ! $verified
                || is_wp_error( $verified )
                || (string) $verified->name
                        !== (string) $state['name']
                || (string) $verified->slug
                        !== (string) $state['slug']
        ) {
                return new WP_Error(
                        'bitacora_instance_term_state_not_verified',
                        'El término estructural no quedó en el estado esperado.',
                        array(
                                'taxonomy' => $taxonomy,
                                'term_id'  => $term_id,
                        )
                );
        }

        return true;
}


/**
 * Convierte el estado "after" del planner al formato físico verificable.
 *
 * Las claves administradas que existían en before pero desaparecen en after
 * se expresan explícitamente como exists=false.
 */
function bitacora_build_instance_structure_term_target_state(
        $before,
        $after
) {

        $state = array(
                'term_id' =>
                        isset( $after['term_id'] )
                                ? $after['term_id']
                                : null,

                'name' =>
                        (string) $after['name'],

                'slug' =>
                        (string) $after['slug'],

                'meta' =>
                        array(),
        );

        $keys = array();

        if (
                is_array( $before )
                && isset( $before['meta'] )
                && is_array( $before['meta'] )
        ) {
                $keys = array_merge(
                        $keys,
                        array_keys(
                                $before['meta']
                        )
                );
        }

        if (
                isset( $after['meta'] )
                && is_array( $after['meta'] )
        ) {
                $keys = array_merge(
                        $keys,
                        array_keys(
                                $after['meta']
                        )
                );
        }

        $keys = array_values(
                array_unique( $keys )
        );

        sort(
                $keys,
                SORT_STRING
        );

        foreach ( $keys as $meta_key ) {

                if (
                        isset( $after['meta'] )
                        && array_key_exists(
                                $meta_key,
                                $after['meta']
                        )
                ) {
                        $state['meta'][ $meta_key ] =
                                array(
                                        'exists' => true,
                                        'value'  =>
                                                (string) $after['meta'][ $meta_key ],
                                );

                } else {

                        $state['meta'][ $meta_key ] =
                                array(
                                        'exists' => false,
                                        'value'  => null,
                                );
                }
        }

        return $state;
}


/**
 * Escribe y verifica el estado físico de una página existente.
 *
 * @return true|WP_Error
 */
function bitacora_write_instance_structure_page_state(
        $post_id,
        $state
) {

        $post_id = absint(
                $post_id
        );

        if (
                ! $post_id
                || ! is_array( $state )
        ) {
                return new WP_Error(
                        'bitacora_instance_page_state_invalid',
                        'El estado físico de la página no es válido.'
                );
        }

        $existing = get_post(
                $post_id
        );

        if (
                ! $existing instanceof WP_Post
                || 'page' !== $existing->post_type
        ) {
                return new WP_Error(
                        'bitacora_instance_page_missing',
                        'No se encontró la página estructural esperada.',
                        array(
                                'post_id' => $post_id,
                        )
                );
        }

        $result = wp_update_post(
                array(
                        'ID'           => $post_id,
                        'post_name'    =>
                                (string) $state['post_name'],
                        'post_title'   =>
                                (string) $state['post_title'],
                        'post_content' =>
                                (string) $state['post_content'],
                        'post_status'  =>
                                (string) $state['post_status'],
                ),
                true
        );

        if ( is_wp_error( $result ) ) {
                return $result;
        }

        clean_post_cache(
                $post_id
        );

        $verified = get_post(
                $post_id
        );

        if (
                ! $verified instanceof WP_Post
                || 'page' !== $verified->post_type
                || (string) $verified->post_name
                        !== (string) $state['post_name']
                || (string) $verified->post_title
                        !== (string) $state['post_title']
                || (string) $verified->post_content
                        !== (string) $state['post_content']
                || (string) $verified->post_status
                        !== (string) $state['post_status']
        ) {
                return new WP_Error(
                        'bitacora_instance_page_state_not_verified',
                        'La página estructural no quedó en el estado esperado.',
                        array(
                                'post_id' => $post_id,
                        )
                );
        }

        return true;
}


/**
 * Ejecuta un plan físico previamente validado.
 *
 * Requiere:
 * - instancia PAUSED;
 * - owner coincidente;
 * - advisory lock poseído por la conexión actual;
 * - CURRENT todavía equivalente al PRE del plan.
 *
 * No cambia ACTIVE/PAUSED y no libera el lock.
 *
 * En caso de fallo devuelve el journal parcial dentro del WP_Error para que
 * el caller pueda recuperar en orden inverso.
 *
 * @return array|WP_Error
 */
function bitacora_execute_instance_structure_reconciliation_plan(
        $plan,
        $owner_user_id
) {

        $context =
                bitacora_check_instance_structure_execution_context(
                        $owner_user_id
                );

        if ( is_wp_error( $context ) ) {
                return $context;
        }

        if (
                ! is_array( $plan )
                || ! isset(
                        $plan['pre_fingerprint'],
                        $plan['proposal_fingerprint'],
                        $plan['actions']
                )
                || ! is_array( $plan['actions'] )
        ) {
                return new WP_Error(
                        'bitacora_instance_structure_plan_invalid',
                        'El plan de reconciliación no es válido.'
                );
        }

        $current =
                bitacora_get_instance_structure_snapshot();

        if ( is_wp_error( $current ) ) {
                return $current;
        }

        if (
                (string) $current['fingerprint']
                !== (string) $plan['pre_fingerprint']
        ) {
                return new WP_Error(
                        'bitacora_instance_structure_plan_stale',
                        'La estructura viva cambió después de construir el plan.',
                        array(
                                'expected' =>
                                        $plan['pre_fingerprint'],
                                'current' =>
                                        $current['fingerprint'],
                        )
                );
        }

        $journal = array(
                'pre_fingerprint' =>
                        (string) $plan['pre_fingerprint'],

                'proposal_fingerprint' =>
                        (string) $plan['proposal_fingerprint'],

                'actions' =>
                        array(),
        );

        $fail = static function (
                $error,
                $action
        ) use ( &$journal ) {

                if ( ! is_wp_error( $error ) ) {
                        $error = new WP_Error(
                                'bitacora_instance_structure_apply_action_failed',
                                (string) $error
                        );
                }

                return new WP_Error(
                        'bitacora_instance_structure_apply_failed',
                        'Falló la aplicación del plan estructural.',
                        array(
                                'cause_code' =>
                                        $error->get_error_code(),
                                'cause_message' =>
                                        $error->get_error_message(),
                                'cause_data' =>
                                        $error->get_error_data(),
                                'failed_action' =>
                                        $action,
                                'journal' =>
                                        $journal,
                        )
                );
        };

        foreach (
                $plan['actions']
                as $index => $action
        ) {

                if (
                        ! is_array( $action )
                        || empty( $action['type'] )
                        || empty( $action['identity'] )
                ) {
                        return $fail(
                                new WP_Error(
                                        'bitacora_instance_structure_action_invalid',
                                        'El plan contiene una acción inválida.'
                                ),
                                $action
                        );
                }

                $expected_sequence =
                        $index + 1;

                if (
                        ! isset( $action['sequence'] )
                        || (int) $action['sequence']
                                !== $expected_sequence
                ) {
                        return $fail(
                                new WP_Error(
                                        'bitacora_instance_structure_sequence_invalid',
                                        'La secuencia del plan no es válida.'
                                ),
                                $action
                        );
                }

                $entry = $action;

                switch ( $action['type'] ) {

                        case 'section.update':
                        case 'class.update':

                                $taxonomy =
                                        'section.update'
                                        === $action['type']
                                                ? 'bitacora_section'
                                                : 'bitacora_class';

                                $term_id =
                                        absint(
                                                $action['target']['term_id']
                                        );

                                $term = get_term(
                                        $term_id,
                                        $taxonomy
                                );

                                if (
                                        ! $term
                                        || is_wp_error( $term )
                                        || (string) $term->slug
                                                !== (string) $action['identity']
                                ) {
                                        return $fail(
                                                new WP_Error(
                                                        'bitacora_instance_structure_update_target_mismatch',
                                                        'El término a modificar no coincide con la identidad planificada.'
                                                ),
                                                $action
                                        );
                                }

                                $target_state =
                                        bitacora_build_instance_structure_term_target_state(
                                                $action['before'],
                                                $action['after']
                                        );

                                $entry['applied_target'] =
                                        array(
                                                'taxonomy' =>
                                                        $taxonomy,
                                                'term_id' =>
                                                        $term_id,
                                        );

                                /*
                                 * Desde este punto la acción puede dejar una
                                 * mutación parcial. Debe estar completa en el
                                 * journal antes de iniciar la escritura.
                                 */
                                $journal['actions'][] =
                                        $entry;

                                $written =
                                        bitacora_write_instance_structure_term_state(
                                                $taxonomy,
                                                $term_id,
                                                $target_state
                                        );

                                if ( is_wp_error( $written ) ) {
                                        return $fail(
                                                $written,
                                                $action
                                        );
                                }

                                break;


                        case 'section.create':
                        case 'class.create':

                                $taxonomy =
                                        'section.create'
                                        === $action['type']
                                                ? 'bitacora_section'
                                                : 'bitacora_class';

                                $existing =
                                        get_term_by(
                                                'slug',
                                                $action['identity'],
                                                $taxonomy
                                        );

                                if (
                                        $existing
                                        && ! is_wp_error( $existing )
                                ) {
                                        return $fail(
                                                new WP_Error(
                                                        'bitacora_instance_structure_create_conflict',
                                                        'La identidad nueva ya existe físicamente.',
                                                        array(
                                                                'taxonomy' =>
                                                                        $taxonomy,
                                                                'slug' =>
                                                                        $action['identity'],
                                                                'term_id' =>
                                                                        (int) $existing->term_id,
                                                        )
                                                ),
                                                $action
                                        );
                                }

                                $inserted =
                                        wp_insert_term(
                                                (string) $action['after']['name'],
                                                $taxonomy,
                                                array(
                                                        'slug' =>
                                                                (string) $action['after']['slug'],
                                                )
                                        );

                                if ( is_wp_error( $inserted ) ) {
                                        return $fail(
                                                $inserted,
                                                $action
                                        );
                                }

                                $term_id =
                                        (int) $inserted['term_id'];

                                $entry['applied_target'] =
                                        array(
                                                'taxonomy' =>
                                                        $taxonomy,
                                                'term_id' =>
                                                        $term_id,
                                        );

                                /*
                                 * El término ya existe físicamente.
                                 * El rollback central debe conocer su ID desde
                                 * este instante.
                                 */
                                $journal['actions'][] =
                                        $entry;

                                $target_state =
                                        bitacora_build_instance_structure_term_target_state(
                                                null,
                                                $action['after']
                                        );

                                $target_state['term_id'] =
                                        $term_id;

                                $written =
                                        bitacora_write_instance_structure_term_state(
                                                $taxonomy,
                                                $term_id,
                                                $target_state
                                        );

                                if ( is_wp_error( $written ) ) {
                                        return $fail(
                                                $written,
                                                $action
                                        );
                                }

                                break;


                        case 'page.update':

                                $post_id =
                                        absint(
                                                $action['target']['post_id']
                                        );

                                $page = get_post(
                                        $post_id
                                );

                                if (
                                        ! $page instanceof WP_Post
                                        || 'page' !== $page->post_type
                                        || (string) $page->post_name
                                                !== (string) $action['identity']
                                ) {
                                        return $fail(
                                                new WP_Error(
                                                        'bitacora_instance_page_update_target_mismatch',
                                                        'La página a modificar no coincide con la identidad planificada.'
                                                ),
                                                $action
                                        );
                                }

                                $entry['applied_target'] =
                                        array(
                                                'post_type' =>
                                                        'page',
                                                'post_id' =>
                                                        $post_id,
                                        );

                                /*
                                 * La actualización puede haber modificado la
                                 * página antes de detectar un fallo posterior.
                                 */
                                $journal['actions'][] =
                                        $entry;

                                $written =
                                        bitacora_write_instance_structure_page_state(
                                                $post_id,
                                                $action['after']
                                        );

                                if ( is_wp_error( $written ) ) {
                                        return $fail(
                                                $written,
                                                $action
                                        );
                                }

                                break;


                        case 'page.create':

                                $existing =
                                        get_page_by_path(
                                                $action['identity'],
                                                OBJECT,
                                                'page'
                                        );

                                if ( $existing ) {
                                        return $fail(
                                                new WP_Error(
                                                        'bitacora_instance_page_create_conflict',
                                                        'El slug de la página nueva ya está ocupado.',
                                                        array(
                                                                'slug' =>
                                                                        $action['identity'],
                                                                'post_id' =>
                                                                        (int) $existing->ID,
                                                        )
                                                ),
                                                $action
                                        );
                                }

                                $post_id =
                                        wp_insert_post(
                                                array(
                                                        'post_type' =>
                                                                'page',
                                                        'post_name' =>
                                                                (string) $action['after']['post_name'],
                                                        'post_title' =>
                                                                (string) $action['after']['post_title'],
                                                        'post_content' =>
                                                                (string) $action['after']['post_content'],
                                                        'post_status' =>
                                                                (string) $action['after']['post_status'],
                                                ),
                                                true
                                        );

                                if ( is_wp_error( $post_id ) ) {
                                        return $fail(
                                                $post_id,
                                                $action
                                        );
                                }

                                $post_id =
                                        (int) $post_id;

                                $entry['applied_target'] =
                                        array(
                                                'post_type' =>
                                                        'page',
                                                'post_id' =>
                                                        $post_id,
                                        );

                                /*
                                 * La página ya existe físicamente.
                                 * Registrar su ID antes de cualquier operación
                                 * posterior que pueda fallar.
                                 */
                                $journal['actions'][] =
                                        $entry;

                                $written =
                                        bitacora_write_instance_structure_page_state(
                                                $post_id,
                                                array_merge(
                                                        $action['after'],
                                                        array(
                                                                'post_id' =>
                                                                        $post_id,
                                                        )
                                                )
                                        );

                                if ( is_wp_error( $written ) ) {
                                        return $fail(
                                                $written,
                                                $action
                                        );
                                }

                                break;


                        default:

                                return $fail(
                                        new WP_Error(
                                                'bitacora_instance_structure_action_not_supported',
                                                sprintf(
                                                        'La acción "%s" no está soportada.',
                                                        (string) $action['type']
                                                )
                                        ),
                                        $action
                                );
                }
        }

        $journal['action_count'] =
                count(
                        $journal['actions']
                );

        return $journal;
}


/**
 * Revierte, en orden inverso, las acciones efectivamente ejecutadas.
 *
 * Sólo elimina objetos que fueron creados por este journal.
 * Los objetos pertenecientes a PRE se restauran sobre sus mismos IDs.
 *
 * @return array|WP_Error
 */
function bitacora_rollback_instance_structure_reconciliation_journal(
        $journal,
        $owner_user_id
) {

        $context =
                bitacora_check_instance_structure_execution_context(
                        $owner_user_id
                );

        if ( is_wp_error( $context ) ) {
                return $context;
        }

        if (
                ! is_array( $journal )
                || ! isset( $journal['actions'] )
                || ! is_array( $journal['actions'] )
        ) {
                return new WP_Error(
                        'bitacora_instance_structure_journal_invalid',
                        'El journal de reconciliación no es válido.'
                );
        }

        $errors = array();
        $rolled_back = 0;

        $actions =
                array_reverse(
                        $journal['actions']
                );

        foreach ( $actions as $action ) {

                $type = isset( $action['type'] )
                        ? (string) $action['type']
                        : '';

                $identity = isset( $action['identity'] )
                        ? (string) $action['identity']
                        : '';

                $error = null;

                switch ( $type ) {

                        case 'section.update':
                        case 'class.update':

                                $taxonomy =
                                        'section.update' === $type
                                                ? 'bitacora_section'
                                                : 'bitacora_class';

                                $term_id =
                                        absint(
                                                $action['applied_target']['term_id']
                                        );

                                $term = get_term(
                                        $term_id,
                                        $taxonomy
                                );

                                if (
                                        ! $term
                                        || is_wp_error( $term )
                                        || (string) $term->slug
                                                !== $identity
                                ) {
                                        $error = new WP_Error(
                                                'bitacora_instance_structure_rollback_target_mismatch',
                                                'No se encontró el término que debe restaurarse.'
                                        );
                                        break;
                                }

                                $written =
                                        bitacora_write_instance_structure_term_state(
                                                $taxonomy,
                                                $term_id,
                                                $action['rollback']['state']
                                        );

                                if ( is_wp_error( $written ) ) {
                                        $error = $written;
                                }

                                break;


                        case 'section.create':
                        case 'class.create':

                                $taxonomy =
                                        'section.create' === $type
                                                ? 'bitacora_section'
                                                : 'bitacora_class';

                                $term_id =
                                        absint(
                                                $action['applied_target']['term_id']
                                        );

                                $term = get_term(
                                        $term_id,
                                        $taxonomy
                                );

                                if (
                                        ! $term
                                        || is_wp_error( $term )
                                        || (string) $term->slug
                                                !== $identity
                                ) {
                                        $error = new WP_Error(
                                                'bitacora_instance_structure_created_term_mismatch',
                                                'El término creado ya no coincide con el journal.'
                                        );
                                        break;
                                }

                                $deleted =
                                        wp_delete_term(
                                                $term_id,
                                                $taxonomy
                                        );

                                if (
                                        is_wp_error( $deleted )
                                        || false === $deleted
                                ) {
                                        $error = is_wp_error( $deleted )
                                                ? $deleted
                                                : new WP_Error(
                                                        'bitacora_instance_structure_created_term_not_deleted',
                                                        'No se pudo eliminar el término creado durante APPLY.'
                                                );
                                }

                                break;


                        case 'page.update':

                                $post_id =
                                        absint(
                                                $action['applied_target']['post_id']
                                        );

                                $page = get_post(
                                        $post_id
                                );

                                if (
                                        ! $page instanceof WP_Post
                                        || 'page' !== $page->post_type
                                ) {
                                        $error = new WP_Error(
                                                'bitacora_instance_page_rollback_target_missing',
                                                'No se encontró la página que debe restaurarse.'
                                        );
                                        break;
                                }

                                $written =
                                        bitacora_write_instance_structure_page_state(
                                                $post_id,
                                                $action['rollback']['state']
                                        );

                                if ( is_wp_error( $written ) ) {
                                        $error = $written;
                                }

                                break;


                        case 'page.create':

                                $post_id =
                                        absint(
                                                $action['applied_target']['post_id']
                                        );

                                $page = get_post(
                                        $post_id
                                );

                                if (
                                        ! $page instanceof WP_Post
                                        || 'page' !== $page->post_type
                                        || (string) $page->post_name
                                                !== $identity
                                ) {
                                        $error = new WP_Error(
                                                'bitacora_instance_created_page_mismatch',
                                                'La página creada ya no coincide con el journal.'
                                        );
                                        break;
                                }

                                $deleted =
                                        wp_delete_post(
                                                $post_id,
                                                true
                                        );

                                if ( ! $deleted ) {
                                        $error = new WP_Error(
                                                'bitacora_instance_created_page_not_deleted',
                                                'No se pudo eliminar la página creada durante APPLY.'
                                        );
                                }

                                break;


                        default:

                                $error = new WP_Error(
                                        'bitacora_instance_structure_rollback_action_not_supported',
                                        sprintf(
                                                'La acción "%s" no puede revertirse.',
                                                $type
                                        )
                                );
                }

                if ( is_wp_error( $error ) ) {

                        $errors[] = array(
                                'type' =>
                                        $type,
                                'identity' =>
                                        $identity,
                                'code' =>
                                        $error->get_error_code(),
                                'message' =>
                                        $error->get_error_message(),
                                'data' =>
                                        $error->get_error_data(),
                        );

                        continue;
                }

                $rolled_back++;
        }

        if ( ! empty( $errors ) ) {
                return new WP_Error(
                        'bitacora_instance_structure_rollback_failed',
                        'La recuperación estructural hacia PRE no pudo completarse.',
                        array(
                                'rolled_back' =>
                                        $rolled_back,
                                'errors' =>
                                        $errors,
                        )
                );
        }

        return array(
                'rolled_back' =>
                        $rolled_back,
                'errors' =>
                        array(),
        );
}


/**
 * Toma una instantánea puramente estructural de la instancia vigente.
 *
 * La definición es la reconstrucción canónica del runtime y fingerprint es
 * su huella determinista. Este par puede utilizarse como PRE para detectar
 * drift estructural antes de una modificación controlada.
 */
function bitacora_get_instance_structure_snapshot() {

        $definition =
                bitacora_get_instance_profile_definition();

        if ( is_wp_error( $definition ) ) {
                return $definition;
        }

        $fingerprint =
                bitacora_get_profile_definition_fingerprint(
                        $definition
                );

        if ( is_wp_error( $fingerprint ) ) {
                return $fingerprint;
        }

        return array(
                'definition'  => $definition,
                'fingerprint' => $fingerprint,
        );
}


/**
 * Compara un snapshot estructural PRE contra la instancia vigente.
 *
 * El snapshot recibido no se considera confiable:
 * - su definición se valida y canonicaliza nuevamente;
 * - su fingerprint se recalcula;
 * - sólo entonces se compara contra un snapshot fresco del runtime.
 *
 * matches=false representa drift estructural, no un error.
 * Un snapshot PRE internamente inconsistente sí produce WP_Error.
 */
function bitacora_check_instance_structure_precondition( $pre ) {

        if (
                ! is_array( $pre )
                || ! isset( $pre['definition'] )
                || ! is_array( $pre['definition'] )
                || ! isset( $pre['fingerprint'] )
                || ! is_string( $pre['fingerprint'] )
        ) {
                return new WP_Error(
                        'bitacora_instance_pre_invalid',
                        'El snapshot PRE no tiene una forma válida.'
                );
        }

        $validation =
                bitacora_validate_profile_definition(
                        $pre['definition']
                );

        if ( empty( $validation['valid'] ) ) {
                return new WP_Error(
                        'bitacora_instance_pre_definition_invalid',
                        'La definición estructural de PRE no es válida.',
                        isset( $validation['errors'] )
                                ? $validation['errors']
                                : array()
                );
        }

        $expected_definition =
                bitacora_get_canonical_profile_definition(
                        $pre['definition']
                );

        if ( is_wp_error( $expected_definition ) ) {
                return $expected_definition;
        }

        $expected_fingerprint =
                bitacora_get_profile_definition_fingerprint(
                        $expected_definition
                );

        if ( is_wp_error( $expected_fingerprint ) ) {
                return $expected_fingerprint;
        }

        $supplied_fingerprint =
                strtolower(
                        trim(
                                $pre['fingerprint']
                        )
                );

        if (
                64 !== strlen( $supplied_fingerprint )
                || ! ctype_xdigit( $supplied_fingerprint )
        ) {
                return new WP_Error(
                        'bitacora_instance_pre_fingerprint_invalid',
                        'La huella estructural de PRE no es válida.'
                );
        }

        if (
                ! hash_equals(
                        $expected_fingerprint,
                        $supplied_fingerprint
                )
        ) {
                return new WP_Error(
                        'bitacora_instance_pre_fingerprint_mismatch',
                        'La huella de PRE no corresponde a su definición.'
                );
        }

        $current =
                bitacora_get_instance_structure_snapshot();

        if ( is_wp_error( $current ) ) {
                return $current;
        }

        $matches =
                hash_equals(
                        $expected_fingerprint,
                        $current['fingerprint']
                )
                && $expected_definition
                        === $current['definition'];

        return array(
                'matches' => $matches,

                'expected' => array(
                        'definition'  => $expected_definition,
                        'fingerprint' => $expected_fingerprint,
                ),

                'current' => $current,
        );
}


/**
 * Reconcilia de forma completa una PROPUESTA estructural contra PRE.
 *
 * El orquestador es dueño de toda la ventana crítica:
 *
 * 1. construye y valida el plan mientras la instancia está ACTIVE;
 * 2. adquiere el advisory lock;
 * 3. publica PAUSED fuera de cualquier transacción;
 * 4. vuelve a comprobar PRE;
 * 5. inicia la transacción estructural;
 * 6. vuelve a comprobar PRE dentro de la transacción;
 * 7. ejecuta APPLY;
 * 8. verifica POST;
 * 9. confirma COMMIT;
 * 10. verifica POST ya confirmado;
 * 11. devuelve la instancia a ACTIVE;
 * 12. libera el advisory lock.
 *
 * Si APPLY o la verificación previa al COMMIT fallan, intenta primero
 * rollback lógico mediante el journal. Si ese rollback no puede verificarse,
 * ROLLBACK SQL sigue siendo la red de seguridad de la transacción original.
 *
 * Si no puede demostrar PRE o POST de forma exacta, la instancia permanece
 * PAUSED. Nunca publica ACTIVE sobre un estado estructural incierto.
 *
 * @return array|WP_Error
 */
function bitacora_reconcile_instance_structure(
        $pre,
        $proposal_definition,
        $owner_user_id,
        $options = array()
) {
        global $wpdb;

        $owner_user_id =
                absint(
                        $owner_user_id
                );

        if ( ! $owner_user_id ) {
                return new WP_Error(
                        'bitacora_instance_reconciliation_owner_required',
                        'Debe indicarse el usuario responsable de la reconciliación.'
                );
        }

        $owner =
                get_user_by(
                        'id',
                        $owner_user_id
                );

        if ( ! $owner instanceof WP_User ) {
                return new WP_Error(
                        'bitacora_instance_reconciliation_owner_invalid',
                        'El usuario responsable de la reconciliación no existe.'
                );
        }

        /*
         * El owner no es un actor declarativo: debe ser exactamente
         * el usuario autenticado que inicia la operación.
         */
        $current_user_id =
                get_current_user_id();

        if (
                ! $current_user_id
                || $current_user_id !== $owner_user_id
        ) {
                return new WP_Error(
                        'bitacora_instance_reconciliation_owner_mismatch',
                        'El responsable de la reconciliación debe coincidir con el usuario actual.',
                        array(
                                'current_user_id' =>
                                        $current_user_id,
                                'owner_user_id' =>
                                        $owner_user_id,
                        )
                );
        }

        /*
         * Mantener una instancia viva es una autoridad distinta de
         * administrar o poner Profiles en uso.
         */
        if (
                ! user_can(
                        $owner,
                        'maintain_bitacora_instance'
                )
        ) {
                return new WP_Error(
                        'bitacora_instance_reconciliation_forbidden',
                        'El usuario actual no tiene permiso para mantener la estructura de la instancia.',
                        array(
                                'owner_user_id' =>
                                        $owner_user_id,
                        )
                );
        }

        $options =
                wp_parse_args(
                        $options,
                        array(
                                'lock_timeout' => 10,
                        )
                );

        $lock_timeout =
                max(
                        0,
                        (int) $options['lock_timeout']
                );

        $initial_state =
                bitacora_get_instance_runtime_state();

        if ( 'active' !== $initial_state ) {
                return new WP_Error(
                        'bitacora_instance_reconciliation_requires_active',
                        'La reconciliación estructural sólo puede iniciarse desde ACTIVE.',
                        array(
                                'runtime_state' =>
                                        $initial_state,
                                'owner_user_id' =>
                                        bitacora_get_instance_maintenance_owner(),
                        )
                );
        }

        /*
         * El planner vuelve a validar PRE, política de evolución,
         * uso histórico y PROPUESTA contra CURRENT.
         */
        $plan =
                bitacora_build_instance_structure_reconciliation_plan(
                        $pre,
                        $proposal_definition
                );

        if ( is_wp_error( $plan ) ) {
                return $plan;
        }

        $pre_definition =
                isset( $pre['definition'] )
                && is_array( $pre['definition'] )
                        ? bitacora_get_canonical_profile_definition(
                                $pre['definition']
                        )
                        : new WP_Error(
                                'bitacora_instance_pre_invalid',
                                'PRE no contiene una definición estructural válida.'
                        );

        if ( is_wp_error( $pre_definition ) ) {
                return $pre_definition;
        }

        $proposal =
                bitacora_get_canonical_profile_definition(
                        $proposal_definition
                );

        if ( is_wp_error( $proposal ) ) {
                return $proposal;
        }

        $snapshot_matches =
                static function (
                        $snapshot,
                        $definition,
                        $fingerprint
                ) {

                        return (
                                is_array( $snapshot )
                                && isset(
                                        $snapshot['definition'],
                                        $snapshot['fingerprint']
                                )
                                && is_array(
                                        $snapshot['definition']
                                )
                                && is_string(
                                        $snapshot['fingerprint']
                                )
                                && hash_equals(
                                        (string) $fingerprint,
                                        (string) $snapshot['fingerprint']
                                )
                                && $definition
                                        === $snapshot['definition']
                        );
                };

        /*
         * Un plan realmente vacío no necesita PAUSA.
         */
        if ( empty( $plan['actions'] ) ) {

                $current =
                        bitacora_get_instance_structure_snapshot();

                if ( is_wp_error( $current ) ) {
                        return $current;
                }

                if (
                        ! $snapshot_matches(
                                $current,
                                $proposal,
                                $plan['proposal_fingerprint']
                        )
                ) {
                        return new WP_Error(
                                'bitacora_instance_reconciliation_noop_mismatch',
                                'El plan quedó vacío pero CURRENT no coincide con la PROPUESTA.',
                                array(
                                        'current' =>
                                                $current,
                                        'proposal_fingerprint' =>
                                                $plan['proposal_fingerprint'],
                                )
                        );
                }

                return array(
                        'changed' =>
                                false,
                        'action_count' =>
                                0,
                        'pre_fingerprint' =>
                                (string) $plan['pre_fingerprint'],
                        'post_fingerprint' =>
                                (string) $current['fingerprint'],
                        'runtime_state' =>
                                bitacora_get_instance_runtime_state(),
                );
        }

        $lock =
                bitacora_acquire_instance_maintenance_lock(
                        $lock_timeout
                );

        if ( is_wp_error( $lock ) ) {
                return $lock;
        }

        $lock_held = true;

        $release_lock =
                static function () use (
                        &$lock_held
                ) {

                        if ( ! $lock_held ) {
                                return true;
                        }

                        $released =
                                bitacora_release_instance_maintenance_lock();

                        if ( true === $released ) {
                                $lock_held = false;
                        }

                        return $released;
                };

        /*
         * Mientras esperábamos el advisory lock otro mantenimiento pudo
         * haber terminado dejando PAUSED para recuperación manual.
         *
         * No apropiarse silenciosamente de ese estado.
         */
        if (
                'active'
                !== bitacora_get_instance_runtime_state()
        ) {
                $state =
                        bitacora_get_instance_runtime_state();

                $maintenance_owner =
                        bitacora_get_instance_maintenance_owner();

                $released =
                        $release_lock();

                return new WP_Error(
                        'bitacora_instance_reconciliation_not_active_after_lock',
                        'La instancia dejó de estar ACTIVE antes de iniciar la PAUSA.',
                        array(
                                'runtime_state' =>
                                        $state,
                                'owner_user_id' =>
                                        $maintenance_owner,
                                'lock_release' =>
                                        is_wp_error( $released )
                                                ? array(
                                                        'code' =>
                                                                $released->get_error_code(),
                                                        'message' =>
                                                                $released->get_error_message(),
                                                )
                                                : true,
                        )
                );
        }

        /*
         * PAUSED se persiste todavía en autocommit.
         *
         * De este modo otras conexiones conocen la ventana de mantenimiento
         * antes de que empiece la transacción que ocultará APPLY.
         */
        $paused =
                bitacora_pause_instance_for_maintenance(
                        $owner_user_id
                );

        if ( is_wp_error( $paused ) ) {
                $released =
                        $release_lock();

                if ( is_wp_error( $released ) ) {
                        return new WP_Error(
                                'bitacora_instance_reconciliation_pause_cleanup_failed',
                                'No se pudo iniciar la PAUSA ni liberar el advisory lock.',
                                array(
                                        'pause_error' =>
                                                array(
                                                        'code' =>
                                                                $paused->get_error_code(),
                                                        'message' =>
                                                                $paused->get_error_message(),
                                                        'data' =>
                                                                $paused->get_error_data(),
                                                ),
                                        'release_error' =>
                                                array(
                                                        'code' =>
                                                                $released->get_error_code(),
                                                        'message' =>
                                                                $released->get_error_message(),
                                                        'data' =>
                                                                $released->get_error_data(),
                                                ),
                                )
                        );
                }

                return $paused;
        }

        $transaction_active = false;

        /*
         * Aborta una etapa donde todavía no existe APPLY que recuperar.
         *
         * Si había una transacción abierta, ROLLBACK SQL es suficiente.
         */
        $abort_without_apply =
                static function (
                        $cause
                ) use (
                        &$transaction_active,
                        $wpdb,
                        $owner_user_id,
                        $release_lock
                ) {

                        if ( ! is_wp_error( $cause ) ) {
                                $cause =
                                        new WP_Error(
                                                'bitacora_instance_reconciliation_aborted',
                                                (string) $cause
                                        );
                        }

                        $sql_rollback = null;

                        if ( $transaction_active ) {
                                $sql_rollback =
                                        $wpdb->query(
                                                'ROLLBACK'
                                        );

                                $transaction_active = false;

                                wp_cache_flush();
                        }

                        $resumed =
                                bitacora_resume_instance_from_maintenance(
                                        $owner_user_id
                                );

                        $released =
                                $release_lock();

                        if (
                                is_wp_error( $resumed )
                                || is_wp_error( $released )
                        ) {
                                return new WP_Error(
                                        'bitacora_instance_reconciliation_abort_cleanup_failed',
                                        'La reconciliación se abortó antes de APPLY, pero la limpieza operativa no pudo completarse.',
                                        array(
                                                'cause' =>
                                                        array(
                                                                'code' =>
                                                                        $cause->get_error_code(),
                                                                'message' =>
                                                                        $cause->get_error_message(),
                                                                'data' =>
                                                                        $cause->get_error_data(),
                                                        ),
                                                'sql_rollback' =>
                                                        $sql_rollback,
                                                'resume_error' =>
                                                        is_wp_error( $resumed )
                                                                ? array(
                                                                        'code' =>
                                                                                $resumed->get_error_code(),
                                                                        'message' =>
                                                                                $resumed->get_error_message(),
                                                                        'data' =>
                                                                                $resumed->get_error_data(),
                                                                )
                                                                : null,
                                                'release_error' =>
                                                        is_wp_error( $released )
                                                                ? array(
                                                                        'code' =>
                                                                                $released->get_error_code(),
                                                                        'message' =>
                                                                                $released->get_error_message(),
                                                                        'data' =>
                                                                                $released->get_error_data(),
                                                                )
                                                                : null,
                                                'runtime_state' =>
                                                        bitacora_get_instance_runtime_state(),
                                                'owner_user_id' =>
                                                        bitacora_get_instance_maintenance_owner(),
                                        )
                                );
                        }

                        return $cause;
                };

        /*
         * Recupera PRE tras un APPLY iniciado.
         *
         * Si transaction_active pertenece al APPLY original:
         * - primero intenta rollback lógico + COMMIT de la recuperación;
         * - si no logra verificar PRE, ROLLBACK SQL devuelve toda la
         *   transacción original al punto de partida.
         *
         * Esta misma rutina puede recibir una transacción compensatoria
         * iniciada después de un COMMIT ya confirmado.
         */
        $recover_pre =
                static function (
                        $cause,
                        $journal,
                        $phase,
                        $original_apply_transaction
                ) use (
                        &$transaction_active,
                        $wpdb,
                        $owner_user_id,
                        $pre_definition,
                        $plan,
                        $snapshot_matches,
                        $release_lock
                ) {

                        if ( ! is_wp_error( $cause ) ) {
                                $cause =
                                        new WP_Error(
                                                'bitacora_instance_reconciliation_failed',
                                                (string) $cause
                                        );
                        }

                        $recovery = array(
                                'phase' =>
                                        (string) $phase,
                                'logical_rollback' =>
                                        null,
                                'logical_pre_verified' =>
                                        false,
                                'recovery_commit' =>
                                        null,
                                'sql_rollback' =>
                                        null,
                                'sql_pre_verified' =>
                                        false,
                                'method' =>
                                        '',
                        );

                        /*
                         * Primer intento: journal reversible.
                         */
                        if (
                                $transaction_active
                                && is_array( $journal )
                        ) {
                                $logical =
                                        bitacora_rollback_instance_structure_reconciliation_journal(
                                                $journal,
                                                $owner_user_id
                                        );

                                if ( is_wp_error( $logical ) ) {
                                        $recovery['logical_rollback'] =
                                                array(
                                                        'code' =>
                                                                $logical->get_error_code(),
                                                        'message' =>
                                                                $logical->get_error_message(),
                                                        'data' =>
                                                                $logical->get_error_data(),
                                                );
                                } else {
                                        $recovery['logical_rollback'] =
                                                $logical;

                                        wp_cache_flush();

                                        $logical_snapshot =
                                                bitacora_get_instance_structure_snapshot();

                                        $logical_matches =
                                                ! is_wp_error(
                                                        $logical_snapshot
                                                )
                                                && $snapshot_matches(
                                                        $logical_snapshot,
                                                        $pre_definition,
                                                        $plan['pre_fingerprint']
                                                );

                                        $recovery['logical_pre_verified'] =
                                                $logical_matches;

                                        if ( $logical_matches ) {

                                                $committed =
                                                        $wpdb->query(
                                                                'COMMIT'
                                                        );

                                                $recovery['recovery_commit'] =
                                                        $committed;

                                                if ( false !== $committed ) {

                                                        $transaction_active =
                                                                false;

                                                        wp_cache_flush();

                                                        $confirmed =
                                                                bitacora_get_instance_structure_snapshot();

                                                        if (
                                                                ! is_wp_error(
                                                                        $confirmed
                                                                )
                                                                && $snapshot_matches(
                                                                        $confirmed,
                                                                        $pre_definition,
                                                                        $plan['pre_fingerprint']
                                                                )
                                                        ) {
                                                                $recovery['method'] =
                                                                        'journal';

                                                                $resumed =
                                                                        bitacora_resume_instance_from_maintenance(
                                                                                $owner_user_id
                                                                        );

                                                                $released =
                                                                        $release_lock();

                                                                if (
                                                                        ! is_wp_error( $resumed )
                                                                        && ! is_wp_error( $released )
                                                                ) {
                                                                        return new WP_Error(
                                                                                'bitacora_instance_reconciliation_failed',
                                                                                'La reconciliación no se completó; PRE fue restaurado y la instancia volvió a ACTIVE.',
                                                                                array(
                                                                                        'recovered' =>
                                                                                                true,
                                                                                        'recovery' =>
                                                                                                $recovery,
                                                                                        'cause' =>
                                                                                                array(
                                                                                                        'code' =>
                                                                                                                $cause->get_error_code(),
                                                                                                        'message' =>
                                                                                                                $cause->get_error_message(),
                                                                                                        'data' =>
                                                                                                                $cause->get_error_data(),
                                                                                                ),
                                                                                        'runtime_state' =>
                                                                                                bitacora_get_instance_runtime_state(),
                                                                                )
                                                                        );
                                                                }

                                                                return new WP_Error(
                                                                        'bitacora_instance_reconciliation_recovery_cleanup_failed',
                                                                        'PRE fue restaurado, pero no pudo completarse la salida de mantenimiento.',
                                                                        array(
                                                                                'recovered' =>
                                                                                        true,
                                                                                'recovery' =>
                                                                                        $recovery,
                                                                                'resume_error' =>
                                                                                        is_wp_error( $resumed )
                                                                                                ? array(
                                                                                                        'code' =>
                                                                                                                $resumed->get_error_code(),
                                                                                                        'message' =>
                                                                                                                $resumed->get_error_message(),
                                                                                                        'data' =>
                                                                                                                $resumed->get_error_data(),
                                                                                                )
                                                                                                : null,
                                                                                'release_error' =>
                                                                                        is_wp_error( $released )
                                                                                                ? array(
                                                                                                        'code' =>
                                                                                                                $released->get_error_code(),
                                                                                                        'message' =>
                                                                                                                $released->get_error_message(),
                                                                                                        'data' =>
                                                                                                                $released->get_error_data(),
                                                                                                )
                                                                                                : null,
                                                                                'runtime_state' =>
                                                                                        bitacora_get_instance_runtime_state(),
                                                                        )
                                                                );
                                                        }
                                                }
                                        }
                                }
                        }

                        /*
                         * Si seguimos dentro de la transacción original de
                         * APPLY, ROLLBACK SQL es la segunda red de seguridad.
                         *
                         * No se utiliza como "éxito" de una transacción
                         * compensatoria posterior al COMMIT, porque allí sólo
                         * nos devolvería al POST defectuoso.
                         */
                        if (
                                $transaction_active
                                && $original_apply_transaction
                        ) {
                                $sql_rollback =
                                        $wpdb->query(
                                                'ROLLBACK'
                                        );

                                $recovery['sql_rollback'] =
                                        $sql_rollback;

                                $transaction_active =
                                        false;

                                wp_cache_flush();

                                $sql_snapshot =
                                        bitacora_get_instance_structure_snapshot();

                                $sql_matches =
                                        ! is_wp_error(
                                                $sql_snapshot
                                        )
                                        && $snapshot_matches(
                                                $sql_snapshot,
                                                $pre_definition,
                                                $plan['pre_fingerprint']
                                        );

                                $recovery['sql_pre_verified'] =
                                        $sql_matches;

                                if ( $sql_matches ) {

                                        $recovery['method'] =
                                                'sql_rollback';

                                        $resumed =
                                                bitacora_resume_instance_from_maintenance(
                                                        $owner_user_id
                                                );

                                        $released =
                                                $release_lock();

                                        if (
                                                ! is_wp_error( $resumed )
                                                && ! is_wp_error( $released )
                                        ) {
                                                return new WP_Error(
                                                        'bitacora_instance_reconciliation_failed',
                                                        'La reconciliación no se completó; PRE fue restaurado por ROLLBACK SQL y la instancia volvió a ACTIVE.',
                                                        array(
                                                                'recovered' =>
                                                                        true,
                                                                'recovery' =>
                                                                        $recovery,
                                                                'cause' =>
                                                                        array(
                                                                                'code' =>
                                                                                        $cause->get_error_code(),
                                                                                'message' =>
                                                                                        $cause->get_error_message(),
                                                                                'data' =>
                                                                                        $cause->get_error_data(),
                                                                        ),
                                                                'runtime_state' =>
                                                                        bitacora_get_instance_runtime_state(),
                                                        )
                                                );
                                        }

                                        return new WP_Error(
                                                'bitacora_instance_reconciliation_recovery_cleanup_failed',
                                                'PRE fue restaurado por ROLLBACK SQL, pero no pudo completarse la salida de mantenimiento.',
                                                array(
                                                        'recovered' =>
                                                                true,
                                                        'recovery' =>
                                                                $recovery,
                                                        'resume_error' =>
                                                                is_wp_error( $resumed )
                                                                        ? array(
                                                                                'code' =>
                                                                                        $resumed->get_error_code(),
                                                                                'message' =>
                                                                                        $resumed->get_error_message(),
                                                                                'data' =>
                                                                                        $resumed->get_error_data(),
                                                                        )
                                                                        : null,
                                                        'release_error' =>
                                                                is_wp_error( $released )
                                                                        ? array(
                                                                                'code' =>
                                                                                        $released->get_error_code(),
                                                                                'message' =>
                                                                                        $released->get_error_message(),
                                                                                'data' =>
                                                                                        $released->get_error_data(),
                                                                        )
                                                                        : null,
                                                        'runtime_state' =>
                                                                bitacora_get_instance_runtime_state(),
                                                )
                                        );
                                }
                        }

                        /*
                         * Una transacción compensatoria fallida debe
                         * descartarse, pero ello no constituye recuperación.
                         */
                        if ( $transaction_active ) {
                                $wpdb->query(
                                        'ROLLBACK'
                                );

                                $transaction_active =
                                        false;

                                wp_cache_flush();
                        }

                        /*
                         * No demostrar PRE => no publicar ACTIVE.
                         */
                        $released =
                                $release_lock();

                        return new WP_Error(
                                'bitacora_instance_reconciliation_recovery_failed',
                                'No fue posible demostrar una recuperación estructural exacta hacia PRE. La instancia permanece PAUSED.',
                                array(
                                        'recovered' =>
                                                false,
                                        'cause' =>
                                                array(
                                                        'code' =>
                                                                $cause->get_error_code(),
                                                        'message' =>
                                                                $cause->get_error_message(),
                                                        'data' =>
                                                                $cause->get_error_data(),
                                                ),
                                        'recovery' =>
                                                $recovery,
                                        'release_error' =>
                                                is_wp_error( $released )
                                                        ? array(
                                                                'code' =>
                                                                        $released->get_error_code(),
                                                                'message' =>
                                                                        $released->get_error_message(),
                                                                'data' =>
                                                                        $released->get_error_data(),
                                                        )
                                                        : null,
                                        'runtime_state' =>
                                                bitacora_get_instance_runtime_state(),
                                        'owner_user_id' =>
                                                bitacora_get_instance_maintenance_owner(),
                                )
                        );
                };

        /*
         * Recheck PRE con PAUSED ya visible, todavía fuera de la
         * transacción estructural.
         */
        wp_cache_flush();

        $precheck =
                bitacora_check_instance_structure_precondition(
                        $pre
                );

        if ( is_wp_error( $precheck ) ) {
                return $abort_without_apply(
                        $precheck
                );
        }

        if ( empty( $precheck['matches'] ) ) {
                return $abort_without_apply(
                        new WP_Error(
                                'bitacora_instance_structure_precondition_changed',
                                'CURRENT dejó de coincidir con PRE después de adquirir la PAUSA.',
                                $precheck
                        )
                );
        }

        /*
         * PAUSED ya fue confirmado y es visible desde otras conexiones.
         * Sólo ahora comienza la transacción que ocultará APPLY.
         */
        $started =
                $wpdb->query(
                        'START TRANSACTION'
                );

        if ( false === $started ) {
                return $abort_without_apply(
                        new WP_Error(
                                'bitacora_instance_reconciliation_transaction_not_started',
                                'No se pudo iniciar la transacción de reconciliación.'
                        )
                );
        }

        $transaction_active = true;

        /*
         * Segundo recheck PRE ya dentro de la sección crítica SQL.
         */
        wp_cache_flush();

        $inside_precheck =
                bitacora_check_instance_structure_precondition(
                        $pre
                );

        if ( is_wp_error( $inside_precheck ) ) {
                return $abort_without_apply(
                        $inside_precheck
                );
        }

        if ( empty( $inside_precheck['matches'] ) ) {
                return $abort_without_apply(
                        new WP_Error(
                                'bitacora_instance_structure_precondition_changed',
                                'CURRENT dejó de coincidir con PRE dentro de la transacción.',
                                $inside_precheck
                        )
                );
        }

        /*
         * APPLY físico.
         */
        $journal =
                bitacora_execute_instance_structure_reconciliation_plan(
                        $plan,
                        $owner_user_id
                );

        if ( is_wp_error( $journal ) ) {

                $error_data =
                        $journal->get_error_data();

                $partial_journal =
                        is_array( $error_data )
                        && isset( $error_data['journal'] )
                        && is_array( $error_data['journal'] )
                                ? $error_data['journal']
                                : null;

                return $recover_pre(
                        $journal,
                        $partial_journal,
                        'apply',
                        true
                );
        }

        /*
         * POST debe ser exacto antes del COMMIT.
         */
        wp_cache_flush();

        $post =
                bitacora_get_instance_structure_snapshot();

        if (
                is_wp_error( $post )
                || ! $snapshot_matches(
                        $post,
                        $proposal,
                        $plan['proposal_fingerprint']
                )
        ) {
                $verification_error =
                        is_wp_error( $post )
                                ? $post
                                : new WP_Error(
                                        'bitacora_instance_reconciliation_post_mismatch',
                                        'POST no coincide exactamente con la PROPUESTA antes del COMMIT.',
                                        array(
                                                'current' =>
                                                        $post,
                                                'proposal_fingerprint' =>
                                                        $plan['proposal_fingerprint'],
                                        )
                                );

                return $recover_pre(
                        $verification_error,
                        $journal,
                        'pre_commit_verification',
                        true
                );
        }

        /*
         * Confirmar la estructura.
         */
        $committed =
                $wpdb->query(
                        'COMMIT'
                );

        if ( false === $committed ) {

                /*
                 * Estado transaccional incierto:
                 * intentar primero ROLLBACK y observar qué quedó realmente.
                 */
                $wpdb->query(
                        'ROLLBACK'
                );

                $transaction_active =
                        false;

                wp_cache_flush();

                $after_failed_commit =
                        bitacora_get_instance_structure_snapshot();

                if (
                        ! is_wp_error( $after_failed_commit )
                        && $snapshot_matches(
                                $after_failed_commit,
                                $pre_definition,
                                $plan['pre_fingerprint']
                        )
                ) {
                        $resumed =
                                bitacora_resume_instance_from_maintenance(
                                        $owner_user_id
                                );

                        $released =
                                $release_lock();

                        if (
                                ! is_wp_error( $resumed )
                                && ! is_wp_error( $released )
                        ) {
                                return new WP_Error(
                                        'bitacora_instance_reconciliation_commit_failed',
                                        'COMMIT falló, pero PRE quedó restaurado y la instancia volvió a ACTIVE.',
                                        array(
                                                'recovered' =>
                                                        true,
                                                'runtime_state' =>
                                                        bitacora_get_instance_runtime_state(),
                                        )
                                );
                        }
                }

                /*
                 * Si el COMMIT pudo haberse aplicado realmente y vemos POST,
                 * intentar una compensación journal -> PRE en una transacción
                 * nueva. Durante todo este tiempo PAUSED sigue visible.
                 */
                if (
                        ! is_wp_error( $after_failed_commit )
                        && $snapshot_matches(
                                $after_failed_commit,
                                $proposal,
                                $plan['proposal_fingerprint']
                        )
                ) {
                        $recovery_started =
                                $wpdb->query(
                                        'START TRANSACTION'
                                );

                        if ( false !== $recovery_started ) {
                                $transaction_active =
                                        true;

                                return $recover_pre(
                                        new WP_Error(
                                                'bitacora_instance_reconciliation_commit_failed',
                                                'COMMIT devolvió error después de APPLY.'
                                        ),
                                        $journal,
                                        'commit_uncertain',
                                        false
                                );
                        }
                }

                $released =
                        $release_lock();

                return new WP_Error(
                        'bitacora_instance_reconciliation_commit_uncertain',
                        'COMMIT devolvió error y no pudo demostrarse PRE ni POST de forma segura. La instancia permanece PAUSED.',
                        array(
                                'recovered' =>
                                        false,
                                'current' =>
                                        is_wp_error( $after_failed_commit )
                                                ? array(
                                                        'code' =>
                                                                $after_failed_commit->get_error_code(),
                                                        'message' =>
                                                                $after_failed_commit->get_error_message(),
                                                        'data' =>
                                                                $after_failed_commit->get_error_data(),
                                                )
                                                : $after_failed_commit,
                                'release_error' =>
                                        is_wp_error( $released )
                                                ? array(
                                                        'code' =>
                                                                $released->get_error_code(),
                                                        'message' =>
                                                                $released->get_error_message(),
                                                        'data' =>
                                                                $released->get_error_data(),
                                                )
                                                : null,
                                'runtime_state' =>
                                        bitacora_get_instance_runtime_state(),
                        )
                );
        }

        $transaction_active = false;

        /*
         * Verificación posterior al COMMIT.
         */
        wp_cache_flush();

        $confirmed =
                bitacora_get_instance_structure_snapshot();

        if (
                is_wp_error( $confirmed )
                || ! $snapshot_matches(
                        $confirmed,
                        $proposal,
                        $plan['proposal_fingerprint']
                )
        ) {
                $verification_error =
                        is_wp_error( $confirmed )
                                ? $confirmed
                                : new WP_Error(
                                        'bitacora_instance_reconciliation_committed_post_mismatch',
                                        'POST confirmado no coincide exactamente con la PROPUESTA.',
                                        array(
                                                'current' =>
                                                        $confirmed,
                                                'proposal_fingerprint' =>
                                                        $plan['proposal_fingerprint'],
                                        )
                                );

                /*
                 * El APPLY ya fue confirmado.
                 * Recuperar PRE requiere una transacción compensatoria nueva.
                 */
                $recovery_started =
                        $wpdb->query(
                                'START TRANSACTION'
                        );

                if ( false === $recovery_started ) {
                        $released =
                                $release_lock();

                        return new WP_Error(
                                'bitacora_instance_reconciliation_committed_recovery_not_started',
                                'POST confirmado no pudo verificarse y no fue posible iniciar recuperación. La instancia permanece PAUSED.',
                                array(
                                        'cause' =>
                                                array(
                                                        'code' =>
                                                                $verification_error->get_error_code(),
                                                        'message' =>
                                                                $verification_error->get_error_message(),
                                                        'data' =>
                                                                $verification_error->get_error_data(),
                                                ),
                                        'release_error' =>
                                                is_wp_error( $released )
                                                        ? array(
                                                                'code' =>
                                                                        $released->get_error_code(),
                                                                'message' =>
                                                                        $released->get_error_message(),
                                                                'data' =>
                                                                        $released->get_error_data(),
                                                        )
                                                        : null,
                                )
                        );
                }

                $transaction_active =
                        true;

                return $recover_pre(
                        $verification_error,
                        $journal,
                        'post_commit_verification',
                        false
                );
        }

        /*
         * Sólo un POST confirmado puede volver a ACTIVE.
         */
        $resumed =
                bitacora_resume_instance_from_maintenance(
                        $owner_user_id
                );

        if ( is_wp_error( $resumed ) ) {

                $released =
                        $release_lock();

                return new WP_Error(
                        'bitacora_instance_reconciliation_resume_failed',
                        'POST fue confirmado, pero no pudo devolverse la instancia a ACTIVE.',
                        array(
                                'applied' =>
                                        true,
                                'post_fingerprint' =>
                                        $confirmed['fingerprint'],
                                'resume_error' =>
                                        array(
                                                'code' =>
                                                        $resumed->get_error_code(),
                                                'message' =>
                                                        $resumed->get_error_message(),
                                                'data' =>
                                                        $resumed->get_error_data(),
                                        ),
                                'release_error' =>
                                        is_wp_error( $released )
                                                ? array(
                                                        'code' =>
                                                                $released->get_error_code(),
                                                        'message' =>
                                                                $released->get_error_message(),
                                                        'data' =>
                                                                $released->get_error_data(),
                                                )
                                                : null,
                                'runtime_state' =>
                                        bitacora_get_instance_runtime_state(),
                        )
                );
        }

        $released =
                $release_lock();

        if ( is_wp_error( $released ) ) {
                return new WP_Error(
                        'bitacora_instance_reconciliation_lock_release_failed',
                        'La reconciliación fue confirmada y la instancia está ACTIVE, pero no pudo liberarse explícitamente el advisory lock.',
                        array(
                                'applied' =>
                                        true,
                                'runtime_state' =>
                                        bitacora_get_instance_runtime_state(),
                                'post_fingerprint' =>
                                        $confirmed['fingerprint'],
                                'release_error' =>
                                        array(
                                                'code' =>
                                                        $released->get_error_code(),
                                                'message' =>
                                                        $released->get_error_message(),
                                                'data' =>
                                                        $released->get_error_data(),
                                        ),
                        )
                );
        }

        return array(
                'changed' =>
                        true,
                'action_count' =>
                        count(
                                $plan['actions']
                        ),
                'pre_fingerprint' =>
                        (string) $plan['pre_fingerprint'],
                'post_fingerprint' =>
                        (string) $confirmed['fingerprint'],
                'runtime_state' =>
                        bitacora_get_instance_runtime_state(),
                'owner_user_id' =>
                        bitacora_get_instance_maintenance_owner(),
        );
}


/**
 * Extrae la estructura vigente de la instancia como definición canónica.
 *
 * La extracción contiene solamente propiedades pertenecientes al contrato
 * Profile. No incluye identidad del Profile de origen, IDs WordPress,
 * páginas, entradas, comentarios, adjuntos ni estado operativo.
 *
 * La instancia debe encontrarse configurada y su estructura materializada
 * debe satisfacer las invariantes actuales del Engine.
 */
function bitacora_get_instance_profile_definition() {

        if (
                ! function_exists( 'bitacora_is_configured' )
                || ! bitacora_is_configured()
        ) {
                return new WP_Error(
                        'bitacora_instance_not_configured',
                        'Bitácora no tiene una instancia configurada.'
                );
        }

        $section_terms = get_terms(
                array(
                        'taxonomy'   => 'bitacora_section',
                        'hide_empty' => false,
                )
        );

        if ( is_wp_error( $section_terms ) ) {
                return new WP_Error(
                        'bitacora_instance_sections_unavailable',
                        'No se pudo leer la estructura de secciones de la instancia.',
                        $section_terms
                );
        }

        $class_terms = get_terms(
                array(
                        'taxonomy'   => 'bitacora_class',
                        'hide_empty' => false,
                )
        );

        if ( is_wp_error( $class_terms ) ) {
                return new WP_Error(
                        'bitacora_instance_classes_unavailable',
                        'No se pudo leer la estructura de Tipos de la instancia.',
                        $class_terms
                );
        }

        $definition = array(
                'core'     => array(),
                'sections' => array(),
                'classes'  => array(),
        );

        $errors     = array();
        $core_count = 0;

        $section_meta_map = array(
                'singular'         => 'bitacora_section_singular',
                'plural'           => 'bitacora_section_plural',
                'subtitle'         => 'bitacora_section_subtitle',
                'new_label'        => 'bitacora_section_new_label',
                'order'            => 'bitacora_section_order',
                'area'             => 'bitacora_section_area',
                'state'            => 'bitacora_section_state',
                'feature_file'     => 'bitacora_section_feature_file',
                'feature_location' => 'bitacora_section_feature_location',
                'feature_comments' => 'bitacora_section_feature_comments',
        );

        $feature_keys = array(
                'feature_file',
                'feature_location',
                'feature_comments',
        );

        foreach ( $section_terms as $term ) {

                $role = metadata_exists(
                        'term',
                        $term->term_id,
                        'bitacora_section_role'
                )
                        ? (string) get_term_meta(
                                $term->term_id,
                                'bitacora_section_role',
                                true
                        )
                        : '';

                /*
                 * Core: identidad y estructura reservadas pertenecen
                 * al Engine y no forman parte de los overrides extraídos.
                 */
                if ( 'core' === $role ) {

                        $core_count++;

                        if ( 'notas' !== $term->slug ) {
                                $errors[] = sprintf(
                                        'Core: slug runtime inesperado "%s".',
                                        $term->slug
                                );
                        }

                        $runtime_order = metadata_exists(
                                'term',
                                $term->term_id,
                                'bitacora_section_order'
                        )
                                ? get_term_meta(
                                        $term->term_id,
                                        'bitacora_section_order',
                                        true
                                )
                                : null;

                        $runtime_area = metadata_exists(
                                'term',
                                $term->term_id,
                                'bitacora_section_area'
                        )
                                ? get_term_meta(
                                        $term->term_id,
                                        'bitacora_section_area',
                                        true
                                )
                                : null;

                        $runtime_state = metadata_exists(
                                'term',
                                $term->term_id,
                                'bitacora_section_state'
                        )
                                ? get_term_meta(
                                        $term->term_id,
                                        'bitacora_section_state',
                                        true
                                )
                                : null;

                        if (
                                0 !== (int) $runtime_order
                                || 'main' !== $runtime_area
                                || 'active' !== $runtime_state
                        ) {
                                $errors[] =
                                        'Core: la estructura runtime viola '
                                        . 'order=0, area=main o state=active.';
                        }

                        $core = array(
                                'name' => (string) $term->name,
                        );

                        foreach (
                                array(
                                        'singular',
                                        'plural',
                                        'subtitle',
                                        'new_label',
                                        'feature_file',
                                        'feature_location',
                                        'feature_comments',
                                )
                                as $key
                        ) {
                                $meta_key = $section_meta_map[ $key ];

                                if (
                                        in_array(
                                                $key,
                                                $feature_keys,
                                                true
                                        )
                                ) {
                                        $core[ $key ] =
                                                metadata_exists(
                                                        'term',
                                                        $term->term_id,
                                                        $meta_key
                                                )
                                                && '1' === (string) get_term_meta(
                                                        $term->term_id,
                                                        $meta_key,
                                                        true
                                                );

                                        continue;
                                }

                                if (
                                        metadata_exists(
                                                'term',
                                                $term->term_id,
                                                $meta_key
                                        )
                                ) {
                                        $core[ $key ] =
                                                (string) get_term_meta(
                                                        $term->term_id,
                                                        $meta_key,
                                                        true
                                                );
                                }
                        }

                        $definition['core'] = $core;
                        continue;
                }

                /*
                 * Toda sección no-core materializada debe declarar
                 * explícitamente role=section.
                 */
                if ( 'section' !== $role ) {

                        $errors[] = sprintf(
                                'Sección runtime "%s": role inválido o ausente.',
                                $term->slug
                        );

                        continue;
                }

                $section = array(
                        'name' => (string) $term->name,
                        'slug' => (string) $term->slug,
                );

                foreach ( $section_meta_map as $key => $meta_key ) {

                        if (
                                in_array(
                                        $key,
                                        $feature_keys,
                                        true
                                )
                        ) {
                                $section[ $key ] =
                                        metadata_exists(
                                                'term',
                                                $term->term_id,
                                                $meta_key
                                        )
                                        && '1' === (string) get_term_meta(
                                                $term->term_id,
                                                $meta_key,
                                                true
                                        );

                                continue;
                        }

                        if (
                                ! metadata_exists(
                                        'term',
                                        $term->term_id,
                                        $meta_key
                                )
                        ) {
                                continue;
                        }

                        $value = get_term_meta(
                                $term->term_id,
                                $meta_key,
                                true
                        );

                        if ( 'order' === $key ) {
                                $value = (int) $value;
                        } else {
                                $value = (string) $value;
                        }

                        $section[ $key ] = $value;
                }

                $definition['sections'][ $term->slug ] =
                        $section;
        }

        if ( 1 !== $core_count ) {
                $errors[] = sprintf(
                        'La instancia debe contener exactamente un core; se encontraron %d.',
                        $core_count
                );
        }

        $class_meta_map = array(
                'scope'    => 'bitacora_class_scope',
                'scope_id' => 'bitacora_class_scope_id',
                'order'    => 'bitacora_class_order',
                'state'    => 'bitacora_class_state',
        );

        foreach ( $class_terms as $term ) {

                $class = array(
                        'name' => (string) $term->name,
                        'slug' => (string) $term->slug,
                );

                foreach ( $class_meta_map as $key => $meta_key ) {

                        if (
                                ! metadata_exists(
                                        'term',
                                        $term->term_id,
                                        $meta_key
                                )
                        ) {
                                continue;
                        }

                        $value = get_term_meta(
                                $term->term_id,
                                $meta_key,
                                true
                        );

                        if ( 'order' === $key ) {
                                $value = (int) $value;
                        } else {
                                $value = (string) $value;
                        }

                        $class[ $key ] = $value;
                }

                $definition['classes'][ $term->slug ] =
                        $class;
        }

        if ( ! empty( $errors ) ) {
                return new WP_Error(
                        'bitacora_instance_structure_invalid',
                        'La estructura runtime de la instancia no es válida.',
                        $errors
                );
        }

        /*
         * La misma validación de frontera gobierna también la estructura
         * reconstruida desde runtime.
         */
        $validation = bitacora_validate_profile_definition(
                $definition
        );

        if ( empty( $validation['valid'] ) ) {
                return new WP_Error(
                        'bitacora_instance_definition_invalid',
                        'La estructura extraída no satisface el contrato Profile.',
                        isset( $validation['errors'] )
                                ? $validation['errors']
                                : array()
                );
        }

        return bitacora_get_canonical_profile_definition(
                $definition
        );
}


/**
 * Inspecciona la relación entre un candidato válido y la biblioteca.
 *
 * Resultados independientes:
 *
 * - duplicate:
 *   mismo label normalizado + misma definición canónica.
 *
 * - name_collision:
 *   mismo label normalizado + definición diferente.
 *
 * - similar:
 *   label diferente + misma cantidad y mismos nombres normalizados
 *   de secciones complementarias, ignorando orden.
 *
 * La similitud es informativa y nunca impide la adopción.
 */
function bitacora_inspect_profile_candidate( $candidate ) {

        $report = array(
                'duplicate'              => false,
                'duplicate_matches'      => array(),
                'name_collision'         => false,
                'name_collision_matches' => array(),
                'similar'                => false,
                'similar_matches'        => array(),
                'errors'                 => array(),
        );

        $validation = bitacora_validate_profile_candidate( $candidate );

        if ( empty( $validation['valid'] ) ) {

                $report['errors'] = isset( $validation['errors'] )
                        && is_array( $validation['errors'] )
                                ? $validation['errors']
                                : array( 'El candidato no es válido.' );

                return $report;
        }

        $candidate_definition =
                bitacora_get_canonical_profile_definition(
                        $candidate['definition']
                );

        if ( is_wp_error( $candidate_definition ) ) {

                $report['errors'][] =
                        $candidate_definition->get_error_message();

                return $report;
        }

        $candidate_label =
                bitacora_normalize_profile_comparison_text(
                        sanitize_text_field(
                                (string) $candidate['label']
                        )
                );

        $candidate_section_names = array();

        foreach ( $candidate_definition['sections'] as $section ) {

                if (
                        ! isset( $section['name'] )
                        || '' === trim( (string) $section['name'] )
                ) {
                        continue;
                }

                $candidate_section_names[] =
                        bitacora_normalize_profile_comparison_text(
                                $section['name']
                        );
        }

        sort(
                $candidate_section_names,
                SORT_STRING
        );

        foreach ( bitacora_get_profile_library() as $library_profile ) {

                if (
                        ! isset( $library_profile['id'] )
                        || ! is_string( $library_profile['id'] )
                ) {
                        continue;
                }

                $profile = bitacora_load_profile(
                        $library_profile['id']
                );

                if ( ! is_array( $profile ) ) {

                        $report['errors'][] = sprintf(
                                'No se pudo resolver el Profile "%s" durante la comparación.',
                                (string) $library_profile['id']
                        );

                        continue;
                }

                $profile_definition =
                        bitacora_get_canonical_profile_definition(
                                array(
                                        'core' => isset( $profile['core'] )
                                                && is_array( $profile['core'] )
                                                        ? $profile['core']
                                                        : array(),

                                        'sections' => isset( $profile['sections'] )
                                                && is_array( $profile['sections'] )
                                                        ? $profile['sections']
                                                        : array(),

                                        'classes' => isset( $profile['classes'] )
                                                && is_array( $profile['classes'] )
                                                        ? $profile['classes']
                                                        : array(),
                                )
                        );

                if ( is_wp_error( $profile_definition ) ) {

                        $report['errors'][] = sprintf(
                                'No se pudo canonicalizar el Profile "%s": %s',
                                (string) $library_profile['id'],
                                $profile_definition->get_error_message()
                        );

                        continue;
                }

                $profile_label =
                        bitacora_normalize_profile_comparison_text(
                                isset( $profile['label'] )
                                        ? $profile['label']
                                        : ''
                        );

                $label_equal = (
                        $candidate_label === $profile_label
                );

                $definition_equal = (
                        $candidate_definition === $profile_definition
                );

                $match = array(
                        'id'    => (string) $profile['id'],
                        'label' => isset( $profile['label'] )
                                ? (string) $profile['label']
                                : '',
                );

                if ( $label_equal && $definition_equal ) {

                        $report['duplicate_matches'][] = $match;
                        continue;
                }

                if ( $label_equal ) {

                        $report['name_collision_matches'][] = $match;
                        continue;
                }

                $profile_section_names = array();

                foreach ( $profile_definition['sections'] as $section ) {

                        if (
                                ! isset( $section['name'] )
                                || '' === trim( (string) $section['name'] )
                        ) {
                                continue;
                        }

                        $profile_section_names[] =
                                bitacora_normalize_profile_comparison_text(
                                        $section['name']
                                );
                }

                sort(
                        $profile_section_names,
                        SORT_STRING
                );

                if (
                        count( $candidate_section_names )
                        === count( $profile_section_names )
                        && $candidate_section_names
                        === $profile_section_names
                ) {
                        $report['similar_matches'][] = $match;
                }
        }

        $report['duplicate'] =
                ! empty( $report['duplicate_matches'] );

        $report['name_collision'] =
                ! empty( $report['name_collision_matches'] );

        $report['similar'] =
                ! empty( $report['similar_matches'] );

        return $report;
}


/**
 * Compatibilidad con la API histórica de comprobación de duplicados.
 *
 * Desde 2026-10-10 "duplicate" significa exclusivamente:
 * mismo label normalizado + misma definición estructural canónica.
 */
function bitacora_check_profile_candidate_duplicate( $candidate ) {

        $inspection = bitacora_inspect_profile_candidate(
                $candidate
        );

        return array(
                'duplicate' => ! empty(
                        $inspection['duplicate']
                ),
                'matches'   => isset(
                        $inspection['duplicate_matches']
                )
                        ? $inspection['duplicate_matches']
                        : array(),
                'errors'    => isset(
                        $inspection['errors']
                )
                        ? $inspection['errors']
                        : array(),
        );
}


function bitacora_get_profile_catalog() {

        $configured_profile_id = bitacora_get_configured_profile_id();

        $catalog = array();

        foreach ( bitacora_get_profile_library() as $library_profile ) {

                $profile_id = isset( $library_profile['id'] )
                        ? strtolower(
                                trim(
                                        (string) $library_profile['id']
                                )
                        )
                        : '';

                if ( ! wp_is_uuid( $profile_id, 4 ) ) {
                        continue;
                }

                $profile = bitacora_load_profile( $profile_id );

                if ( ! is_array( $profile ) ) {
                        continue;
                }

                $label = isset( $profile['label'] )
                        ? trim( (string) $profile['label'] )
                        : $profile_id;

                if ( '' === $label ) {
                        $label = $profile_id;
                }

                $bundled_file = get_stylesheet_directory()
                        . '/inc/profiles/'
                        . $profile_id
                        . '.php';

                $source = is_file( $bundled_file )
                        ? 'bundled'
                        : 'stored';

                $validation = bitacora_validate_profile(
                        $profile_id
                );

                $valid = ! empty(
                        $validation['valid']
                );

                $errors = isset( $validation['errors'] )
                        && is_array( $validation['errors'] )
                                ? $validation['errors']
                                : array();

                $in_use = (
                        '' !== $configured_profile_id
                        && $profile_id === $configured_profile_id
                );

                $deletable = (
                        'stored' === $source
                        && ! $in_use
                );

                $catalog[] = array(
                        'id'        => $profile_id,
                        'label'     => $label,
                        'source'    => $source,
                        'valid'     => $valid,
                        'in_use'    => $in_use,
                        'deletable' => $deletable,
                        'errors'    => $errors,
                );
        }

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
 * Elimina definitivamente un perfil persistente que no está en uso.
 *
 * Los perfiles incluidos, actualmente en uso o con una identidad ambigua
 * no pueden eliminarse mediante esta API.
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
                        'post_status'    => 'publish',
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

        $post_id = (int) $post_ids[0];

        /*
         * Eliminación definitiva deliberada:
         * un perfil persistente fuera de uso es sólo un esqueleto de
         * configuración y puede eliminarse definitivamente.
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
