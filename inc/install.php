<?php
/**
 * Instalación inicial de Bitácora.
 *
 * - Crea la estructura mínima de páginas.
 * - Configura la portada en instalaciones nuevas.
 * - Ajusta el nombre genérico de WordPress.
 * - Verifica dependencias funcionales.
 *
 * La instalación es idempotente:
 * no duplica páginas ni pisa configuraciones existentes.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Devuelve el identificador del perfil actualmente en uso.
 *
 * Una opción ausente o vacía representa una Bitácora sin configurar.
 */
function bitacora_get_configured_profile_id() {

        $profile_id = get_option( 'bitacora_configured_profile', '' );

        if ( ! is_string( $profile_id ) ) {
                return '';
        }

        return sanitize_key( $profile_id );
}


/**
 * Indica si Bitácora tiene una configuración vigente.
 */
function bitacora_is_configured() {
        return '' !== bitacora_get_configured_profile_id();
}


/**
 * Devuelve el instante en que la instancia actual quedó puesta en uso.
 *
 * Se persiste como timestamp Unix UTC. Las instancias creadas antes de
 * incorporar este dato pueden devolver 0.
 */
function bitacora_get_configured_at() {

    $configured_at = get_option(
        'bitacora_configured_at',
        0
    );

    return absint( $configured_at );
}


/**
 * Devuelve el estado básico de configuración.
 */
function bitacora_get_configuration_state() {
        return bitacora_is_configured()
                ? 'configured'
                : 'unconfigured';
}

/**
 * Devuelve el estado operativo de la instancia.
 *
 * La ausencia de opción es deliberadamente compatible con todas las
 * instancias existentes: una Bitácora configurada se considera ACTIVE.
 *
 * Estados:
 * - unconfigured: no existe instancia materializada;
 * - active: instancia disponible para uso normal;
 * - paused: ventana crítica de mantenimiento estructural.
 */
function bitacora_get_instance_runtime_state() {

        if ( ! bitacora_is_configured() ) {
                return 'unconfigured';
        }

        $state = get_option(
                'bitacora_instance_runtime_state',
                'active'
        );

        if (
                ! is_string( $state )
                || ! in_array(
                        $state,
                        array(
                                'active',
                                'paused',
                        ),
                        true
                )
        ) {
                /*
                 * Fail-open sólo para compatibilidad de una opción inválida
                 * que no debería existir. La transición a PAUSED se realiza
                 * exclusivamente mediante las primitivas controladas.
                 */
                return 'active';
        }

        return $state;
}


/**
 * Devuelve el usuario que posee una PAUSA de mantenimiento.
 *
 * Fuera de PAUSED no existe owner operativo aunque hubiera quedado una
 * opción residual; así el estado ACTIVE nunca adquiere exclusividad.
 */
function bitacora_get_instance_maintenance_owner() {

        if (
                'paused'
                !== bitacora_get_instance_runtime_state()
        ) {
                return 0;
        }

        return absint(
                get_option(
                        'bitacora_instance_maintenance_owner',
                        0
                )
        );
}


/**
 * Comprueba si la conexión MySQL actual posee el advisory lock
 * de mantenimiento estructural.
 *
 * @return bool|WP_Error
 */
function bitacora_instance_maintenance_lock_is_owned() {

        global $wpdb;

        $lock_name =
                'bitacora_instance_maintenance';

        $owner_connection = $wpdb->get_var(
                $wpdb->prepare(
                        'SELECT IS_USED_LOCK(%s)',
                        $lock_name
                )
        );

        /*
         * NULL significa que el lock no está tomado.
         */
        if ( null === $owner_connection ) {
                return false;
        }

        $current_connection =
                $wpdb->get_var(
                        'SELECT CONNECTION_ID()'
                );

        if (
                null === $current_connection
                || '' === (string) $current_connection
        ) {
                return new WP_Error(
                        'bitacora_instance_maintenance_connection_unknown',
                        'No se pudo identificar la conexión MySQL actual.'
                );
        }

        return (
                (string) $owner_connection
                === (string) $current_connection
        );
}


/**
 * Adquiere el advisory lock de mantenimiento estructural.
 *
 * El lock es de conexión MySQL y por tanto debe mantenerse dentro del mismo
 * request que ejecutará PAUSA -> APPLY/RECOVERY -> ACTIVE.
 *
 * PAUSED puede sobrevivir al request si una recuperación falla; el advisory
 * lock no. La exclusividad persistente en ese caso queda representada por
 * maintenance_owner y deberá respetarse en cualquier operación posterior.
 *
 * @return true|WP_Error
 */
function bitacora_acquire_instance_maintenance_lock(
        $timeout = 10
) {

        global $wpdb;

        $timeout = max(
                0,
                (int) $timeout
        );

        $lock_name =
                'bitacora_instance_maintenance';

        $acquired = $wpdb->get_var(
                $wpdb->prepare(
                        'SELECT GET_LOCK(%s, %d)',
                        $lock_name,
                        $timeout
                )
        );

        if ( '1' !== (string) $acquired ) {
                return new WP_Error(
                        'bitacora_instance_maintenance_lock_unavailable',
                        'No se pudo adquirir el bloqueo de mantenimiento de la instancia.'
                );
        }

        return true;
}


/**
 * Libera el advisory lock de mantenimiento estructural.
 *
 * @return true|WP_Error
 */
function bitacora_release_instance_maintenance_lock() {

        global $wpdb;

        $released = $wpdb->get_var(
                $wpdb->prepare(
                        'SELECT RELEASE_LOCK(%s)',
                        'bitacora_instance_maintenance'
                )
        );

        if ( '1' !== (string) $released ) {
                return new WP_Error(
                        'bitacora_instance_maintenance_lock_not_released',
                        'No se pudo liberar el bloqueo de mantenimiento de la instancia.'
                );
        }

        return true;
}


/**
 * Pone la instancia en PAUSED para una sección crítica ya serializada.
 *
 * Esta función NO adquiere el advisory lock. El caller debe haber adquirido
 * bitacora_instance_maintenance y conservarlo durante toda la operación.
 *
 * El owner se persiste antes que PAUSED para no publicar un estado pausado
 * sin dueño.
 */
function bitacora_pause_instance_for_maintenance(
        $owner_user_id
) {

        $owner_user_id = absint(
                $owner_user_id
        );

        if ( ! bitacora_is_configured() ) {
                return new WP_Error(
                        'bitacora_instance_not_configured',
                        'Bitácora no tiene una instancia configurada para pausar.'
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
                        'La PAUSA requiere poseer el bloqueo de mantenimiento de la instancia.'
                );
        }

        if ( ! $owner_user_id ) {
                return new WP_Error(
                        'bitacora_instance_maintenance_owner_required',
                        'Debe indicarse un usuario responsable del mantenimiento.'
                );
        }

        $owner = get_user_by(
                'id',
                $owner_user_id
        );

        if ( ! $owner instanceof WP_User ) {
                return new WP_Error(
                        'bitacora_instance_maintenance_owner_invalid',
                        'El usuario responsable del mantenimiento no existe.'
                );
        }

        $state =
                bitacora_get_instance_runtime_state();

        if ( 'paused' === $state ) {

                $current_owner =
                        bitacora_get_instance_maintenance_owner();

                if (
                        $owner_user_id
                        !== $current_owner
                ) {
                        return new WP_Error(
                                'bitacora_instance_maintenance_owned',
                                'La instancia ya está en mantenimiento por otro usuario.',
                                array(
                                        'owner_user_id' =>
                                                $current_owner,
                                )
                        );
                }

                return array(
                        'state'         => 'paused',
                        'owner_user_id' => $owner_user_id,
                        'changed'       => false,
                );
        }

        if ( 'active' !== $state ) {
                return new WP_Error(
                        'bitacora_instance_runtime_state_invalid',
                        sprintf(
                                'La instancia no puede pausarse desde el estado "%s".',
                                (string) $state
                        )
                );
        }

        $owner_saved = update_option(
                'bitacora_instance_maintenance_owner',
                $owner_user_id,
                false
        );

        /*
         * update_option() devuelve false también cuando no hay cambio.
         * Verificar siempre por lectura.
         */
        if (
                $owner_user_id
                !== absint(
                        get_option(
                                'bitacora_instance_maintenance_owner',
                                0
                        )
                )
        ) {
                return new WP_Error(
                        'bitacora_instance_maintenance_owner_not_saved',
                        'No se pudo registrar el responsable del mantenimiento.'
                );
        }

        update_option(
                'bitacora_instance_runtime_state',
                'paused',
                false
        );

        if (
                'paused'
                !== get_option(
                        'bitacora_instance_runtime_state',
                        ''
                )
        ) {
                delete_option(
                        'bitacora_instance_maintenance_owner'
                );

                return new WP_Error(
                        'bitacora_instance_pause_not_saved',
                        'No se pudo registrar la PAUSA de mantenimiento.'
                );
        }

        return array(
                'state'         => 'paused',
                'owner_user_id' => $owner_user_id,
                'changed'       => true,
        );
}


/**
 * Devuelve la instancia a ACTIVE después de APPLY o recuperación verificada.
 *
 * Sólo el owner de la PAUSA puede liberarla.
 *
 * Esta función NO libera el advisory lock. El caller debe reanudar primero
 * y liberar el lock como último paso.
 */
function bitacora_resume_instance_from_maintenance(
        $owner_user_id
) {

        $owner_user_id = absint(
                $owner_user_id
        );

        if ( ! bitacora_is_configured() ) {
                return new WP_Error(
                        'bitacora_instance_not_configured',
                        'Bitácora no tiene una instancia configurada para reanudar.'
                );
        }

        $state =
                bitacora_get_instance_runtime_state();

        if ( 'active' === $state ) {

                return array(
                        'state'         => 'active',
                        'owner_user_id' => 0,
                        'changed'       => false,
                );
        }

        if ( 'paused' !== $state ) {
                return new WP_Error(
                        'bitacora_instance_runtime_state_invalid',
                        sprintf(
                                'La instancia no puede reanudarse desde el estado "%s".',
                                (string) $state
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
                        'Reanudar una instancia en PAUSA requiere poseer el bloqueo de mantenimiento.'
                );
        }

        $current_owner =
                bitacora_get_instance_maintenance_owner();

        if (
                ! $owner_user_id
                || $owner_user_id
                !== $current_owner
        ) {
                return new WP_Error(
                        'bitacora_instance_maintenance_owner_mismatch',
                        'Sólo el responsable actual del mantenimiento puede reanudar la instancia.',
                        array(
                                'owner_user_id' =>
                                        $current_owner,
                        )
                );
        }

        /*
         * ACTIVE vuelve a ser el estado implícito.
         *
         * Primero desaparece PAUSED; después se limpia el owner residual.
         */
        delete_option(
                'bitacora_instance_runtime_state'
        );

        if (
                'active'
                !== bitacora_get_instance_runtime_state()
        ) {
                return new WP_Error(
                        'bitacora_instance_resume_not_saved',
                        'No se pudo devolver la instancia a ACTIVE.'
                );
        }

        delete_option(
                'bitacora_instance_maintenance_owner'
        );

        if (
                0 !== absint(
                        get_option(
                                'bitacora_instance_maintenance_owner',
                                0
                        )
                )
        ) {
                return new WP_Error(
                        'bitacora_instance_maintenance_owner_not_cleared',
                        'La instancia volvió a ACTIVE pero no pudo limpiarse el responsable de mantenimiento.'
                );
        }

        return array(
                'state'         => 'active',
                'owner_user_id' => 0,
                'changed'       => true,
        );
}


/**
 * Indica si el request actual puede modificar el runtime mientras Bitácora
 * se encuentra en mantenimiento.
 *
 * ACTIVE:
 *     escritura normal permitida.
 *
 * PAUSED:
 *     sólo puede escribir la conexión MySQL que posee el advisory lock
 *     bitacora_instance_maintenance.
 *
 * Esto permite que APPLY / ROLLBACK utilicen las APIs normales de WordPress,
 * pero bloquea requests concurrentes o posteriores que no forman parte de
 * la sección crítica.
 */
function bitacora_instance_runtime_mutation_is_allowed() {

        if (
                'paused'
                !== bitacora_get_instance_runtime_state()
        ) {
                return true;
        }

        $owned =
                bitacora_instance_maintenance_lock_is_owned();

        return true === $owned;
}


/**
 * Durante PAUSED retira las capabilities de escritura ordinaria.
 *
 * manage_bitacora_profiles se conserva deliberadamente para que la futura
 * UI de mantenimiento siga siendo accesible.
 */
add_filter(
        'user_has_cap',
        'bitacora_block_runtime_write_capabilities_during_maintenance',
        999,
        4
);

function bitacora_block_runtime_write_capabilities_during_maintenance(
        $allcaps,
        $caps,
        $args,
        $user
) {

        if (
                bitacora_instance_runtime_mutation_is_allowed()
        ) {
                return $allcaps;
        }

        $blocked = array(
                /*
                 * bitacora_item.
                 */
                'edit_bitacora_contents',
                'publish_bitacora_contents',
                'edit_published_bitacora_contents',
                'edit_private_bitacora_contents',
                'edit_others_bitacora_contents',
                'delete_bitacora_contents',
                'delete_published_bitacora_contents',
                'delete_private_bitacora_contents',
                'delete_others_bitacora_contents',

                /*
                 * Media.
                 */
                'upload_files',

                /*
                 * Comentarios.
                 */
                'moderate_comments',

                /*
                 * Páginas.
                 *
                 * Las páginas materializadas de sección forman parte del
                 * runtime estructural. Durante la breve PAUSA se bloquea
                 * toda escritura de páginas para evitar colisiones.
                 */
                'edit_pages',
                'edit_others_pages',
                'publish_pages',
                'edit_published_pages',
                'edit_private_pages',
                'delete_pages',
                'delete_others_pages',
                'delete_published_pages',
                'delete_private_pages',

                /*
                 * Usar/Cerrar Profile modifica la instancia materializada.
                 */
                'use_bitacora_profiles',
        );

        foreach ( $blocked as $capability ) {
                $allcaps[ $capability ] = false;
        }

        return $allcaps;
}


/**
 * Defensa por objeto para posts, attachments y comentarios existentes.
 */
add_filter(
        'map_meta_cap',
        'bitacora_block_runtime_object_capabilities_during_maintenance',
        999,
        4
);

function bitacora_block_runtime_object_capabilities_during_maintenance(
        $caps,
        $cap,
        $user_id,
        $args
) {

        if (
                bitacora_instance_runtime_mutation_is_allowed()
        ) {
                return $caps;
        }

        if (
                in_array(
                        $cap,
                        array(
                                'edit_post',
                                'delete_post',
                        ),
                        true
                )
                && ! empty( $args[0] )
        ) {

                $post = get_post(
                        absint( $args[0] )
                );

                if (
                        $post instanceof WP_Post
                        && in_array(
                                $post->post_type,
                                array(
                                        'bitacora_item',
                                        'attachment',
                                        'page',
                                ),
                                true
                        )
                ) {
                        return array(
                                'do_not_allow',
                        );
                }
        }

        if (
                in_array(
                        $cap,
                        array(
                                'edit_comment',
                                'delete_comment',
                        ),
                        true
                )
                && ! empty( $args[0] )
        ) {

                $comment = get_comment(
                        absint( $args[0] )
                );

                if ( $comment instanceof WP_Comment ) {

                        $post = get_post(
                                $comment->comment_post_ID
                        );

                        if (
                                $post instanceof WP_Post
                                && 'bitacora_item'
                                        === $post->post_type
                        ) {
                                return array(
                                        'do_not_allow',
                                );
                        }
                }
        }

        return $caps;
}


/**
 * Última barrera antes de wp_insert_post()/wp_update_post().
 *
 * Cierra la pequeña ventana en la que un request pudo haber superado una
 * comprobación de capabilities justo antes de que otro request activara
 * PAUSED.
 *
 * APPLY / ROLLBACK pasan porque poseen el advisory lock.
 */
add_filter(
        'wp_insert_post_data',
        'bitacora_guard_runtime_post_write_during_maintenance',
        999,
        4
);

function bitacora_guard_runtime_post_write_during_maintenance(
        $data,
        $postarr,
        $unsanitized_postarr,
        $update
) {

        if (
                bitacora_instance_runtime_mutation_is_allowed()
        ) {
                return $data;
        }

        $post_type = isset( $data['post_type'] )
                ? (string) $data['post_type']
                : '';

        if (
                ! in_array(
                        $post_type,
                        array(
                                'bitacora_item',
                                'attachment',
                                'page',
                        ),
                        true
                )
        ) {
                return $data;
        }

        wp_die(
                esc_html__(
                        'Bitácora está temporalmente en mantenimiento. Intentá nuevamente en unos instantes.',
                        'bitacora'
                ),
                esc_html__(
                        'Bitácora en mantenimiento',
                        'bitacora'
                ),
                array(
                        'response' => 503,
                )
        );
}


/**
 * Bloquea eliminación física durante PAUSED.
 *
 * El executor puede eliminar páginas creadas por un APPLY fallido porque
 * ejecuta con el advisory lock adquirido.
 */
add_filter(
        'pre_delete_post',
        'bitacora_guard_runtime_post_delete_during_maintenance',
        999,
        3
);

add_filter(
        'pre_delete_attachment',
        'bitacora_guard_runtime_post_delete_during_maintenance',
        999,
        3
);

function bitacora_guard_runtime_post_delete_during_maintenance(
        $delete,
        $post,
        $force_delete = false
) {

        if (
                null !== $delete
                || bitacora_instance_runtime_mutation_is_allowed()
        ) {
                return $delete;
        }

        if (
                $post instanceof WP_Post
                && in_array(
                        $post->post_type,
                        array(
                                'bitacora_item',
                                'attachment',
                                'page',
                        ),
                        true
                )
        ) {
                return false;
        }

        return $delete;
}


/**
 * Bloquea trash/untrash durante PAUSED.
 */
add_filter(
        'pre_trash_post',
        'bitacora_guard_runtime_post_status_transition_during_maintenance',
        999,
        3
);

add_filter(
        'pre_untrash_post',
        'bitacora_guard_runtime_post_status_transition_during_maintenance',
        999,
        3
);

function bitacora_guard_runtime_post_status_transition_during_maintenance(
        $check,
        $post,
        $extra = null
) {

        if (
                null !== $check
                || bitacora_instance_runtime_mutation_is_allowed()
        ) {
                return $check;
        }

        if (
                $post instanceof WP_Post
                && in_array(
                        $post->post_type,
                        array(
                                'bitacora_item',
                                'attachment',
                                'page',
                        ),
                        true
                )
        ) {
                return false;
        }

        return $check;
}


/**
 * Bloquea creación manual/programática de términos estructurales durante
 * PAUSED salvo dentro de la conexión que ejecuta mantenimiento.
 */
add_filter(
        'pre_insert_term',
        'bitacora_guard_runtime_term_insert_during_maintenance',
        999,
        2
);

function bitacora_guard_runtime_term_insert_during_maintenance(
        $term,
        $taxonomy
) {

        if (
                bitacora_instance_runtime_mutation_is_allowed()
                || ! in_array(
                        $taxonomy,
                        array(
                                'bitacora_section',
                                'bitacora_class',
                        ),
                        true
                )
        ) {
                return $term;
        }

        return new WP_Error(
                'bitacora_instance_runtime_paused',
                'La estructura de Bitácora no puede modificarse durante la PAUSA.'
        );
}


/**
 * Las taxonomías estructurales usan manage_options.
 *
 * No se retira manage_options globalmente porque también sostiene tareas
 * administrativas ajenas a Bitácora. En cambio se bloquean específicamente
 * sus pantallas mientras la instancia permanece PAUSED.
 */
add_action(
        'admin_init',
        'bitacora_block_structural_taxonomy_admin_during_maintenance',
        0
);

function bitacora_block_structural_taxonomy_admin_during_maintenance() {

        if (
                bitacora_instance_runtime_mutation_is_allowed()
        ) {
                return;
        }

        $current = isset( $_SERVER['PHP_SELF'] )
                ? basename(
                        (string) $_SERVER['PHP_SELF']
                )
                : '';

        if (
                ! in_array(
                        $current,
                        array(
                                'edit-tags.php',
                                'term.php',
                        ),
                        true
                )
        ) {
                return;
        }

        $taxonomy = isset( $_REQUEST['taxonomy'] )
                ? sanitize_key(
                        wp_unslash(
                                $_REQUEST['taxonomy']
                        )
                )
                : '';

        if (
                ! in_array(
                        $taxonomy,
                        array(
                                'bitacora_section',
                                'bitacora_class',
                        ),
                        true
                )
        ) {
                return;
        }

        wp_die(
                esc_html__(
                        'La estructura de Bitácora está temporalmente bloqueada por mantenimiento.',
                        'bitacora'
                ),
                esc_html__(
                        'Bitácora en mantenimiento',
                        'bitacora'
                ),
                array(
                        'response' => 503,
                )
        );
}


/**
 * Comprueba las dependencias funcionales del theme.
 *
 * Devuelve únicamente problemas:
 * [
 *   [
 *     'plugin' => '...',
 *     'label'  => '...',
 *     'status' => 'missing|inactive',
 *   ],
 * ]
 */
function obras_theme_check_dependencies() {

	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$dependencies = array(
		'advanced-custom-fields/acf.php' => 'Advanced Custom Fields',
		'classic-editor/classic-editor.php' => 'Classic Editor',
	);

	$problems = array();

	foreach ( $dependencies as $plugin_file => $label ) {

		$installed = file_exists( WP_PLUGIN_DIR . '/' . $plugin_file );

		$active = $installed && is_plugin_active( $plugin_file );

		if (
			! $active
			&& is_multisite()
			&& function_exists( 'is_plugin_active_for_network' )
		) {
			$active = is_plugin_active_for_network( $plugin_file );
		}

		if ( ! $installed ) {

			$problems[] = array(
				'plugin' => $plugin_file,
				'label'  => $label,
				'status' => 'missing',
			);

		} elseif ( ! $active ) {

			$problems[] = array(
				'plugin' => $plugin_file,
				'label'  => $label,
				'status' => 'inactive',
			);
		}
	}

	update_option( 'obras_theme_dependency_status', $problems );

	return $problems;
}


/**
 * Aviso en wp-admin cuando falta alguna dependencia.
 */
function obras_theme_dependency_notice() {

	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$problems = obras_theme_check_dependencies();

	if ( empty( $problems ) ) {
		return;
	}

	$messages = array();

	foreach ( $problems as $problem ) {

		if ( 'missing' === $problem['status'] ) {
			$messages[] = sprintf(
				'%s no está instalado.',
				$problem['label']
			);
		} else {
			$messages[] = sprintf(
				'%s está instalado pero no está activo.',
				$problem['label']
			);
		}
	}

	printf(
		'<div class="notice notice-warning"><p><strong>Bitácora:</strong> %s</p></div>',
		esc_html( implode( ' ', $messages ) )
	);
}

add_action( 'admin_notices', 'obras_theme_dependency_notice' );


/**
 * Capabilities primitivas gestionadas por el modelo de contenido 0.2.0.
 *
 * Se aplican al CPT unificado:
 * - bitacora_item
 */
function bitacora_get_managed_content_capabilities() {
        return array(
                'edit_bitacora_contents',
                'edit_others_bitacora_contents',
                'publish_bitacora_contents',
                'read_private_bitacora_contents',
                'delete_bitacora_contents',
                'delete_private_bitacora_contents',
                'delete_published_bitacora_contents',
                'delete_others_bitacora_contents',
                'edit_private_bitacora_contents',
                'edit_published_bitacora_contents',
        );
}

/**
 * Capabilities gestionadas por Bitácora para administrar perfiles.
 */
function bitacora_get_managed_profile_capabilities() {
        return array(
                'manage_bitacora_profiles',
                'use_bitacora_profiles',
        );
}

/**
 * Crea y sincroniza los roles propios de Bitácora.
 *
 * Autor Bitácora:
 * - administra contenido propio;
 * - no administra contenido ajeno.
 *
 * Supervisor Bitácora:
 * - posee las capacidades del Autor;
 * - además puede gestionar contenido ajeno y leer privados ajenos.
 *
 * Administrator recibe todas las capabilities gestionadas por Bitácora,
 * sin alterar sus capacidades administrativas de WordPress.
 */
function bitacora_seed_content_roles() {

        $author_caps = array(
                'read',
                'upload_files',
                'edit_bitacora_contents',
                'publish_bitacora_contents',
                'edit_published_bitacora_contents',
                'edit_private_bitacora_contents',
                'delete_bitacora_contents',
                'delete_published_bitacora_contents',
                'delete_private_bitacora_contents',
        );

        $supervisor_caps = array_merge(
                $author_caps,
                array(
                        'edit_others_bitacora_contents',
                        'delete_others_bitacora_contents',
                        'read_private_bitacora_contents',
                        'manage_bitacora_profiles',
                        'use_bitacora_profiles',
                )
        );

        $definitions = array(
                'bitacora_author' => array(
                        'label' => 'Autor Bitácora',
                        'caps'  => $author_caps,
                ),
                'bitacora_supervisor' => array(
                        'label' => 'Supervisor Bitácora',
                        'caps'  => $supervisor_caps,
                ),
        );

        $managed_caps = array_merge(
                bitacora_get_managed_content_capabilities(),
                bitacora_get_managed_profile_capabilities()
        );

        $report = array(
                'created'      => array(),
                'caps_added'   => array(),
                'caps_removed' => array(),
                'errors'       => array(),
                'changed'      => false,
        );

        foreach ( $definitions as $role_slug => $definition ) {

                $role = get_role( $role_slug );

                if ( ! $role ) {
                        add_role(
                                $role_slug,
                                $definition['label'],
                                array()
                        );

                        $role = get_role( $role_slug );

                        if ( ! $role ) {
                                $report['errors'][] =
                                        'No se pudo crear el rol '
                                        . $role_slug
                                        . '.';
                                continue;
                        }

                        $report['created'][] = $role_slug;
                        $report['changed']   = true;
                }

                foreach ( $definition['caps'] as $cap ) {
                        if (
                                ! isset( $role->capabilities[ $cap ] )
                                || ! $role->capabilities[ $cap ]
                        ) {
                                $role->add_cap( $cap, true );

                                $report['caps_added'][] =
                                        $role_slug . ':' . $cap;
                                $report['changed'] = true;
                        }
                }

                /*
                 * Dentro de nuestra propia familia bitacora_*,
                 * eliminar capacidades que no correspondan al rol.
                 *
                 * No se tocan capabilities ajenas a Bitácora.
                 */
                foreach ( $managed_caps as $cap ) {
                        if (
                                ! in_array(
                                        $cap,
                                        $definition['caps'],
                                        true
                                )
                                && array_key_exists(
                                        $cap,
                                        $role->capabilities
                                )
                        ) {
                                $role->remove_cap( $cap );

                                $report['caps_removed'][] =
                                        $role_slug . ':' . $cap;
                                $report['changed'] = true;
                        }
                }
        }

        /*
         * El Administrator conserva su rol WordPress normal,
         * pero recibe todas las capabilities gestionadas por Bitácora.
         */
        $administrator = get_role( 'administrator' );

        if ( ! $administrator ) {
                $report['errors'][] =
                        'No se encontró el rol administrator.';
        } else {
                foreach ( $managed_caps as $cap ) {
                        if (
                                ! isset(
                                        $administrator->capabilities[ $cap ]
                                )
                                || ! $administrator->capabilities[ $cap ]
                        ) {
                                $administrator->add_cap( $cap, true );

                                $report['caps_added'][] =
                                        'administrator:' . $cap;
                                $report['changed'] = true;
                        }
                }
        }

        return $report;
}


/**
 * Configura Bitácora a partir de un perfil.
 *
 * Sólo puede ejecutarse cuando Bitácora está sin configurar.
 * El perfil queda en uso únicamente después de completar
 * satisfactoriamente toda la configuración.
 */
/**
 * Desmonta por completo la Bitácora actualmente en uso.
 *
 * El perfil persiste como definición reutilizable, pero se eliminan
 * todos los datos y toda la estructura materializada de su uso actual.
 *
 * La infraestructura general de WordPress y de Bitácora permanece:
 * usuarios, roles, perfiles almacenados, Inicio y Más secciones.
 */
function bitacora_remove_configured_profile() {

    $profile_id = bitacora_get_configured_profile_id();

    if ( '' === $profile_id ) {
        return new WP_Error(
            'bitacora_profile_not_configured',
            'Bitácora no tiene un perfil en uso para desafectar.'
        );
    }

    $report = array(
        'profile'             => $profile_id,
        'items_deleted'       => 0,
        'attachments_deleted' => 0,
        'pages_deleted'       => 0,
        'classes_deleted'     => 0,
        'sections_deleted'    => 0,
        'errors'              => array(),
        'configured'          => true,
    );

    $all_post_statuses = array_values(
        get_post_stati( array(), 'names' )
    );

    /*
     * 1. Contenido producido por la Bitácora en uso.
     *
     * wp_delete_post( ..., true ) elimina también postmeta,
     * relaciones de taxonomía y comentarios asociados.
     */
    $item_ids = get_posts(
        array(
            'post_type'      => 'bitacora_item',
            'post_status'    => $all_post_statuses,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        )
    );

    foreach ( $item_ids as $item_id ) {

        if ( ! wp_delete_post( $item_id, true ) ) {
            $report['errors'][] = sprintf(
                'No se pudo eliminar el bitacora_item %d.',
                $item_id
            );
            continue;
        }

        $report['items_deleted']++;
    }

    /*
     * 2. Biblioteca de medios.
     *
     * Una instalación de Bitácora mantiene una sola Bitácora
     * materializada. Al desafectarla no quedan medios de ese uso.
     */
    $attachment_ids = get_posts(
        array(
            'post_type'      => 'attachment',
            'post_status'    => $all_post_statuses,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        )
    );

    foreach ( $attachment_ids as $attachment_id ) {

        if ( ! wp_delete_attachment( $attachment_id, true ) ) {
            $report['errors'][] = sprintf(
                'No se pudo eliminar el attachment %d.',
                $attachment_id
            );
            continue;
        }

        $report['attachments_deleted']++;
    }

    /*
     * 3. Páginas pertenecientes a secciones materializadas.
     *
     * Inicio y Más secciones son infraestructura general y por eso
     * no contienen el shortcode bitacora_section.
     */
    $pages = get_posts(
        array(
            'post_type'      => 'page',
            'post_status'    => $all_post_statuses,
            'posts_per_page' => -1,
        )
    );

    foreach ( $pages as $page ) {

        if (
            ! preg_match(
                '/\[bitacora_section(?:\s|\])/i',
                (string) $page->post_content
            )
        ) {
            continue;
        }

        if ( ! wp_delete_post( $page->ID, true ) ) {
            $report['errors'][] = sprintf(
                'No se pudo eliminar la página de sección %d.',
                $page->ID
            );
            continue;
        }

        $report['pages_deleted']++;
    }

    /*
     * 4. Tipos materializados.
     */
    $class_ids = get_terms(
        array(
            'taxonomy'   => 'bitacora_class',
            'hide_empty' => false,
            'fields'     => 'ids',
        )
    );

    if ( is_wp_error( $class_ids ) ) {

        $report['errors'][] = $class_ids->get_error_message();

    } else {

        foreach ( $class_ids as $class_id ) {

            $deleted = wp_delete_term(
                $class_id,
                'bitacora_class'
            );

            if ( is_wp_error( $deleted ) || false === $deleted ) {
                $report['errors'][] = sprintf(
                    'No se pudo eliminar el tipo %d.',
                    $class_id
                );
                continue;
            }

            $report['classes_deleted']++;
        }
    }

    /*
     * 5. Secciones materializadas.
     */
    $section_ids = get_terms(
        array(
            'taxonomy'   => 'bitacora_section',
            'hide_empty' => false,
            'fields'     => 'ids',
        )
    );

    if ( is_wp_error( $section_ids ) ) {

        $report['errors'][] = $section_ids->get_error_message();

    } else {

        foreach ( $section_ids as $section_id ) {

            $deleted = wp_delete_term(
                $section_id,
                'bitacora_section'
            );

            if ( is_wp_error( $deleted ) || false === $deleted ) {
                $report['errors'][] = sprintf(
                    'No se pudo eliminar la sección %d.',
                    $section_id
                );
                continue;
            }

            $report['sections_deleted']++;
        }
    }

    /*
     * Sólo liberar el estado EN USO cuando el desmontaje terminó
     * completamente. Si hubo errores, conservarlo permite corregir
     * el problema y volver a ejecutar esta operación.
     */
    if ( empty( $report['errors'] ) ) {

        delete_option( 'bitacora_configured_at' );

        if ( 0 !== bitacora_get_configured_at() ) {
            $report['errors'][] =
                'No se pudo eliminar la fecha de puesta en uso.';
        }
    }

    if ( empty( $report['errors'] ) ) {
        delete_option( 'bitacora_configured_profile' );
    }

    $report['configured'] =
        '' !== bitacora_get_configured_profile_id();

    if (
        empty( $report['errors'] )
        && $report['configured']
    ) {
        $report['errors'][] =
            'No se pudo eliminar el estado de perfil EN USO.';
    }

    return $report;
}


function bitacora_configure_profile( $profile_id ) {

        $profile_id = sanitize_key( (string) $profile_id );

        if ( '' === $profile_id ) {
                return new WP_Error(
                        'bitacora_profile_required',
                        'Debe indicarse un perfil para configurar Bitácora.'
                );
        }

        if ( bitacora_is_configured() ) {
                return new WP_Error(
                        'bitacora_already_configured',
                        sprintf(
                                'Bitácora ya está configurada con el perfil "%s".',
                                bitacora_get_configured_profile_id()
                        )
                );
        }

        if (
                ! bitacora_profile_library_contains( $profile_id )
                || ! bitacora_load_profile( $profile_id )
        ) {
                return new WP_Error(
                        'bitacora_profile_not_found',
                        sprintf(
                                'No se pudo cargar el perfil aceptado "%s".',
                                $profile_id
                        )
                );
        }

        $profile_validation = bitacora_validate_profile( $profile_id );

        if ( empty( $profile_validation['valid'] ) ) {
                return new WP_Error(
                        'bitacora_profile_invalid',
                        sprintf(
                                'El perfil aceptado "%s" no tiene una definición válida.',
                                $profile_id
                        ),
                        $profile_validation
                );
        }

        $dependency_problems = obras_theme_check_dependencies();

        if ( ! empty( $dependency_problems ) ) {
                return new WP_Error(
                        'bitacora_dependencies_not_ready',
                        'No se puede configurar Bitácora hasta resolver sus dependencias.',
                        $dependency_problems
                );
        }

        $section_report = bitacora_seed_profile_sections(
                $profile_id
        );

        if ( is_wp_error( $section_report ) ) {
                error_log(
                        'Bitácora: '
                        . $section_report->get_error_message()
                );

                return $section_report;
        }

        if ( ! empty( $section_report['errors'] ) ) {
                $error = new WP_Error(
                        'bitacora_install_section_seed_errors',
                        implode(
                                ' ',
                                $section_report['errors']
                        )
                );

                error_log(
                        'Bitácora: '
                        . $error->get_error_message()
                );

                return $error;
        }

        $class_report = bitacora_seed_profile_classes(
                $profile_id
        );

        if ( is_wp_error( $class_report ) ) {
                error_log(
                        'Bitácora: '
                        . $class_report->get_error_message()
                );

                return $class_report;
        }

        if ( ! empty( $class_report['errors'] ) ) {
                $error = new WP_Error(
                        'bitacora_install_class_seed_errors',
                        implode(
                                ' ',
                                $class_report['errors']
                        )
                );

                error_log(
                        'Bitácora: '
                        . $error->get_error_message()
                );

                return $error;
        }


	$role_report = bitacora_seed_content_roles();

	if ( ! empty( $role_report['errors'] ) ) {
	        $error = new WP_Error(
	                'bitacora_install_role_seed_errors',
	                implode(
	                        ' ',
	                        $role_report['errors']
	                )
	        );

	        error_log(
	                'Bitácora: '
	                . $error->get_error_message()
	        );

	        return $error;
	}


    /*
     * Páginas estructurales independientes de las secciones.
     */
    $pages = array(

            'inicio' => array(
                    'title'   => 'Inicio',
                    'content' => '[obras_dashboard]',
            ),

            'auxiliar' => array(
                    'title'   => 'Más secciones',
                    'content' => '[bitacora_more_sections]',
            ),
    );

    /*
     * Cada sección activa —core incluido— tiene su página frontend.
     *
     * El installer no conoce slugs concretos de secciones:
     * la estructura procede del modelo sembrado por el perfil.
     */
    $active_sections = bitacora_get_sections(
            array(
                    'state' => 'active',
            )
    );

    foreach ( $active_sections as $section ) {

            $pages[ $section->slug ] = array(
                    'title' => bitacora_get_section_meta(
                            $section,
                            'bitacora_section_plural',
                            $section->name
                    ),
                    'content' => sprintf(
                            '[bitacora_section slug="%s"]',
                            $section->slug
                    ),
            );
    }

    $page_ids    = array();
        $page_errors = array();
	$changed = ! empty( $role_report['changed'] );
	foreach ( $pages as $slug => $data ) {

		$existing = get_page_by_path( $slug, OBJECT, 'page' );

		if ( $existing ) {
		        $page_ids[ $slug ] = (int) $existing->ID;

		        $page_update = array(
		                'ID' => (int) $existing->ID,
		        );

		        if ( $data['title'] !== $existing->post_title ) {
		                $page_update['post_title'] = $data['title'];
		        }

		        if ( $data['content'] !== $existing->post_content ) {
		                $page_update['post_content'] = $data['content'];
		        }

		        if ( 'publish' !== $existing->post_status ) {
		                $page_update['post_status'] = 'publish';
		        }

		        if ( count( $page_update ) > 1 ) {
		                $updated_id = wp_update_post(
		                        $page_update,
		                        true
		                );

		                if ( is_wp_error( $updated_id ) ) {
		                        error_log(
		                                sprintf(
		                                        'Bitácora: no se pudo actualizar la página "%s": %s',
		                                        $slug,
		                                        $updated_id->get_error_message()
		                                )
		                        );

		                        $page_errors[] = sprintf(
                                                'No se pudo actualizar la página "%s": %s',
                                                $slug,
                                                $updated_id->get_error_message()
                                        );

                                        continue;
		                }

		                $changed = true;
		        }

		        continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $data['title'],
				'post_name'    => $slug,
				'post_content' => $data['content'],
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			error_log(
				sprintf(
					'Bitácora: no se pudo crear la página "%s": %s',
					$slug,
					$post_id->get_error_message()
				)
			);
			$page_errors[] = sprintf(
                                'No se pudo crear la página "%s": %s',
                                $slug,
                                $post_id->get_error_message()
                        );

                        continue;
		}

		$page_ids[ $slug ] = (int) $post_id;
		$changed           = true;
	}

	if ( ! empty( $page_errors ) ) {
                return new WP_Error(
                        'bitacora_install_page_errors',
                        implode( ' ', $page_errors ),
                        $page_errors
                );
        }


        /*
	 * Portada: sólo sustituir la configuración inicial de WordPress.
	 */
	if (
		isset( $page_ids['inicio'] )
		&& 'posts' === get_option( 'show_on_front' )
		&& 0 === (int) get_option( 'page_on_front' )
	) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_ids['inicio'] );
		$changed = true;
	}

	/*
	 * Nombre del sitio: sólo sustituir el valor genérico.
	 */
	$blogname = trim( (string) get_option( 'blogname' ) );

	if (
		'' === $blogname
		|| 'Just another WordPress site' === $blogname
	) {
		update_option( 'blogname', 'Bitácora' );
		$changed = true;
	}

	/*
	 * Descripción genérica, si todavía existe.
	 */
	if (
		'Just another WordPress site'
		=== trim( (string) get_option( 'blogdescription' ) )
	) {
		update_option( 'blogdescription', '' );
		$changed = true;
	}

	/*
         * La configuración sólo se considera vigente cuando toda
         * la estructura alcanzó un estado consistente.
         */
        update_option( 'obras_theme_install_schema', '5' );

        /*
         * El instante pertenece a esta instancia materializada, no al Profile.
         * Se registra inmediatamente antes de declarar el Profile EN USO.
         */
        $configured_at = time();

        update_option(
                'bitacora_configured_at',
                $configured_at
        );

        if ( $configured_at !== bitacora_get_configured_at() ) {
                return new WP_Error(
                        'bitacora_configuration_timestamp_not_saved',
                        'No se pudo registrar la fecha de puesta en uso de Bitácora.'
                );
        }

        update_option(
                'bitacora_configured_profile',
                $profile_id
        );

        if ( $profile_id !== bitacora_get_configured_profile_id() ) {

                delete_option( 'bitacora_configured_at' );

                return new WP_Error(
                        'bitacora_configuration_state_not_saved',
                        'No se pudo registrar la configuración de Bitácora.'
                );
        }

        $changed = true;

        return array(
                'profile'  => $profile_id,
                'sections' => $section_report,
                'classes'  => $class_report,
                'roles'   => $role_report,
                'pages'    => $page_ids,
                'changed'  => $changed,
                'schema'   => 5,
                'state'    => bitacora_get_configuration_state(),
        );

}

/**
 * Pone un perfil disponible en uso.
 *
 * Si existe una Bitácora materializada, primero valida completamente
 * el perfil de destino y luego desmonta la actual.
 *
 * No existe rollback de la Bitácora saliente: si la posterior
 * materialización del destino falla, la instalación queda sin
 * perfil en uso y puede reintentarse la configuración.
 */
function bitacora_change_profile( $profile_id ) {

    $profile_id = sanitize_key( (string) $profile_id );

    if ( '' === $profile_id ) {
        return new WP_Error(
            'bitacora_profile_required',
            'Debe indicarse un perfil para poner en uso.'
        );
    }

    $current_profile_id = bitacora_get_configured_profile_id();

    if ( $profile_id === $current_profile_id ) {
        return new WP_Error(
            'bitacora_profile_already_in_use',
            'El perfil indicado ya está EN USO.'
        );
    }

    /*
     * Validar completamente el destino antes de tocar la
     * Bitácora actualmente materializada.
     */
    if (
        ! bitacora_profile_library_contains( $profile_id )
        || ! bitacora_load_profile( $profile_id )
    ) {
        return new WP_Error(
            'bitacora_profile_not_found',
            sprintf(
                'No se pudo cargar el perfil aceptado "%s".',
                $profile_id
            )
        );
    }

    $validation = bitacora_validate_profile( $profile_id );

    if ( empty( $validation['valid'] ) ) {
        return new WP_Error(
            'bitacora_profile_invalid',
            sprintf(
                'El perfil aceptado "%s" no tiene una definición válida.',
                $profile_id
            ),
            $validation
        );
    }

    $dependency_problems = obras_theme_check_dependencies();

    if ( ! empty( $dependency_problems ) ) {
        return new WP_Error(
            'bitacora_dependencies_not_ready',
            'No se puede cambiar de perfil hasta resolver las dependencias de Bitácora.',
            $dependency_problems
        );
    }

    $report = array(
        'from'      => $current_profile_id,
        'to'        => $profile_id,
        'teardown'  => null,
        'configure' => null,
    );

    /*
     * Si existe una Bitácora en uso, terminarla completamente.
     */
    if ( '' !== $current_profile_id ) {

        $teardown = bitacora_remove_configured_profile();

        if ( is_wp_error( $teardown ) ) {
            return $teardown;
        }

        $report['teardown'] = $teardown;

        if (
            ! empty( $teardown['errors'] )
            || ! empty( $teardown['configured'] )
        ) {
            return new WP_Error(
                'bitacora_profile_teardown_failed',
                'No se pudo desmontar completamente la Bitácora en uso.',
                $report
            );
        }
    }

    /*
     * La instalación está ahora limpia y sin perfil en uso.
     * Reutilizar la configuración normal ya probada.
     */
    $configured = bitacora_configure_profile( $profile_id );

    if ( is_wp_error( $configured ) ) {
        return new WP_Error(
            'bitacora_profile_configuration_failed',
            $configured->get_error_message(),
            array(
                'change' => $report,
                'cause'  => $configured,
            )
        );
    }

    $report['configure'] = $configured;

    return $report;
}


/* La configuración de perfiles es explícita y no ocurre al activar el theme. */
