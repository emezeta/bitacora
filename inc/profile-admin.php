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

		$type_names = preg_split(
			'/\R/u',
			(string) $raw_types
		);

		if ( false === $type_names ) {
			$type_names = array();
		}

		$core_classes = array();
		$order        = 10;

		foreach ( $type_names as $type_name ) {

			$type_name = trim(
				sanitize_text_field( $type_name )
			);

			if ( '' === $type_name ) {
				continue;
			}

			$class_slug = sanitize_title(
				$core_slug . '-' . $type_name
			);

			if ( '' === $class_slug ) {
				$result = new WP_Error(
					'bitacora_profile_class_name_invalid',
					'Uno de los tipos no tiene un nombre válido.'
				);
				break;
			}

			if ( isset( $core_classes[ $class_slug ] ) ) {
				$result = new WP_Error(
					'bitacora_profile_class_duplicate',
					'Hay tipos de contenido repetidos.'
				);
				break;
			}

			$core_classes[ $class_slug ] = array(
				'name'     => $type_name,
				'slug'     => $class_slug,
				'scope'    => 'section',
				'scope_id' => $core_slug,
				'order'    => $order,
				'state'    => 'active',
			);

			$order += 10;
		}
	}

	if ( ! is_wp_error( $result ) ) {

		$existing_classes = isset( $definition['classes'] )
			&& is_array( $definition['classes'] )
				? $definition['classes']
				: array();

		$updated_classes = array();

		foreach ( $existing_classes as $class_id => $class ) {

			$is_core_class = (
				is_array( $class )
				&& 'section' === ( $class['scope'] ?? '' )
				&& $core_slug === sanitize_title(
					(string) ( $class['scope_id'] ?? '' )
				)
			);

			if ( $is_core_class ) {
				continue;
			}

			$updated_classes[ $class_id ] = $class;
		}

		foreach ( $core_classes as $class_id => $class ) {

			if ( isset( $updated_classes[ $class_id ] ) ) {
				$result = new WP_Error(
					'bitacora_profile_class_collision',
					'Un tipo de contenido entra en conflicto con otra clase.'
				);
				break;
			}

			$updated_classes[ $class_id ] = $class;
		}
	}

	if ( ! is_wp_error( $result ) ) {

		$definition['classes'] = $updated_classes;

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
					if (
						'bitacora_profile_class_duplicate'
						=== $error_code
					) {
						echo esc_html(
							'Hay tipos de contenido repetidos.'
						);
					} elseif (
						'bitacora_profile_class_name_invalid'
						=== $error_code
					) {
						echo esc_html(
							'Uno de los tipos no tiene un nombre válido.'
						);
					} else {
						echo esc_html(
							'No se pudieron guardar los cambios.'
						);
					}
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
