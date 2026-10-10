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
