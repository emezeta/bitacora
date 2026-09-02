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
 * Devuelve el estado básico de configuración.
 */
function bitacora_get_configuration_state() {
        return bitacora_is_configured()
                ? 'configured'
                : 'unconfigured';
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
 * Administrator recibe todas las capabilities de contenido Bitácora,
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

        $managed_caps = bitacora_get_managed_content_capabilities();

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
         * pero recibe toda la familia de contenido Bitácora.
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

        if ( ! bitacora_load_profile( $profile_id ) ) {
                return new WP_Error(
                        'bitacora_profile_not_found',
                        sprintf(
                                'No se pudo cargar el perfil "%s".',
                                $profile_id
                        )
                );
        }

        $profile_validation = bitacora_validate_profile( $profile_id );

        if ( ! $profile_validation['available'] ) {
                return new WP_Error(
                        'bitacora_profile_not_available',
                        sprintf(
                                'El perfil "%s" todavía no está disponible para usar.',
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

        update_option(
                'bitacora_configured_profile',
                $profile_id
        );

        if ( $profile_id !== bitacora_get_configured_profile_id() ) {
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

/* La configuración de perfiles es explícita y no ocurre al activar el theme. */
