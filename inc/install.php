<?php
/**
 * Instalación inicial de Bitácora de Obra.
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
		'<div class="notice notice-warning"><p><strong>Bitácora de Obra:</strong> %s</p></div>',
		esc_html( implode( ' ', $messages ) )
	);
}

add_action( 'admin_notices', 'obras_theme_dependency_notice' );


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
                        'Bitácora de Obra: '
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
                        'Bitácora de Obra: '
                        . $error->get_error_message()
                );

                return $error;
        }

        $class_report = bitacora_seed_profile_classes(
                $profile_id
        );

        if ( is_wp_error( $class_report ) ) {
                error_log(
                        'Bitácora de Obra: '
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
                        'Bitácora de Obra: '
                        . $error->get_error_message()
                );

                return $error;
        }


	$pages = array(

		'inicio' => array(
			'title'   => 'Inicio',
			'content' => '[obras_dashboard]',
		),

		'entradas' => array(
			'title'   => 'Entradas',
			'content' => '[obras_lista_entradas]',
		),

		'documentos' => array(
			'title'   => 'Documentos',
			'content' => '[bitacora_section slug="documentos"]',
		),

		'materiales' => array(
			'title'   => 'Materiales',
			'content' => '[bitacora_section slug="materiales"]',
		),

		'catalogos' => array(
			'title'   => 'Catálogos',
			'content' => '[bitacora_section slug="catalogos"]',
		),

		'planos' => array(
			'title'   => 'Planos',
			'content' => '[bitacora_section slug="planos"]',
		),

		'auxiliar' => array(
			'title'   => 'Más secciones',
			'content' => '[obras_aux_dashboard]',
		),

		'paisajismo' => array(
			'title'   => 'Paisajismo',
			'content' => '[bitacora_section slug="paisajismo"]',
		),
	);

	$page_ids = array();
	$changed  = false;

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
					'Bitácora de Obra: no se pudo crear la página "%s": %s',
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
		update_option( 'blogname', 'Bitácora de Obra' );
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

	update_option( 'obras_theme_install_schema', '3' );

	if ( $changed ) {
		flush_rewrite_rules( false );
	}

        return array(
                'profile'  => $profile_id,
                'sections' => $section_report,
                'classes'  => $class_report,
                'pages'    => $page_ids,
                'changed'  => $changed,
                'schema'   => 3,
        );

}

add_action( 'after_switch_theme', 'obras_theme_install', 10, 0 );
