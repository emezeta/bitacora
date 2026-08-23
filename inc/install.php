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
 * Perfil inicial de esta distribución.
 *
 * El sistema de perfiles no posee un perfil universal por defecto.
 */
function obras_theme_get_install_profile_id() {
        return 'construccion';
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
 * Se aplican exclusivamente a:
 * - bitacora
 * - bitacora_item
 *
 * Los CPT legacy quedan fuera de esta familia.
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
 * Instalación estructural.
 */
function obras_theme_install() {

        /*
         * Configuración inicial de esta distribución.
         */
        $profile_id = obras_theme_get_install_profile_id();

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
     * Páginas estructurales.
     *
     * Entradas se mantiene únicamente mientras exista el CPT legacy
     * bitacora. Las páginas del modelo nuevo se generan desde
     * bitacora_section.
     */
    $pages = array(

            'inicio' => array(
                    'title'   => 'Inicio',
                    'content' => '[obras_dashboard]',
            ),

            'entradas' => array(
                    'title'   => 'Entradas',
                    'content' => '[obras_lista_entradas]',
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

    $page_ids = array();
	$changed = ! empty( $role_report['changed'] );
	foreach ( $pages as $slug => $data ) {

		$existing = get_page_by_path( $slug, OBJECT, 'page' );

		if ( $existing ) {
			$page_ids[ $slug ] = (int) $existing->ID;
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
			continue;
		}

		$page_ids[ $slug ] = (int) $post_id;
		$changed           = true;
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
	 * Registrar estado actual de las dependencias.
	 */
	obras_theme_check_dependencies();

	update_option( 'obras_theme_install_schema', '4' );

	if ( $changed ) {
		flush_rewrite_rules( false );
	}

        return array(
                'profile'  => $profile_id,
                'sections' => $section_report,
                'classes'  => $class_report,
                'roles'   => $role_report,
                'pages'    => $page_ids,
                'changed'  => $changed,
                'schema'   => 4,
        );

}

add_action( 'after_switch_theme', 'obras_theme_install', 10, 0 );
