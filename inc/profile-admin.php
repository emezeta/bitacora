<?php
/**
 * Bitácora - Administración de perfiles.
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}


/**
 * URL canónica de la pantalla de perfiles.
 */
function bitacora_get_profiles_admin_url() {

        return admin_url(
                'edit.php?post_type=bitacora_item&page=bitacora-profiles'
        );
}


/**
 * URL de edición de un perfil.
 */
function bitacora_get_profile_edit_admin_url( $profile_id ) {

	return add_query_arg(
		'profile',
		sanitize_key( (string) $profile_id ),
		bitacora_get_profiles_admin_url()
	);
}


/**
 * Registra Perfiles como pantalla administrativa propia.
 *
 * Administrator la ve como submenu de Bitácora. Supervisor puede acceder
 * directamente aunque el modo kiosk oculte el chrome administrativo.
 */
add_action(
        'admin_menu',
        'bitacora_register_profiles_admin_page'
);

function bitacora_register_profiles_admin_page() {

        add_submenu_page(
                'edit.php?post_type=bitacora_item',
                'Perfiles disponibles',
                'Perfiles',
                'manage_bitacora_profiles',
                'bitacora-profiles',
                'bitacora_render_profiles_admin_page'
        );
}


/**
 * Traduce el estado de dominio de un perfil a un estado humano de UI.
 */
function bitacora_get_profile_admin_status( $profile ) {

        if (
                'collision' === $profile['source']
                || ! $profile['resolvable']
        ) {
                return array(
                        'key'   => 'error',
                        'label' => 'ERROR',
                );
        }

        if ( $profile['in_use'] ) {
                return array(
                        'key'   => 'in_use',
                        'label' => 'EN USO',
                );
        }

        if (
                'stored' === $profile['source']
                && ! $profile['available']
        ) {
                return array(
                        'key'   => 'preparing',
                        'label' => 'EN PREPARACIÓN',
                );
        }

        if ( $profile['available'] ) {
                return array(
                        'key'   => 'available',
                        'label' => 'DISPONIBLE',
                );
        }

        return array(
                'key'   => 'unavailable',
                'label' => 'NO DISPONIBLE',
        );
}


/**
 * Crea un nuevo perfil persistente desde la pantalla de administración.
 */
add_action(
        'admin_post_bitacora_create_profile',
        'bitacora_handle_create_profile'
);

function bitacora_handle_create_profile() {

        if ( ! current_user_can( 'manage_bitacora_profiles' ) ) {
                wp_die(
                        esc_html__(
                                'No tenés permisos para administrar perfiles.',
                                'bitacora'
                        )
                );
        }

        check_admin_referer(
                'bitacora_create_profile',
                'bitacora_create_profile_nonce'
        );

        $label = isset( $_POST['profile_label'] )
                ? sanitize_text_field(
                        wp_unslash( $_POST['profile_label'] )
                )
                : '';

        $result = bitacora_create_stored_profile( $label );

        if ( is_wp_error( $result ) ) {
                $redirect = add_query_arg(
                        array(
                                'bitacora_profile_notice' => 'create_error',
                                'bitacora_profile_error'  => $result->get_error_code(),
                        ),
                        bitacora_get_profiles_admin_url()
                );
        } else {
                $redirect = add_query_arg(
                        'bitacora_profile_notice',
                        'created',
                        bitacora_get_profiles_admin_url()
                );
        }

        wp_safe_redirect( $redirect );
        exit;
}


/**
 * Guarda los tipos de contenido del core de un perfil editable.
 */
/**
 * Construye las clases de una sección desde una lista de nombres.
 *
 * El operador define solamente los nombres. La identidad técnica,
 * alcance, orden y estado se derivan de forma determinista.
 */
function bitacora_build_profile_section_classes(
	$scope_slug,
	$raw_types
) {

	$scope_slug = sanitize_title(
		(string) $scope_slug
	);

	if ( '' === $scope_slug ) {
		return new WP_Error(
			'bitacora_profile_class_scope_invalid',
			'La sección de los tipos de contenido no es válida.'
		);
	}

	$type_names = preg_split(
		'/\R/u',
		(string) $raw_types
	);

	if ( false === $type_names ) {
		$type_names = array();
	}

	$classes = array();
	$order   = 10;

	foreach ( $type_names as $type_name ) {

		$type_name = trim(
			sanitize_text_field( $type_name )
		);

		if ( '' === $type_name ) {
			continue;
		}

		$type_slug = sanitize_title(
			$type_name
		);

		if ( '' === $type_slug ) {
			return new WP_Error(
				'bitacora_profile_class_name_invalid',
				'Uno de los tipos de contenido no tiene un nombre válido.'
			);
		}

		$class_slug = $scope_slug
			. '-'
			. $type_slug;

		if ( isset( $classes[ $class_slug ] ) ) {
			return new WP_Error(
				'bitacora_profile_class_duplicate',
				'Hay tipos de contenido repetidos.'
			);
		}

		$classes[ $class_slug ] = array(
			'name'     => $type_name,
			'slug'     => $class_slug,
			'scope'    => 'section',
			'scope_id' => $scope_slug,
			'order'    => $order,
			'state'    => 'active',
		);

		$order += 10;
	}

	return $classes;
}


/**
 * Reemplaza únicamente las clases pertenecientes a una sección.
 *
 * Las clases del resto del perfil permanecen intactas.
 */
function bitacora_replace_profile_section_classes(
	$definition,
	$scope_slug,
	$scope_classes
) {

	if (
		! is_array( $definition )
		|| ! is_array( $scope_classes )
	) {
		return new WP_Error(
			'bitacora_profile_class_definition_invalid',
			'La definición de tipos de contenido no es válida.'
		);
	}

	$scope_slug = sanitize_title(
		(string) $scope_slug
	);

	if ( '' === $scope_slug ) {
		return new WP_Error(
			'bitacora_profile_class_scope_invalid',
			'La sección de los tipos de contenido no es válida.'
		);
	}

	$existing_classes = isset( $definition['classes'] )
		&& is_array( $definition['classes'] )
			? $definition['classes']
			: array();

	$updated_classes = array();

	foreach ( $existing_classes as $class_id => $class ) {

		if (
			is_array( $class )
			&& 'section' === ( $class['scope'] ?? '' )
			&& $scope_slug === sanitize_title(
				(string) ( $class['scope_id'] ?? '' )
			)
		) {
			continue;
		}

		$updated_classes[ $class_id ] = $class;
	}

	foreach ( $scope_classes as $class_id => $class ) {

		if ( isset( $updated_classes[ $class_id ] ) ) {
			return new WP_Error(
				'bitacora_profile_class_collision',
				'Un tipo de contenido entra en conflicto con otro existente.'
			);
		}

		$updated_classes[ $class_id ] = $class;
	}

	$definition['classes'] = $updated_classes;

	return $definition;
}


add_action(
	'admin_post_bitacora_update_profile_core_types',
	'bitacora_handle_update_profile_core_types'
);

function bitacora_handle_update_profile_core_types() {

	if ( ! current_user_can( 'manage_bitacora_profiles' ) ) {
		wp_die(
			esc_html__(
				'No tenés permisos para administrar perfiles.',
				'bitacora'
			)
		);
	}

	$profile_id = isset( $_POST['profile_id'] )
		? sanitize_key(
			wp_unslash( $_POST['profile_id'] )
		)
		: '';

	check_admin_referer(
		'bitacora_update_profile_core_types_' . $profile_id,
		'bitacora_update_profile_core_types_nonce'
	);

	$catalog_entry = bitacora_get_profile_admin_catalog_entry(
		$profile_id
	);

	$definition = bitacora_get_stored_profile_definition(
		$profile_id
	);

	$profile = bitacora_load_profile(
		$profile_id
	);

	$result = null;

	if (
		! $catalog_entry
		|| empty( $catalog_entry['editable'] )
		|| ! is_array( $definition )
		|| ! is_array( $profile )
		|| empty( $profile['core']['slug'] )
	) {
		$result = new WP_Error(
			'bitacora_profile_not_editable',
			'Este perfil no puede editarse.'
		);
	}

	if ( ! is_wp_error( $result ) ) {

		$core_slug = sanitize_title(
			(string) $profile['core']['slug']
		);

		$raw_types = isset( $_POST['profile_core_types'] )
			? wp_unslash( $_POST['profile_core_types'] )
			: '';

		$core_classes = bitacora_build_profile_section_classes(
			$core_slug,
			$raw_types
		);

		if ( is_wp_error( $core_classes ) ) {
			$result = $core_classes;
		}
	}

	if ( ! is_wp_error( $result ) ) {

		$definition = bitacora_replace_profile_section_classes(
			$definition,
			$core_slug,
			$core_classes
		);

		if ( is_wp_error( $definition ) ) {
			$result = $definition;
		}
	}

	if ( ! is_wp_error( $result ) ) {
		$result = bitacora_update_stored_profile_definition(
			$profile_id,
			$definition
		);
	}

	if ( is_wp_error( $result ) ) {

		$redirect = add_query_arg(
			array(
				'bitacora_profile_notice' => 'save_error',
				'bitacora_profile_error'  => $result->get_error_code(),
			),
			bitacora_get_profile_edit_admin_url(
				$profile_id
			)
		);

	} else {

		$redirect = add_query_arg(
			'bitacora_profile_notice',
			'saved',
			bitacora_get_profile_edit_admin_url(
				$profile_id
			)
		);
	}

	wp_safe_redirect( $redirect );
	exit;
}


/**
 * Guarda los tipos de contenido de una sección complementaria.
 */
add_action(
	'admin_post_bitacora_update_profile_section_types',
	'bitacora_handle_update_profile_section_types'
);

function bitacora_handle_update_profile_section_types() {

	if ( ! current_user_can( 'manage_bitacora_profiles' ) ) {
		wp_die(
			esc_html__(
				'No tenés permisos para administrar perfiles.',
				'bitacora'
			)
		);
	}

	$profile_id = isset( $_POST['profile_id'] )
		? sanitize_key(
			wp_unslash( $_POST['profile_id'] )
		)
		: '';

	$section_id = isset( $_POST['section_id'] )
		? sanitize_key(
			wp_unslash( $_POST['section_id'] )
		)
		: '';

	check_admin_referer(
		'bitacora_update_profile_section_types_'
			. $profile_id
			. '_'
			. $section_id,
		'bitacora_update_profile_section_types_nonce'
	);

	$catalog_entry = bitacora_get_profile_admin_catalog_entry(
		$profile_id
	);

	$definition = bitacora_get_stored_profile_definition(
		$profile_id
	);

	$result = null;

	if (
		! $catalog_entry
		|| empty( $catalog_entry['editable'] )
		|| ! is_array( $definition )
		|| empty( $definition['sections'] )
		|| ! is_array( $definition['sections'] )
		|| ! isset( $definition['sections'][ $section_id ] )
		|| ! is_array( $definition['sections'][ $section_id ] )
	) {
		$result = new WP_Error(
			'bitacora_profile_section_not_editable',
			'La sección indicada no puede editarse.'
		);
	}

	if ( ! is_wp_error( $result ) ) {

		$section = $definition['sections'][ $section_id ];

		$scope_slug = sanitize_title(
			(string) ( $section['slug'] ?? '' )
		);

		if ( '' === $scope_slug ) {
			$result = new WP_Error(
				'bitacora_profile_section_invalid',
				'La sección indicada no es válida.'
			);
		}
	}

	if ( ! is_wp_error( $result ) ) {

		$raw_types = isset( $_POST['profile_section_types'] )
			? wp_unslash( $_POST['profile_section_types'] )
			: '';

		$section_classes = bitacora_build_profile_section_classes(
			$scope_slug,
			$raw_types
		);

		if ( is_wp_error( $section_classes ) ) {
			$result = $section_classes;
		}
	}

	if ( ! is_wp_error( $result ) ) {

		$definition = bitacora_replace_profile_section_classes(
			$definition,
			$scope_slug,
			$section_classes
		);

		if ( is_wp_error( $definition ) ) {
			$result = $definition;
		}
	}

	if ( ! is_wp_error( $result ) ) {
		$result = bitacora_update_stored_profile_definition(
			$profile_id,
			$definition
		);
	}

	if ( is_wp_error( $result ) ) {

		$redirect = add_query_arg(
			array(
				'bitacora_profile_notice' => 'save_error',
				'bitacora_profile_error'  => $result->get_error_code(),
			),
			bitacora_get_profile_edit_admin_url(
				$profile_id
			)
		);

	} else {

		$redirect = add_query_arg(
			'bitacora_profile_notice',
			'saved',
			bitacora_get_profile_edit_admin_url(
				$profile_id
			)
		);
	}

	wp_safe_redirect( $redirect );
	exit;
}


/**
 * Guarda las secciones complementarias de un perfil editable.
 */
add_action(
	'admin_post_bitacora_update_profile_sections',
	'bitacora_handle_update_profile_sections'
);

function bitacora_handle_update_profile_sections() {

	if ( ! current_user_can( 'manage_bitacora_profiles' ) ) {
		wp_die(
			esc_html__(
				'No tenés permisos para administrar perfiles.',
				'bitacora'
			)
		);
	}

	$profile_id = isset( $_POST['profile_id'] )
		? sanitize_key(
			wp_unslash( $_POST['profile_id'] )
		)
		: '';

	check_admin_referer(
		'bitacora_update_profile_sections_' . $profile_id,
		'bitacora_update_profile_sections_nonce'
	);

	$catalog_entry = bitacora_get_profile_admin_catalog_entry(
		$profile_id
	);

	$definition = bitacora_get_stored_profile_definition(
		$profile_id
	);

	$profile = bitacora_load_profile(
		$profile_id
	);

	$result = null;

	if (
		! $catalog_entry
		|| empty( $catalog_entry['editable'] )
		|| ! is_array( $definition )
		|| ! is_array( $profile )
		|| empty( $profile['core']['slug'] )
	) {
		$result = new WP_Error(
			'bitacora_profile_not_editable',
			'Este perfil no puede editarse.'
		);
	}

	if ( ! is_wp_error( $result ) ) {

		$core_slug = sanitize_title(
			(string) $profile['core']['slug']
		);

		$raw_sections = isset( $_POST['profile_sections'] )
			? wp_unslash( $_POST['profile_sections'] )
			: '';

		$section_names = preg_split(
			'/\R/u',
			(string) $raw_sections
		);

		if ( false === $section_names ) {
			$section_names = array();
		}

		$existing_sections = isset( $definition['sections'] )
			&& is_array( $definition['sections'] )
				? $definition['sections']
				: array();

		$existing_sections_by_slug = array();

		foreach ( $existing_sections as $existing_section ) {

			if ( ! is_array( $existing_section ) ) {
				continue;
			}

			$existing_slug = sanitize_title(
				(string) ( $existing_section['slug'] ?? '' )
			);

			if ( '' !== $existing_slug ) {
				$existing_sections_by_slug[ $existing_slug ]
					= $existing_section;
			}
		}

		$sections = array();
		$order    = 10;

		foreach ( $section_names as $section_name ) {

			$section_name = trim(
				sanitize_text_field( $section_name )
			);

			if ( '' === $section_name ) {
				continue;
			}

			$section_slug = sanitize_title(
				$section_name
			);

			if ( '' === $section_slug ) {
				$result = new WP_Error(
					'bitacora_profile_section_name_invalid',
					'Una de las secciones no tiene un nombre válido.'
				);
				break;
			}

			if ( $core_slug === $section_slug ) {
				$result = new WP_Error(
					'bitacora_profile_section_core_collision',
					'Una sección entra en conflicto con el core.'
				);
				break;
			}

			if ( isset( $sections[ $section_slug ] ) ) {
				$result = new WP_Error(
					'bitacora_profile_section_duplicate',
					'Hay secciones repetidas.'
				);
				break;
			}

			if (
				isset(
					$existing_sections_by_slug[ $section_slug ]
				)
			) {

				$section = $existing_sections_by_slug[
					$section_slug
				];

				/*
				 * Esta pantalla controla identidad visible y orden.
				 * El resto de la definición de la sección se conserva.
				 */
				$section['name']  = $section_name;
				$section['slug']  = $section_slug;
				$section['order'] = $order;

			} else {

				$section = array(
					'name'  => $section_name,
					'slug'  => $section_slug,
					'order' => $order,
					'area'  => 'main',
					'state' => 'active',
				);
			}

			$sections[ $section_slug ] = $section;

			$order += 10;
		}
	}

	if ( ! is_wp_error( $result ) ) {

		$next_section_slugs = array();

		foreach ( $sections as $section ) {

			$slug = sanitize_title(
				(string) ( $section['slug'] ?? '' )
			);

			if ( '' !== $slug ) {
				$next_section_slugs[ $slug ] = true;
			}
		}

		$existing_classes = isset( $definition['classes'] )
			&& is_array( $definition['classes'] )
				? $definition['classes']
				: array();

		foreach ( $existing_sections as $existing_section ) {

			if ( ! is_array( $existing_section ) ) {
				continue;
			}

			$existing_slug = sanitize_title(
				(string) ( $existing_section['slug'] ?? '' )
			);

			if (
				'' === $existing_slug
				|| isset( $next_section_slugs[ $existing_slug ] )
			) {
				continue;
			}

			foreach ( $existing_classes as $class ) {

				if (
					is_array( $class )
					&& 'section' === ( $class['scope'] ?? '' )
					&& $existing_slug === sanitize_title(
						(string) ( $class['scope_id'] ?? '' )
					)
				) {
					$result = new WP_Error(
						'bitacora_profile_section_has_types',
						'Primero eliminá los tipos de contenido de la sección que querés quitar o renombrar.'
					);
					break 2;
				}
			}
		}
	}

	if ( ! is_wp_error( $result ) ) {

		/*
		 * Esta pantalla controla únicamente "sections".
		 * Core y clases permanecen intactos.
		 */
		$definition['sections'] = $sections;

		$result = bitacora_update_stored_profile_definition(
			$profile_id,
			$definition
		);
	}

	if ( is_wp_error( $result ) ) {

		$redirect = add_query_arg(
			array(
				'bitacora_profile_notice' => 'save_error',
				'bitacora_profile_error'  => $result->get_error_code(),
			),
			bitacora_get_profile_edit_admin_url(
				$profile_id
			)
		);

	} else {

		$redirect = add_query_arg(
			'bitacora_profile_notice',
			'saved',
			bitacora_get_profile_edit_admin_url(
				$profile_id
			)
		);
	}

	wp_safe_redirect( $redirect );
	exit;
}


/**
 * Devuelve las features editables de una sección de perfil.
 */
function bitacora_get_profile_admin_feature_fields() {

	return array(
		'feature_file'      => 'Archivo',
		'feature_thumbnail' => 'Imagen destacada',
		'feature_location'  => 'Ubicación',
		'feature_comments'  => 'Comentarios',
	);
}


/**
 * Guarda las features de core o de una sección complementaria.
 */
add_action(
	'admin_post_bitacora_update_profile_features',
	'bitacora_handle_update_profile_features'
);

function bitacora_handle_update_profile_features() {

	if ( ! current_user_can( 'manage_bitacora_profiles' ) ) {
		wp_die(
			esc_html__(
				'No tenés permisos para administrar perfiles.',
				'bitacora'
			)
		);
	}

	$profile_id = isset( $_POST['profile_id'] )
		? sanitize_key(
			wp_unslash( $_POST['profile_id'] )
		)
		: '';

	$target = isset( $_POST['feature_target'] )
		? sanitize_key(
			wp_unslash( $_POST['feature_target'] )
		)
		: '';

	$section_id = isset( $_POST['section_id'] )
		? sanitize_key(
			wp_unslash( $_POST['section_id'] )
		)
		: '';

	$nonce_scope = 'core' === $target
		? 'core'
		: 'section_' . $section_id;

	check_admin_referer(
		'bitacora_update_profile_features_'
			. $profile_id
			. '_'
			. $nonce_scope,
		'bitacora_update_profile_features_nonce'
	);

	$catalog_entry = bitacora_get_profile_admin_catalog_entry(
		$profile_id
	);

	$definition = bitacora_get_stored_profile_definition(
		$profile_id
	);

	$result = null;

	if (
		! $catalog_entry
		|| empty( $catalog_entry['editable'] )
		|| ! is_array( $definition )
	) {
		$result = new WP_Error(
			'bitacora_profile_not_editable',
			'Este perfil no puede editarse.'
		);
	}

	$feature_values = array();

	if ( ! is_wp_error( $result ) ) {

		foreach (
			bitacora_get_profile_admin_feature_fields()
			as $feature_key => $feature_label
		) {
			$feature_values[ $feature_key ] = isset(
				$_POST[ $feature_key ]
			);
		}
	}

	if (
		! is_wp_error( $result )
		&& 'core' === $target
	) {

		$core = isset( $definition['core'] )
			&& is_array( $definition['core'] )
				? $definition['core']
				: array();

		$defaults = bitacora_get_default_core_section_definition();

		foreach ( $feature_values as $feature_key => $enabled ) {

			$default_enabled = ! empty(
				$defaults[ $feature_key ]
			);

			if ( $enabled === $default_enabled ) {
				unset( $core[ $feature_key ] );
			} else {
				$core[ $feature_key ] = $enabled;
			}
		}

		/*
		 * El core persiste sólo overrides respecto de los defaults
		 * pertenecientes al sistema.
		 */
		$definition['core'] = $core;

	} elseif (
		! is_wp_error( $result )
		&& 'section' === $target
	) {

		if (
			'' === $section_id
			|| empty( $definition['sections'] )
			|| ! is_array( $definition['sections'] )
			|| ! isset( $definition['sections'][ $section_id ] )
			|| ! is_array( $definition['sections'][ $section_id ] )
		) {
			$result = new WP_Error(
				'bitacora_profile_section_not_editable',
				'La sección indicada no puede editarse.'
			);
		}

		if ( ! is_wp_error( $result ) ) {

			foreach (
				$feature_values
				as $feature_key => $enabled
			) {
				$definition['sections'][ $section_id ][
					$feature_key
				] = $enabled;
			}
		}

	} elseif ( ! is_wp_error( $result ) ) {

		$result = new WP_Error(
			'bitacora_profile_definition_invalid',
			'La definición persistida del perfil no es válida.'
		);
	}

	if ( ! is_wp_error( $result ) ) {
		$result = bitacora_update_stored_profile_definition(
			$profile_id,
			$definition
		);
	}

	if ( is_wp_error( $result ) ) {

		$redirect = add_query_arg(
			array(
				'bitacora_profile_notice' => 'save_error',
				'bitacora_profile_error'  => $result->get_error_code(),
			),
			bitacora_get_profile_edit_admin_url(
				$profile_id
			)
		);

	} else {

		$redirect = add_query_arg(
			'bitacora_profile_notice',
			'saved',
			bitacora_get_profile_edit_admin_url(
				$profile_id
			)
		);
	}

	wp_safe_redirect( $redirect );
	exit;
}


/**
 * Devuelve un mensaje administrativo seguro para un código de error.
 *
 * Los handlers transportan solamente códigos por la URL. La traducción
 * a texto visible permanece centralizada en esta pantalla.
 */
function bitacora_get_profile_admin_error_message( $error_code ) {

	$messages = array(
		'bitacora_profile_class_scope_invalid' =>
			'La sección de los tipos de contenido no es válida.',

		'bitacora_profile_class_name_invalid' =>
			'Uno de los tipos de contenido no tiene un nombre válido.',

		'bitacora_profile_class_duplicate' =>
			'Hay tipos de contenido repetidos.',

		'bitacora_profile_class_definition_invalid' =>
			'La definición de tipos de contenido no es válida.',

		'bitacora_profile_class_collision' =>
			'Un tipo de contenido entra en conflicto con otro existente.',

		'bitacora_profile_id_invalid' =>
			'La identidad del perfil no es válida.',

		'bitacora_profile_not_stored' =>
			'El perfil no es un perfil persistente editable.',

		'bitacora_profile_not_resolvable' =>
			'No se pudo resolver correctamente el perfil persistente.',

		'bitacora_profile_in_use' =>
			'No se puede modificar un perfil que está en uso.',

		'bitacora_profile_already_used' =>
			'No se puede modificar un perfil que ya fue usado.',

		'bitacora_profile_definition_invalid' =>
			'La definición persistida del perfil no es válida.',

		'bitacora_profile_definition_not_saved' =>
			'No se pudo guardar la definición del perfil.',

		'bitacora_profile_not_editable' =>
			'Este perfil no puede editarse.',

		'bitacora_profile_section_not_editable' =>
			'La sección indicada no puede editarse.',

		'bitacora_profile_section_invalid' =>
			'La sección indicada no es válida.',

		'bitacora_profile_section_name_invalid' =>
			'Una de las secciones no tiene un nombre válido.',

		'bitacora_profile_section_core_collision' =>
			'Una sección entra en conflicto con el core.',

		'bitacora_profile_section_duplicate' =>
			'Hay secciones repetidas.',

		'bitacora_profile_section_has_types' =>
			'Primero eliminá los tipos de contenido de la sección que querés quitar o renombrar.',
	);

	return $messages[ $error_code ]
		?? 'No se pudieron guardar los cambios.';
}


/**
 * Busca una entrada del catálogo por identidad.
 */
function bitacora_get_profile_admin_catalog_entry( $profile_id ) {

	$profile_id = sanitize_key( (string) $profile_id );

	foreach ( bitacora_get_profile_catalog() as $profile ) {

		if ( $profile_id === $profile['id'] ) {
			return $profile;
		}
	}

	return false;
}


/**
 * Primera pantalla de edición progresiva.
 *
 * Esta etapa es sólo lectura.
 */
function bitacora_render_profile_edit_admin_page( $profile_id ) {

	$catalog_entry = bitacora_get_profile_admin_catalog_entry(
		$profile_id
	);

	$definition = bitacora_get_stored_profile_definition(
		$profile_id
	);

	$profile = bitacora_load_profile(
		$profile_id
	);

	if (
		! $catalog_entry
		|| empty( $catalog_entry['editable'] )
		|| ! is_array( $definition )
		|| ! is_array( $profile )
		|| empty( $profile['core']['slug'] )
	) {
		?>
		<div class="wrap bitacora-profiles-admin">
			<h1>Editar perfil</h1>

			<div class="notice notice-error inline">
				<p>Este perfil no puede editarse.</p>
			</div>

			<p>
				<a
					href="<?php echo esc_url(
						bitacora_get_profiles_admin_url()
					); ?>"
					class="button button-secondary"
				>← Volver a Perfiles</a>
			</p>
		</div>
		<?php
		return;
	}

	$status = bitacora_get_profile_admin_status(
		$catalog_entry
	);

	$notice = isset( $_GET['bitacora_profile_notice'] )
		? sanitize_key(
			wp_unslash( $_GET['bitacora_profile_notice'] )
		)
		: '';

	$error_code = isset( $_GET['bitacora_profile_error'] )
		? sanitize_key(
			wp_unslash( $_GET['bitacora_profile_error'] )
		)
		: '';

	$core_slug = sanitize_title(
		(string) $profile['core']['slug']
	);

	$core_name = ! empty( $profile['core']['name'] )
		? (string) $profile['core']['name']
		: 'Notas';

	$core_classes = array();

	foreach ( $definition['classes'] as $class ) {

		if (
			! is_array( $class )
			|| 'section' !== ( $class['scope'] ?? '' )
			|| $core_slug !== sanitize_title(
				(string) ( $class['scope_id'] ?? '' )
			)
		) {
			continue;
		}

		$core_classes[] = $class;
	}

	usort(
		$core_classes,
		static function ( $a, $b ) {

			return (int) ( $a['order'] ?? 0 )
				<=> (int) ( $b['order'] ?? 0 );
		}
	);

	$core_type_names = array();

	foreach ( $core_classes as $class ) {

		$name = trim(
			(string) ( $class['name'] ?? '' )
		);

		if ( '' !== $name ) {
			$core_type_names[] = $name;
		}
	}

	$core_types_text = implode(
		"\n",
		$core_type_names
	);

	$profile_sections = isset( $definition['sections'] )
		&& is_array( $definition['sections'] )
			? $definition['sections']
			: array();

	uasort(
		$profile_sections,
		static function ( $a, $b ) {

			return (int) ( $a['order'] ?? 0 )
				<=> (int) ( $b['order'] ?? 0 );
		}
	);

	$section_names = array();

	foreach ( $profile_sections as $section ) {

		$name = trim(
			(string) ( $section['name'] ?? '' )
		);

		if ( '' !== $name ) {
			$section_names[] = $name;
		}
	}

	$sections_text = implode(
		"\n",
		$section_names
	);

	$section_types_text = array();

	$all_classes = isset( $definition['classes'] )
		&& is_array( $definition['classes'] )
			? $definition['classes']
			: array();

	foreach ( $profile_sections as $section_id => $section ) {

		$scope_slug = sanitize_title(
			(string) ( $section['slug'] ?? '' )
		);

		$scope_classes = array();

		foreach ( $all_classes as $class_id => $class ) {

			if (
				! is_array( $class )
				|| 'section' !== ( $class['scope'] ?? '' )
				|| $scope_slug !== sanitize_title(
					(string) ( $class['scope_id'] ?? '' )
				)
			) {
				continue;
			}

			$scope_classes[ $class_id ] = $class;
		}

		uasort(
			$scope_classes,
			static function ( $a, $b ) {

				return (int) ( $a['order'] ?? 0 )
					<=> (int) ( $b['order'] ?? 0 );
			}
		);

		$type_names = array();

		foreach ( $scope_classes as $class ) {

			$name = trim(
				(string) ( $class['name'] ?? '' )
			);

			if ( '' !== $name ) {
				$type_names[] = $name;
			}
		}

		$section_types_text[ $section_id ] = implode(
			"\n",
			$type_names
		);
	}

	?>
	<div class="wrap bitacora-profiles-admin">

		<h1>Editar perfil</h1>

		<p>
			<a
				href="<?php echo esc_url(
					bitacora_get_profiles_admin_url()
				); ?>"
				class="button button-secondary"
			>← Volver a Perfiles</a>
		</p>

		<?php if ( 'saved' === $notice ) : ?>

			<div class="notice notice-success inline">
				<p>Cambios guardados.</p>
			</div>

		<?php elseif ( 'save_error' === $notice ) : ?>

			<div class="notice notice-error inline">
				<p>
						<?php
						echo esc_html(
							bitacora_get_profile_admin_error_message(
								$error_code
							)
						);
						?>
					</p>
			</div>

		<?php endif; ?>

		<h2><?php echo esc_html( $catalog_entry['label'] ); ?></h2>

		<p>
			<strong>Estado:</strong>
			<?php echo esc_html( $status['label'] ); ?>
		</p>

		<hr>

		<h2>
			Tipos de contenido de
			<?php echo esc_html( $core_name ); ?>
		</h2>

		<form
			method="post"
			action="<?php echo esc_url(
				admin_url( 'admin-post.php' )
			); ?>"
		>
			<input
				type="hidden"
				name="action"
				value="bitacora_update_profile_core_types"
			>

			<input
				type="hidden"
				name="profile_id"
				value="<?php echo esc_attr(
					$catalog_entry['id']
				); ?>"
			>

			<?php
			wp_nonce_field(
				'bitacora_update_profile_core_types_'
					. $catalog_entry['id'],
				'bitacora_update_profile_core_types_nonce'
			);
			?>

			<p>
				Escribí un tipo por línea.
				Por ejemplo: <strong>Observación</strong>.
			</p>

			<p>
				<textarea
					id="bitacora-profile-core-types"
					name="profile_core_types"
					rows="8"
					cols="50"
					class="large-text"
				><?php echo esc_textarea(
					$core_types_text
				); ?></textarea>
			</p>

			<p>
				<?php
				submit_button(
					'Guardar cambios',
					'primary',
					'submit',
					false
				);
				?>
			</p>
		</form>

		<hr>

		<h2>Secciones complementarias</h2>

		<form
			method="post"
			action="<?php echo esc_url(
				admin_url( 'admin-post.php' )
			); ?>"
		>
			<input
				type="hidden"
				name="action"
				value="bitacora_update_profile_sections"
			>

			<input
				type="hidden"
				name="profile_id"
				value="<?php echo esc_attr(
					$catalog_entry['id']
				); ?>"
			>

			<?php
			wp_nonce_field(
				'bitacora_update_profile_sections_'
					. $catalog_entry['id'],
				'bitacora_update_profile_sections_nonce'
			);
			?>

			<p>
				Escribí una sección por línea.
				Por ejemplo: <strong>Documentos</strong>.
			</p>

			<p>
				<textarea
					id="bitacora-profile-sections"
					name="profile_sections"
					rows="8"
					cols="50"
					class="large-text"
				><?php echo esc_textarea(
					$sections_text
				); ?></textarea>
			</p>

			<p>
				<?php
				submit_button(
					'Guardar secciones',
					'primary',
					'submit',
					false
				);
				?>
			</p>
		</form>

		<hr>

		<hr>

		<h2>Funciones por sección</h2>

		<p>
			Activá las funciones que correspondan a cada sección.
		</p>

		<?php
		$feature_targets = array(
			array(
				'target'     => 'core',
				'section_id' => '',
				'label'      => trim(
					(string) (
						$profile['core']['name']
						?? 'Notas'
					)
				),
				'section'    => $profile['core'],
			),
		);

		foreach ( $profile_sections as $section_id => $section ) {

			$effective_section = isset(
				$profile['sections'][ $section_id ]
			) && is_array(
				$profile['sections'][ $section_id ]
			)
				? $profile['sections'][ $section_id ]
				: $section;

			$feature_targets[] = array(
				'target'     => 'section',
				'section_id' => $section_id,
				'label'      => trim(
					(string) (
						$effective_section['name']
						?? $section_id
					)
				),
				'section'    => $effective_section,
			);
		}
		?>

		<?php foreach ( $feature_targets as $feature_target ) : ?>

			<?php
			$nonce_scope = 'core' === $feature_target['target']
				? 'core'
				: 'section_' . sanitize_key(
					$feature_target['section_id']
				);
			?>

			<h3>
				<?php echo esc_html(
					$feature_target['label']
				); ?>
			</h3>

			<form
				method="post"
				action="<?php echo esc_url(
					admin_url( 'admin-post.php' )
				); ?>"
			>
				<input
					type="hidden"
					name="action"
					value="bitacora_update_profile_features"
				>

				<input
					type="hidden"
					name="profile_id"
					value="<?php echo esc_attr(
						$catalog_entry['id']
					); ?>"
				>

				<input
					type="hidden"
					name="feature_target"
					value="<?php echo esc_attr(
						$feature_target['target']
					); ?>"
				>

				<input
					type="hidden"
					name="section_id"
					value="<?php echo esc_attr(
						$feature_target['section_id']
					); ?>"
				>

				<?php
				wp_nonce_field(
					'bitacora_update_profile_features_'
						. $catalog_entry['id']
						. '_'
						. $nonce_scope,
					'bitacora_update_profile_features_nonce'
				);
				?>

				<fieldset>

					<?php
					foreach (
						bitacora_get_profile_admin_feature_fields()
						as $feature_key => $feature_label
					) :
					?>

						<p>
							<label>
								<input
									type="checkbox"
									name="<?php echo esc_attr(
										$feature_key
									); ?>"
									value="1"
									<?php
									checked(
										! empty(
											$feature_target[
												'section'
											][
												$feature_key
											]
										)
									);
									?>
								>
								<?php echo esc_html(
									$feature_label
								); ?>
							</label>
						</p>

					<?php endforeach; ?>

				</fieldset>

				<p>
					<?php
					submit_button(
						'Guardar funciones de '
							. $feature_target['label'],
						'secondary',
						'submit',
						false
					);
					?>
				</p>
			</form>

		<?php endforeach; ?>

		<hr>

		<h2>Tipos de contenido por sección</h2>

		<?php if ( empty( $profile_sections ) ) : ?>

			<p>
				Agregá una sección complementaria para definir
				sus tipos de contenido.
			</p>

		<?php else : ?>

			<?php foreach ( $profile_sections as $section_id => $section ) : ?>

				<?php
				$section_name = trim(
					(string) ( $section['name'] ?? '' )
				);
				?>

				<h3>
					<?php echo esc_html(
						'Tipos de contenido de ' . $section_name
					); ?>
				</h3>

				<form
					method="post"
					action="<?php echo esc_url(
						admin_url( 'admin-post.php' )
					); ?>"
				>
					<input
						type="hidden"
						name="action"
						value="bitacora_update_profile_section_types"
					>

					<input
						type="hidden"
						name="profile_id"
						value="<?php echo esc_attr(
							$catalog_entry['id']
						); ?>"
					>

					<input
						type="hidden"
						name="section_id"
						value="<?php echo esc_attr(
							$section_id
						); ?>"
					>

					<?php
					wp_nonce_field(
						'bitacora_update_profile_section_types_'
							. $catalog_entry['id']
							. '_'
							. $section_id,
						'bitacora_update_profile_section_types_nonce'
					);
					?>

					<p>
						Escribí un tipo por línea.
						Por ejemplo: <strong>Referencia</strong>.
					</p>

					<p>
						<textarea
							name="profile_section_types"
							rows="6"
							cols="50"
							class="large-text"
						><?php echo esc_textarea(
							$section_types_text[ $section_id ] ?? ''
						); ?></textarea>
					</p>

					<p>
						<?php
						submit_button(
							'Guardar tipos de ' . $section_name,
							'secondary',
							'submit',
							false
						);
						?>
					</p>
				</form>

			<?php endforeach; ?>

		<?php endif; ?>



	</div>
	<?php
}


/**
 * Render de la pantalla de perfiles.
 */
function bitacora_render_profiles_admin_page() {

        if ( ! current_user_can( 'manage_bitacora_profiles' ) ) {
                wp_die(
                        esc_html__(
                                'No tenés permisos para administrar perfiles.',
                                'bitacora'
                        )
                );
        }

        $edit_profile_id = isset( $_GET['profile'] )
            ? sanitize_key(
                wp_unslash( $_GET['profile'] )
            )
            : '';

        if ( '' !== $edit_profile_id ) {

            bitacora_render_profile_edit_admin_page(
                $edit_profile_id
            );

            return;
        }

        $catalog = bitacora_get_profile_catalog();

        $notice = isset( $_GET['bitacora_profile_notice'] )
                ? sanitize_key(
                        wp_unslash( $_GET['bitacora_profile_notice'] )
                )
                : '';

        $error_code = isset( $_GET['bitacora_profile_error'] )
                ? sanitize_key(
                        wp_unslash( $_GET['bitacora_profile_error'] )
                )
                : '';

        ?>
        <div class="wrap bitacora-profiles-admin">
                <h1>Perfiles disponibles</h1>

                <?php if ( ! current_user_can( 'manage_options' ) ) : ?>
                        <p>
                                <a
                                        href="<?php echo esc_url( home_url( '/' ) ); ?>"
                                        class="button button-secondary"
                                >← Volver al Inicio</a>
                        </p>
                <?php endif; ?>

                <p>
                        Los perfiles definen la estructura inicial de una Bitácora.
                </p>

                <?php if ( 'created' === $notice ) : ?>

                        <div class="notice notice-success inline">
                                <p>Perfil creado. Ahora está EN PREPARACIÓN.</p>
                        </div>

                <?php elseif ( 'create_error' === $notice ) : ?>

                        <div class="notice notice-error inline">
                                <p>
                                        <?php
                                        if (
                                                'bitacora_profile_label_required'
                                                === $error_code
                                        ) {
                                                echo esc_html(
                                                        'El perfil necesita un nombre.'
                                                );
                                        } else {
                                                echo esc_html(
                                                        'No se pudo crear el perfil.'
                                                );
                                        }
                                        ?>
                                </p>
                        </div>

                <?php endif; ?>

                <h2>Crear perfil</h2>

                <form
                        method="post"
                        action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
                >
                        <input
                                type="hidden"
                                name="action"
                                value="bitacora_create_profile"
                        >

                        <?php
                        wp_nonce_field(
                                'bitacora_create_profile',
                                'bitacora_create_profile_nonce'
                        );
                        ?>

                        <p>
                                <label for="bitacora-profile-label">
                                        <strong>Nombre del perfil</strong>
                                </label>
                        </p>

                        <p>
                                <input
                                        type="text"
                                        id="bitacora-profile-label"
                                        name="profile_label"
                                        class="regular-text"
                                        required
                                >
                        </p>

                        <p>
                                <?php
                                submit_button(
                                        'Crear perfil',
                                        'primary',
                                        'submit',
                                        false
                                );
                                ?>
                        </p>
                </form>

                <hr>

                <?php if ( empty( $catalog ) ) : ?>

                        <div class="notice notice-warning inline">
                                <p>No hay perfiles disponibles.</p>
                        </div>

                <?php else : ?>

                        <table class="widefat striped">
                                <thead>
                                        <tr>
                                                <th scope="col">Perfil</th>
                                                <th scope="col">Estado</th>
                                        </tr>
                                </thead>

                                <tbody>
                                        <?php foreach ( $catalog as $profile ) : ?>

                                                <?php
                                                $status = bitacora_get_profile_admin_status(
                                                        $profile
                                                );
                                                ?>

                                                <tr>
                                                        <td>
                                                                <strong>
                                                                        <?php
                                                                        echo esc_html(
                                                                                $profile['label']
                                                                        );
                                                                        ?>
                                                                </strong>

								<?php if ( ! empty( $profile['editable'] ) ) : ?>
									<div>
										<a
											href="<?php echo esc_url(
												bitacora_get_profile_edit_admin_url(
													$profile['id']
												)
											); ?>"
										>Editar</a>
									</div>
								<?php endif; ?>

                                                                <?php
                                                                if (
                                                                        'error' === $status['key']
                                                                        && ! empty( $profile['errors'] )
                                                                ) :
                                                                        ?>
                                                                        <div>
                                                                                <?php
                                                                                echo esc_html(
                                                                                        implode(
                                                                                                ' ',
                                                                                                $profile['errors']
                                                                                        )
                                                                                );
                                                                                ?>
                                                                        </div>
                                                                <?php endif; ?>
                                                        </td>

                                                        <td>
                                                                <strong>
                                                                        <?php
                                                                        echo esc_html(
                                                                                $status['label']
                                                                        );
                                                                        ?>
                                                                </strong>

                                                                <?php
                                                                if (
                                                                        'preparing'
                                                                        === $status['key']
                                                                ) :
                                                                        ?>
                                                                        <div>
                                                                                Todavía necesita completar su definición.
                                                                        </div>
                                                                <?php endif; ?>
                                                        </td>
                                                </tr>

                                        <?php endforeach; ?>
                                </tbody>
                        </table>

                <?php endif; ?>
        </div>
        <?php
}
