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

        if ( empty( $profile['valid'] ) ) {
                return array(
                        'key'   => 'error',
                        'label' => 'ERROR',
                );
        }

        if ( ! empty( $profile['in_use'] ) ) {
                return array(
                        'key'   => 'in_use',
                        'label' => 'EN USO',
                );
        }

        return array(
                'key'   => 'ready',
                'label' => 'DISPONIBLE',
        );
}


/**
 * Elimina un perfil persistente eliminable desde la administración.
 */
add_action(
    'admin_post_bitacora_delete_profile',
    'bitacora_handle_delete_profile'
);

function bitacora_handle_delete_profile() {

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
        'bitacora_delete_profile_' . $profile_id,
        'bitacora_delete_profile_nonce'
    );

    $result = bitacora_delete_stored_profile(
        $profile_id
    );

    if ( is_wp_error( $result ) ) {

        $redirect = add_query_arg(
            array(
                'bitacora_profile_notice' => 'delete_error',
                'bitacora_profile_error'  => $result->get_error_code(),
            ),
            bitacora_get_profiles_admin_url()
        );

    } else {

        $redirect = add_query_arg(
            'bitacora_profile_notice',
            'deleted',
            bitacora_get_profiles_admin_url()
        );
    }

    wp_safe_redirect( $redirect );
    exit;
}


/**
 * Devuelve el impacto de desmontar la Bitácora actualmente en uso.
 *
 * Estos conteos se usan tanto para informar al operador como para
 * detectar cambios de contenido entre la confirmación y el envío.
 */
function bitacora_get_profile_change_impact() {

    $all_post_statuses = array_values(
        get_post_stati( array(), 'names' )
    );

    $item_ids = get_posts(
        array(
            'post_type'      => 'bitacora_item',
            'post_status'    => $all_post_statuses,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        )
    );

    $attachment_ids = get_posts(
        array(
            'post_type'      => 'attachment',
            'post_status'    => $all_post_statuses,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        )
    );

    $comments = 0;

    if ( ! empty( $item_ids ) ) {
        $comments = (int) get_comments(
            array(
                'post__in' => array_map( 'intval', $item_ids ),
                'status'   => 'all',
                'count'    => true,
            )
        );
    }

    return array(
        'items'       => count( $item_ids ),
        'comments'    => $comments,
        'attachments' => count( $attachment_ids ),
    );
}


/**
 * Pone un perfil disponible en uso desde la administración.
 */
add_action(
    'admin_post_bitacora_use_profile',
    'bitacora_handle_use_profile'
);

function bitacora_handle_use_profile() {

    if ( ! current_user_can( 'use_bitacora_profiles' ) ) {
        wp_die(
            esc_html__(
                'No tenés permisos para poner perfiles en uso.',
                'bitacora'
            )
        );
    }

    check_admin_referer(
        'bitacora_use_profile',
        'bitacora_use_profile_nonce'
    );

    $profile_id = isset( $_POST['profile_id'] )
        ? sanitize_key(
            wp_unslash( $_POST['profile_id'] )
        )
        : '';

    $current_profile_id = bitacora_get_configured_profile_id();

    if ( '' !== $current_profile_id ) {

        $result = new WP_Error(
            'bitacora_already_configured',
            'Bitácora ya tiene un perfil en uso. Cerrala antes de usar otro perfil.'
        );

    } else {

        $result = bitacora_configure_profile( $profile_id );
    }

    if ( is_wp_error( $result ) ) {

        $redirect = add_query_arg(
            array(
                'bitacora_profile_notice' => 'use_error',
                'bitacora_profile_error'  => $result->get_error_code(),
            ),
            bitacora_get_profiles_admin_url()
        );

    } else {

        $redirect = add_query_arg(
            'bitacora_profile_notice',
            'used',
            bitacora_get_profiles_admin_url()
        );
    }

    wp_safe_redirect( $redirect );
    exit;
}

/**
 * Cierra la Bitácora actualmente en uso.
 *
 * La operación es explícita y termina en estado neutro.
 * No configura automáticamente otro Profile.
 */
add_action(
    'admin_post_bitacora_close_bitacora',
    'bitacora_handle_close_bitacora'
);

add_action(
    'admin_post_bitacora_documental_backup',
    'bitacora_handle_documental_backup'
);

function bitacora_handle_close_bitacora() {

    if ( ! current_user_can( 'use_bitacora_profiles' ) ) {
        wp_die(
            esc_html__(
                'No tenés permisos para cerrar Bitácora.',
                'bitacora'
            )
        );
    }

    check_admin_referer(
        'bitacora_close_bitacora',
        'bitacora_close_bitacora_nonce'
    );

    $expected_profile_id = isset( $_POST['current_profile_id'] )
        ? sanitize_key(
            wp_unslash( $_POST['current_profile_id'] )
        )
        : '';

    $current_profile_id = bitacora_get_configured_profile_id();

    $expected_impact = array(
        'items' => isset( $_POST['expected_items'] )
            ? absint( $_POST['expected_items'] )
            : 0,
        'comments' => isset( $_POST['expected_comments'] )
            ? absint( $_POST['expected_comments'] )
            : 0,
        'attachments' => isset( $_POST['expected_attachments'] )
            ? absint( $_POST['expected_attachments'] )
            : 0,
    );

    if (
        '' === $current_profile_id
        || $expected_profile_id !== $current_profile_id
    ) {

        $result = new WP_Error(
            'bitacora_profile_state_changed',
            'El perfil en uso cambió desde que se presentó esta acción.'
        );

    } else {

        $current_impact = bitacora_get_profile_change_impact();

        if ( $expected_impact !== $current_impact ) {

            $result = new WP_Error(
                'bitacora_profile_content_changed',
                'El contenido de la Bitácora cambió desde la confirmación.'
            );

        } else {

            $result = bitacora_remove_configured_profile();

            if (
                ! is_wp_error( $result )
                && (
                    ! empty( $result['errors'] )
                    || ! empty( $result['configured'] )
                )
            ) {
                $result = new WP_Error(
                    'bitacora_profile_teardown_failed',
                    'No se pudo cerrar completamente la Bitácora.',
                    $result
                );
            }
        }
    }

    if ( is_wp_error( $result ) ) {

        $redirect = add_query_arg(
            array(
                'bitacora_profile_notice' => 'close_error',
                'bitacora_profile_error'  => $result->get_error_code(),
            ),
            bitacora_get_profiles_admin_url()
        );

    } else {

        $redirect = add_query_arg(
            'bitacora_profile_notice',
            'closed',
            bitacora_get_profiles_admin_url()
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
		'bitacora_profile_id_invalid' =>
			'La identidad del perfil no es válida.',

		'bitacora_profile_not_stored' =>
			'El perfil no es un Profile persistido eliminable.',

		'bitacora_profile_not_resolvable' =>
			'No se pudo resolver correctamente el perfil persistente.',

		'bitacora_profile_in_use' =>
			'No se puede eliminar un perfil que está en uso.',


		'bitacora_profile_not_deleted' =>
			'No se pudo eliminar el perfil.',

		'bitacora_profile_delete_not_confirmed' =>
			'La eliminación del perfil no pudo confirmarse.',

		'bitacora_profile_definition_invalid' =>
			'La definición persistida del perfil no es válida.',

		'bitacora_profile_definition_not_saved' =>
			'No se pudo guardar la definición del perfil.',
	);

	return $messages[ $error_code ]
		?? 'No se pudo completar la operación solicitada.';
}


/**
 * Descarga el respaldo documental de la instancia activa.
 */
function bitacora_handle_documental_backup() {

        if ( ! current_user_can( 'use_bitacora_profiles' ) ) {
                wp_die(
                        esc_html__(
                                'No tenés permisos para generar este respaldo.',
                                'bitacora'
                        )
                );
        }

        check_admin_referer(
                'bitacora_documental_backup',
                'bitacora_documental_backup_nonce'
        );

        $expected_profile_id = isset(
                $_POST['profile_id']
        )
                ? sanitize_text_field(
                        wp_unslash(
                                $_POST['profile_id']
                        )
                )
                : '';

        $current_profile_id =
                bitacora_get_configured_profile_id();

        if (
                '' === $current_profile_id
                || $expected_profile_id !== $current_profile_id
        ) {
                wp_die(
                        esc_html__(
                                'La instancia activa cambió. Volvé a la pantalla de perfiles e intentá nuevamente.',
                                'bitacora'
                        )
                );
        }

        $result =
                bitacora_build_documental_backup();

        if ( is_wp_error( $result ) ) {
                wp_die(
                        esc_html(
                                $result->get_error_message()
                        )
                );
        }

        $path = isset( $result['path'] )
                ? (string) $result['path']
                : '';

        $filename = isset( $result['filename'] )
                ? sanitize_file_name(
                        $result['filename']
                )
                : 'bitacora-documental.zip';

        if (
                '' === $path
                || ! is_file( $path )
                || ! is_readable( $path )
        ) {
                if (
                        '' !== $path
                        && is_file( $path )
                ) {
                        @unlink( $path );
                }

                wp_die(
                        esc_html__(
                                'El respaldo fue generado pero no quedó disponible para descarga.',
                                'bitacora'
                        )
                );
        }

        ignore_user_abort( true );

        while ( ob_get_level() ) {
                ob_end_clean();
        }

        nocache_headers();

        header(
                'Content-Type: application/zip'
        );

        header(
                'Content-Disposition: attachment; filename="'
                . $filename
                . '"'
        );

        header(
                'Content-Length: '
                . (string) filesize( $path )
        );

        header(
                'X-Content-Type-Options: nosniff'
        );

        readfile( $path );

        @unlink( $path );

        exit;
}


/**
 * URL del editor de la instancia actualmente materializada.
 */
function bitacora_get_instance_editor_admin_url() {

        return admin_url(
                'edit.php?post_type=bitacora_item&page=bitacora-instance-editor'
        );
}


/**
 * Registra la edición estructural de la instancia como pantalla propia.
 *
 * La autoridad pertenece a maintain_bitacora_instance y no a la edición
 * de la biblioteca de Profiles.
 */
add_action(
        'admin_menu',
        'bitacora_register_instance_editor_admin_page'
);

function bitacora_register_instance_editor_admin_page() {

        add_submenu_page(
                'edit.php?post_type=bitacora_item',
                'Editar instancia',
                'Instancia',
                'maintain_bitacora_instance',
                'bitacora-instance-editor',
                'bitacora_render_instance_editor_admin_page'
        );
}


/**
 * Construye una PROPUESTA desde el formulario administrativo.
 *
 * Primera vertical:
 * - parte siempre desde PRE;
 * - conserva todas las identidades existentes;
 * - nunca acepta cambios de slug sobre identidades PRE;
 * - permite agregar nuevas Secciones y Tipos;
 * - nunca elimina identidades pertenecientes a PRE;
 * - permite editar presentación, orden, área, estado, features y
 *   scope_id de Tipos.
 *
 * El validator del Engine sigue siendo la autoridad final de política.
 *
 * @return array|WP_Error
 */
function bitacora_build_instance_structure_proposal_from_admin_input(
        $input,
        $pre_definition
) {

        if ( ! is_array( $input ) ) {
                return new WP_Error(
                        'bitacora_instance_editor_input_invalid',
                        'Los datos del editor de instancia no son válidos.'
                );
        }

        $pre =
                bitacora_get_canonical_profile_definition(
                        $pre_definition
                );

        if ( is_wp_error( $pre ) ) {
                return $pre;
        }

        $proposal = $pre;

        /*
         * Core.
         */
        if (
                isset( $input['core'] )
                && is_array( $input['core'] )
                && ! empty( $input['core']['_present'] )
        ) {
                $row = $input['core'];

                foreach (
                        array(
                                'name',
                                'singular',
                                'plural',
                                'subtitle',
                                'new_label',
                        )
                        as $key
                ) {
                        if ( ! array_key_exists( $key, $row ) ) {
                                continue;
                        }

                        $proposal['core'][ $key ] =
                                sanitize_text_field(
                                        (string) $row[ $key ]
                                );
                }

                foreach (
                        array(
                                'feature_file',
                                'feature_location',
                                'feature_comments',
                        )
                        as $key
                ) {
                        $proposal['core'][ $key ] =
                                ! empty( $row[ $key ] );
                }
        }

        /*
         * Secciones existentes.
         *
         * La clave del array PRE es la identidad técnica. El formulario
         * nunca puede sustituirla por un slug enviado por el cliente.
         */
        foreach (
                $pre['sections']
                as $slug => $section
        ) {
                if (
                        ! isset(
                                $input['sections'][ $slug ]
                        )
                        || ! is_array(
                                $input['sections'][ $slug ]
                        )
                        || empty(
                                $input['sections'][ $slug ]['_present']
                        )
                ) {
                        continue;
                }

                $row =
                        $input['sections'][ $slug ];

                if ( array_key_exists( 'name', $row ) ) {
                        $proposal['sections'][ $slug ]['name'] =
                                sanitize_text_field(
                                        (string) $row['name']
                                );
                }

                /*
                 * Estos atributos son opcionales.
                 * Vacío significa volver al fallback del sistema.
                 */
                foreach (
                        array(
                                'singular',
                                'plural',
                                'subtitle',
                                'new_label',
                        )
                        as $key
                ) {
                        if ( ! array_key_exists( $key, $row ) ) {
                                continue;
                        }

                        $value =
                                sanitize_text_field(
                                        (string) $row[ $key ]
                                );

                        if ( '' === $value ) {
                                unset(
                                        $proposal['sections']
                                                 [ $slug ]
                                                 [ $key ]
                                );
                        } else {
                                $proposal['sections']
                                         [ $slug ]
                                         [ $key ] =
                                        $value;
                        }
                }

                if ( array_key_exists( 'order', $row ) ) {
                        $proposal['sections'][ $slug ]['order'] =
                                (int) $row['order'];
                }

                if ( array_key_exists( 'area', $row ) ) {
                        $proposal['sections'][ $slug ]['area'] =
                                sanitize_key(
                                        (string) $row['area']
                                );
                }

                if ( array_key_exists( 'state', $row ) ) {
                        $proposal['sections'][ $slug ]['state'] =
                                sanitize_key(
                                        (string) $row['state']
                                );
                }

                foreach (
                        array(
                                'feature_file',
                                'feature_location',
                                'feature_comments',
                        )
                        as $key
                ) {
                        $proposal['sections'][ $slug ][ $key ] =
                                ! empty( $row[ $key ] );
                }
        }

        /*
         * Secciones nuevas.
         *
         * Hay dos fuentes:
         *
         * 1. filas ya incorporadas a una PROPUESTA anterior y que vuelven
         *    en instance[sections][slug];
         * 2. la fila explícita instance[new_section].
         *
         * En ambos casos el slug nace una única vez y luego pasa a ser
         * identidad técnica inmutable durante el resto de la edición.
         */
        $build_new_section =
                static function (
                        $row,
                        $slug
                ) {

                        if ( ! is_array( $row ) ) {
                                return new WP_Error(
                                        'bitacora_instance_editor_new_section_invalid',
                                        'La nueva sección no tiene una definición válida.'
                                );
                        }

                        $slug =
                                sanitize_title(
                                        (string) $slug
                                );

                        $name =
                                isset( $row['name'] )
                                        ? sanitize_text_field(
                                                (string) $row['name']
                                        )
                                        : '';

                        if (
                                '' === $slug
                                || '' === $name
                        ) {
                                return new WP_Error(
                                        'bitacora_instance_editor_new_section_invalid',
                                        'La nueva sección requiere nombre y slug.'
                                );
                        }

                        $area =
                                isset( $row['area'] )
                                        ? sanitize_key(
                                                (string) $row['area']
                                        )
                                        : 'more';

                        if (
                                ! in_array(
                                        $area,
                                        array(
                                                'main',
                                                'more',
                                        ),
                                        true
                                )
                        ) {
                                return new WP_Error(
                                        'bitacora_instance_editor_new_section_area_invalid',
                                        'El área de la nueva sección no es válida.'
                                );
                        }

                        $state =
                                isset( $row['state'] )
                                        ? sanitize_key(
                                                (string) $row['state']
                                        )
                                        : 'active';

                        if (
                                ! in_array(
                                        $state,
                                        array(
                                                'active',
                                                'hidden',
                                                'archived',
                                        ),
                                        true
                                )
                        ) {
                                return new WP_Error(
                                        'bitacora_instance_editor_new_section_state_invalid',
                                        'El estado de la nueva sección no es válido.'
                                );
                        }

                        $section = array(
                                'name' =>
                                        $name,

                                'slug' =>
                                        $slug,

                                'order' =>
                                        isset( $row['order'] )
                                                ? (int) $row['order']
                                                : 0,

                                'area' =>
                                        $area,

                                'state' =>
                                        $state,

                                'feature_file' =>
                                        ! empty(
                                                $row['feature_file']
                                        ),

                                'feature_location' =>
                                        ! empty(
                                                $row['feature_location']
                                        ),

                                'feature_comments' =>
                                        ! empty(
                                                $row['feature_comments']
                                        ),
                        );

                        foreach (
                                array(
                                        'singular',
                                        'plural',
                                        'subtitle',
                                        'new_label',
                                )
                                as $key
                        ) {
                                if (
                                        ! array_key_exists(
                                                $key,
                                                $row
                                        )
                                ) {
                                        continue;
                                }

                                $value =
                                        sanitize_text_field(
                                                (string) $row[ $key ]
                                        );

                                if ( '' !== $value ) {
                                        $section[ $key ] =
                                                $value;
                                }
                        }

                        return $section;
                };


        if (
                isset( $input['sections'] )
                && is_array(
                        $input['sections']
                )
        ) {
                foreach (
                        $input['sections']
                        as $input_slug => $row
                ) {
                        $slug =
                                sanitize_title(
                                        (string) $input_slug
                                );

                        if (
                                '' === $slug
                                || isset(
                                        $pre['sections'][ $slug ]
                                )
                                || ! is_array( $row )
                                || empty( $row['_present'] )
                        ) {
                                continue;
                        }

                        $section =
                                $build_new_section(
                                        $row,
                                        $slug
                                );

                        if ( is_wp_error( $section ) ) {
                                return $section;
                        }

                        if (
                                isset(
                                        $proposal['sections'][ $slug ]
                                )
                        ) {
                                return new WP_Error(
                                        'bitacora_instance_editor_new_section_duplicate',
                                        'La nueva sección utiliza un slug que ya pertenece a la PROPUESTA.'
                                );
                        }

                        $proposal['sections'][ $slug ] =
                                $section;
                }
        }


        if (
                isset( $input['new_section'] )
                && is_array(
                        $input['new_section']
                )
        ) {
                $row =
                        $input['new_section'];

                $name =
                        isset( $row['name'] )
                                ? trim(
                                        sanitize_text_field(
                                                (string) $row['name']
                                        )
                                )
                                : '';

                $slug =
                        isset( $row['slug'] )
                                ? sanitize_title(
                                        (string) $row['slug']
                                )
                                : '';

                /*
                 * Fila completamente vacía: no hay alta solicitada.
                 */
                if (
                        '' !== $name
                        || '' !== $slug
                ) {
                        if (
                                '' === $name
                                || '' === $slug
                        ) {
                                $fields =
                                        array();

                                if ( '' === $name ) {
                                        $fields[] =
                                                'new_section.name';
                                }

                                if ( '' === $slug ) {
                                        $fields[] =
                                                'new_section.slug';
                                }

                                return new WP_Error(
                                        'bitacora_instance_editor_new_section_invalid',
                                        'La nueva sección requiere nombre y slug.',
                                        array(
                                                'fields' =>
                                                        $fields,
                                        )
                                );
                        }

                        if (
                                isset(
                                        $proposal['sections'][ $slug ]
                                )
                        ) {
                                return new WP_Error(
                                        'bitacora_instance_editor_new_section_duplicate',
                                        'La nueva sección utiliza un slug que ya pertenece a la PROPUESTA.',
                                        array(
                                                'fields' =>
                                                        array(
                                                                'new_section.slug',
                                                        ),
                                        )
                                );
                        }

                        $section =
                                $build_new_section(
                                        $row,
                                        $slug
                                );

                        if ( is_wp_error( $section ) ) {
                                return $section;
                        }

                        $proposal['sections'][ $slug ] =
                                $section;
                }
        }


        /*
         * Tipos existentes.
         *
         * scope permanece reservado a "section".
         * scope_id sí puede proponerse; la política runtime decidirá si
         * un Tipo usado puede moverse o no.
         */
        foreach (
                $pre['classes']
                as $slug => $class
        ) {
                if (
                        ! isset(
                                $input['classes'][ $slug ]
                        )
                        || ! is_array(
                                $input['classes'][ $slug ]
                        )
                        || empty(
                                $input['classes'][ $slug ]['_present']
                        )
                ) {
                        continue;
                }

                $row =
                        $input['classes'][ $slug ];

                if ( array_key_exists( 'name', $row ) ) {
                        $proposal['classes'][ $slug ]['name'] =
                                sanitize_text_field(
                                        (string) $row['name']
                                );
                }

                if ( array_key_exists( 'scope_id', $row ) ) {
                        $proposal['classes'][ $slug ]['scope_id'] =
                                sanitize_title(
                                        (string) $row['scope_id']
                                );
                }

                if ( array_key_exists( 'order', $row ) ) {
                        $proposal['classes'][ $slug ]['order'] =
                                (int) $row['order'];
                }

                if ( array_key_exists( 'state', $row ) ) {
                        $proposal['classes'][ $slug ]['state'] =
                                sanitize_key(
                                        (string) $row['state']
                                );
                }

                $proposal['classes'][ $slug ]['scope'] =
                        'section';
        }

        /*
         * Tipos nuevos.
         *
         * Igual que con Secciones, las filas que ya pertenecen a una
         * PROPUESTA previa vuelven por instance[classes][slug].
         */
        $build_new_class =
                static function (
                        $row,
                        $slug
                ) {

                        if ( ! is_array( $row ) ) {
                                return new WP_Error(
                                        'bitacora_instance_editor_new_class_invalid',
                                        'El nuevo Tipo no tiene una definición válida.'
                                );
                        }

                        $slug =
                                sanitize_title(
                                        (string) $slug
                                );

                        $name =
                                isset( $row['name'] )
                                        ? sanitize_text_field(
                                                (string) $row['name']
                                        )
                                        : '';

                        $scope_id =
                                isset( $row['scope_id'] )
                                        ? sanitize_title(
                                                (string) $row['scope_id']
                                        )
                                        : '';

                        if (
                                '' === $slug
                                || '' === $name
                                || '' === $scope_id
                        ) {
                                return new WP_Error(
                                        'bitacora_instance_editor_new_class_invalid',
                                        'El nuevo Tipo requiere nombre, slug y sección.'
                                );
                        }

                        $state =
                                isset( $row['state'] )
                                        ? sanitize_key(
                                                (string) $row['state']
                                        )
                                        : 'active';

                        if (
                                ! in_array(
                                        $state,
                                        array(
                                                'active',
                                                'hidden',
                                                'archived',
                                        ),
                                        true
                                )
                        ) {
                                return new WP_Error(
                                        'bitacora_instance_editor_new_class_state_invalid',
                                        'El estado del nuevo Tipo no es válido.'
                                );
                        }

                        return array(
                                'name' =>
                                        $name,

                                'slug' =>
                                        $slug,

                                'scope' =>
                                        'section',

                                'scope_id' =>
                                        $scope_id,

                                'order' =>
                                        isset( $row['order'] )
                                                ? (int) $row['order']
                                                : 0,

                                'state' =>
                                        $state,
                        );
                };


        if (
                isset( $input['classes'] )
                && is_array(
                        $input['classes']
                )
        ) {
                foreach (
                        $input['classes']
                        as $input_slug => $row
                ) {
                        $slug =
                                sanitize_title(
                                        (string) $input_slug
                                );

                        if (
                                '' === $slug
                                || isset(
                                        $pre['classes'][ $slug ]
                                )
                                || ! is_array( $row )
                                || empty( $row['_present'] )
                        ) {
                                continue;
                        }

                        $class =
                                $build_new_class(
                                        $row,
                                        $slug
                                );

                        if ( is_wp_error( $class ) ) {
                                return $class;
                        }

                        if (
                                isset(
                                        $proposal['classes'][ $slug ]
                                )
                        ) {
                                return new WP_Error(
                                        'bitacora_instance_editor_new_class_duplicate',
                                        'El nuevo Tipo utiliza un slug que ya pertenece a la PROPUESTA.'
                                );
                        }

                        $proposal['classes'][ $slug ] =
                                $class;
                }
        }


        if (
                isset( $input['new_class'] )
                && is_array(
                        $input['new_class']
                )
        ) {
                $row =
                        $input['new_class'];

                $name =
                        isset( $row['name'] )
                                ? trim(
                                        sanitize_text_field(
                                                (string) $row['name']
                                        )
                                )
                                : '';

                $slug =
                        isset( $row['slug'] )
                                ? sanitize_title(
                                        (string) $row['slug']
                                )
                                : '';

                if (
                        '' !== $name
                        || '' !== $slug
                ) {
                        if (
                                '' === $name
                                || '' === $slug
                        ) {
                                $fields =
                                        array();

                                if ( '' === $name ) {
                                        $fields[] =
                                                'new_class.name';
                                }

                                if ( '' === $slug ) {
                                        $fields[] =
                                                'new_class.slug';
                                }

                                return new WP_Error(
                                        'bitacora_instance_editor_new_class_invalid',
                                        'El nuevo Tipo requiere nombre y slug.',
                                        array(
                                                'fields' =>
                                                        $fields,
                                        )
                                );
                        }

                        if (
                                isset(
                                        $proposal['classes'][ $slug ]
                                )
                        ) {
                                return new WP_Error(
                                        'bitacora_instance_editor_new_class_duplicate',
                                        'El nuevo Tipo utiliza un slug que ya pertenece a la PROPUESTA.',
                                        array(
                                                'fields' =>
                                                        array(
                                                                'new_class.slug',
                                                        ),
                                        )
                                );
                        }

                        $scope_id =
                                isset( $row['scope_id'] )
                                        ? sanitize_title(
                                                (string)
                                                $row['scope_id']
                                        )
                                        : '';

                        if ( '' === $scope_id ) {
                                return new WP_Error(
                                        'bitacora_instance_editor_new_class_scope_required',
                                        sprintf(
                                                'El nuevo Tipo "%s" no tiene una Sección asignada. Seleccioná una Sección existente o la nueva Sección que estás preparando y volvé a previsualizar. No se aplicó ningún cambio.',
                                                $name
                                        ),
                                        array(
                                                'fields' =>
                                                        array(
                                                                'new_class.scope_id',
                                                        ),
                                        )
                                );
                        }


                        $class =
                                $build_new_class(
                                        $row,
                                        $slug
                                );

                        if ( is_wp_error( $class ) ) {
                                return $class;
                        }

                        $proposal['classes'][ $slug ] =
                                $class;
                }
        }


        return bitacora_get_canonical_profile_definition(
                $proposal
        );
}


/**
 * Aplica desde administración una PROPUESTA previamente revisada.
 *
 * Dos fingerprints atan la intención del operador:
 *
 * - PRE debe seguir coincidiendo con CURRENT;
 * - la PROPUESTA reconstruida desde el formulario debe coincidir
 *   exactamente con la PROPUESTA que fue previsualizada.
 *
 * El helper no implementa la sección crítica. Esa responsabilidad sigue
 * perteneciendo exclusivamente a bitacora_reconcile_instance_structure().
 *
 * @return array|WP_Error
 */
function bitacora_apply_instance_structure_from_admin_input(
        $input,
        $expected_pre_fingerprint,
        $reviewed_proposal_fingerprint,
        $owner_user_id
) {

        if (
                ! current_user_can(
                        'maintain_bitacora_instance'
                )
        ) {
                return new WP_Error(
                        'bitacora_instance_editor_apply_forbidden',
                        'No tenés permisos para aplicar cambios estructurales.'
                );
        }

        $owner_user_id =
                absint(
                        $owner_user_id
                );

        if (
                ! $owner_user_id
                || get_current_user_id()
                        !== $owner_user_id
        ) {
                return new WP_Error(
                        'bitacora_instance_editor_apply_owner_mismatch',
                        'El usuario responsable no coincide con el usuario actual.'
                );
        }

        $snapshot =
                bitacora_get_instance_structure_snapshot();

        if ( is_wp_error( $snapshot ) ) {
                return $snapshot;
        }

        $expected_pre_fingerprint =
                trim(
                        (string) $expected_pre_fingerprint
                );

        if (
                ''
                === $expected_pre_fingerprint
                || ! hash_equals(
                        (string) $snapshot['fingerprint'],
                        $expected_pre_fingerprint
                )
        ) {
                return new WP_Error(
                        'bitacora_instance_editor_pre_changed',
                        'La estructura de la instancia cambió desde la vista previa. Previsualizá nuevamente antes de aplicar.',
                        array(
                                'expected_fingerprint' =>
                                        $expected_pre_fingerprint,

                                'current_fingerprint' =>
                                        isset(
                                                $snapshot['fingerprint']
                                        )
                                                ? (string)
                                                  $snapshot['fingerprint']
                                                : '',
                        )
                );
        }

        $proposal =
                bitacora_build_instance_structure_proposal_from_admin_input(
                        $input,
                        $snapshot['definition']
                );

        if ( is_wp_error( $proposal ) ) {
                return $proposal;
        }

        $proposal_fingerprint =
                bitacora_get_profile_definition_fingerprint(
                        $proposal
                );

        if ( is_wp_error( $proposal_fingerprint ) ) {
                return $proposal_fingerprint;
        }

        $reviewed_proposal_fingerprint =
                trim(
                        (string)
                        $reviewed_proposal_fingerprint
                );

        if (
                ''
                === $reviewed_proposal_fingerprint
                || ! hash_equals(
                        (string) $proposal_fingerprint,
                        $reviewed_proposal_fingerprint
                )
        ) {
                return new WP_Error(
                        'bitacora_instance_editor_proposal_changed',
                        'La PROPUESTA cambió después de la vista previa. Previsualizá nuevamente antes de aplicar.',
                        array(
                                'reviewed_proposal_fingerprint' =>
                                        $reviewed_proposal_fingerprint,

                                'current_proposal_fingerprint' =>
                                        (string)
                                        $proposal_fingerprint,
                        )
                );
        }

        return bitacora_reconcile_instance_structure(
                $snapshot,
                $proposal,
                $owner_user_id
        );
}


/**
 * Render del editor estructural de la instancia.
 *
 * La pantalla construye PROPUESTA, exige vista previa exacta y sólo entonces
 * permite APPLY. La sección crítica y la PAUSA siguen encapsuladas dentro
 * del orquestador estructural.
 */
function bitacora_render_instance_editor_admin_page() {

        if (
                ! current_user_can(
                        'maintain_bitacora_instance'
                )
        ) {
                wp_die(
                        esc_html__(
                                'No tenés permisos para mantener la instancia.',
                                'bitacora'
                        )
                );
        }

        if ( ! bitacora_is_configured() ) {
                ?>
                <div class="wrap bitacora-instance-editor">
                        <h1>Editar instancia</h1>

                        <div class="notice notice-warning inline">
                                <p>
                                        Bitácora no tiene una instancia
                                        configurada.
                                </p>
                        </div>

                        <p>
                                <a
                                        class="button button-secondary"
                                        href="<?php echo esc_url(
                                                bitacora_get_profiles_admin_url()
                                        ); ?>"
                                >Volver a Perfiles</a>
                        </p>
                </div>
                <?php
                return;
        }

        $runtime_state =
                bitacora_get_instance_runtime_state();

        if ( 'active' !== $runtime_state ) {
                ?>
                <div class="wrap bitacora-instance-editor">
                        <h1>Editar instancia</h1>

                        <div class="notice notice-warning inline">
                                <p>
                                        La instancia se encuentra en estado
                                        <strong><?php
                                        echo esc_html(
                                                strtoupper(
                                                        $runtime_state
                                                )
                                        );
                                        ?></strong>.
                                        No puede prepararse una nueva edición
                                        estructural hasta volver a ACTIVE.
                                </p>
                        </div>

                        <p>
                                <a
                                        class="button button-secondary"
                                        href="<?php echo esc_url(
                                                bitacora_get_profiles_admin_url()
                                        ); ?>"
                                >Volver a Perfiles</a>
                        </p>
                </div>
                <?php
                return;
        }

        $snapshot =
                bitacora_get_instance_structure_snapshot();

        if ( is_wp_error( $snapshot ) ) {
                wp_die(
                        esc_html(
                                $snapshot->get_error_message()
                        )
                );
        }

        $form_definition =
                $snapshot['definition'];

        $preview = null;
        $preview_errors = array();
        $apply_result = null;

        /*
         * Conserva el POST útil cuando PREVIEW encuentra un error recuperable.
         * El runtime nunca se reconstruye desde estos valores: sólo sirven
         * para devolver al operador el formulario que estaba editando.
         */
        $form_input = array();

        /*
         * Campos responsables de errores conocidos del editor.
         * La validación autoritativa sigue viviendo en el callback/Engine;
         * esto sólo permite señalar visualmente dónde corregir.
         */
        $field_errors = array();


        $get_form_input =
                static function (
                        $group,
                        $key,
                        $default = ''
                ) use (
                        &$form_input
                ) {

                        if (
                                isset( $form_input[ $group ] )
                                && is_array(
                                        $form_input[ $group ]
                                )
                                && array_key_exists(
                                        $key,
                                        $form_input[ $group ]
                                )
                        ) {
                                return $form_input[
                                        $group
                                ][
                                        $key
                                ];
                        }

                        return $default;
                };


        $field_has_error =
                static function (
                        $field
                ) use (
                        &$field_errors
                ) {

                        return isset(
                                $field_errors[ $field ]
                        );
                };


        $capture_editor_error =
                static function (
                        $error
                ) use (
                        &$preview_errors,
                        &$field_errors
                ) {

                        if ( ! is_wp_error( $error ) ) {
                                return;
                        }

                        $preview_errors[] =
                                $error->get_error_message();

                        $data =
                                $error->get_error_data();

                        if (
                                ! is_array( $data )
                                || empty( $data['fields'] )
                                || ! is_array(
                                        $data['fields']
                                )
                        ) {
                                return;
                        }

                        foreach (
                                $data['fields']
                                as $field
                        ) {
                                $field =
                                        (string) $field;

                                if ( '' === $field ) {
                                        continue;
                                }

                                $field_errors[ $field ] =
                                        true;
                        }
                };

        $reviewed_proposal_fingerprint =
                isset(
                        $_POST[
                                'reviewed_proposal_fingerprint'
                        ]
                )
                        ? sanitize_text_field(
                                wp_unslash(
                                        $_POST[
                                                'reviewed_proposal_fingerprint'
                                        ]
                                )
                        )
                        : '';

        $request_method =
                isset( $_SERVER['REQUEST_METHOD'] )
                        ? strtoupper(
                                (string) $_SERVER['REQUEST_METHOD']
                        )
                        : 'GET';

        $editor_action =
                isset( $_POST['bitacora_instance_editor_action'] )
                        ? sanitize_key(
                                wp_unslash(
                                        $_POST[
                                                'bitacora_instance_editor_action'
                                        ]
                                )
                        )
                        : '';

        if (
                'POST' === $request_method
                && in_array(
                        $editor_action,
                        array(
                                'preview',
                                'apply',
                        ),
                        true
                )
        ) {
                check_admin_referer(
                        'bitacora_edit_instance_structure',
                        'bitacora_instance_editor_nonce'
                );

                $expected_fingerprint =
                        isset( $_POST['pre_fingerprint'] )
                                ? sanitize_text_field(
                                        wp_unslash(
                                                $_POST['pre_fingerprint']
                                        )
                                )
                                : '';

                $input =
                        isset( $_POST['instance'] )
                        && is_array(
                                $_POST['instance']
                        )
                                ? wp_unslash(
                                        $_POST['instance']
                                )
                                : array();


                $form_input =
                        $input;

                /*
                 * Conserva también cambios válidos sobre estructura existente.
                 * Las filas explícitas de alta se excluyen porque pueden estar
                 * incompletas; sus datos permanecen en $form_input.
                 */
                $display_input =
                        $input;

                unset(
                        $display_input['new_section'],
                        $display_input['new_class']
                );

                $display_definition =
                        bitacora_build_instance_structure_proposal_from_admin_input(
                                $display_input,
                                $snapshot['definition']
                        );

                if (
                        ! is_wp_error(
                                $display_definition
                        )
                ) {
                        $form_definition =
                                $display_definition;
                }

                /*
                 * PREVIEW
                 */
                if ( 'preview' === $editor_action ) {

                        if (
                                ''
                                === $expected_fingerprint
                                || ! hash_equals(
                                        (string)
                                        $snapshot['fingerprint'],
                                        (string)
                                        $expected_fingerprint
                                )
                        ) {
                                $preview_errors[] =
                                        'La estructura de la instancia cambió desde que abriste el editor. Volvé a cargarla antes de continuar.';
                        } else {
                                $proposal =
                                        bitacora_build_instance_structure_proposal_from_admin_input(
                                                $input,
                                                $snapshot['definition']
                                        );

                                if ( is_wp_error( $proposal ) ) {
                                        $capture_editor_error(
                                                $proposal
                                        );
                                } else {
                                        $form_definition =
                                                $proposal;

                                        $validation =
                                                bitacora_validate_instance_structure_proposal(
                                                        $snapshot,
                                                        $proposal
                                                );

                                        if (
                                                empty(
                                                        $validation['valid']
                                                )
                                        ) {

                                                foreach (
                                                        isset(
                                                                $validation[
                                                                        'errors'
                                                                ]
                                                        )
                                                                ? $validation[
                                                                        'errors'
                                                                ]
                                                                : array()
                                                        as $error
                                                ) {
                                                        if (
                                                                is_array(
                                                                        $error
                                                                )
                                                                && isset(
                                                                        $error[
                                                                                'message'
                                                                        ]
                                                                )
                                                        ) {
                                                                $preview_errors[] =
                                                                        (string)
                                                                        $error[
                                                                                'message'
                                                                        ];
                                                        } elseif (
                                                                is_string(
                                                                        $error
                                                                )
                                                        ) {
                                                                $preview_errors[] =
                                                                        $error;
                                                        }
                                                }

                                                if (
                                                        empty(
                                                                $preview_errors
                                                        )
                                                ) {
                                                        $preview_errors[] =
                                                                'La propuesta estructural no es válida.';
                                                }

                                        } else {
                                                $plan =
                                                        bitacora_build_instance_structure_reconciliation_plan(
                                                                $snapshot,
                                                                $proposal
                                                        );

                                                if (
                                                        is_wp_error(
                                                                $plan
                                                        )
                                                ) {
                                                        $preview_errors[] =
                                                                $plan->get_error_message();
                                                } else {
                                                        $preview =
                                                                $plan;

                                                        $reviewed_proposal_fingerprint =
                                                                (string)
                                                                $plan[
                                                                        'proposal_fingerprint'
                                                                ];

                                                        /*
                                                         * Una alta que alcanzó
                                                         * una PREVIEW válida ya
                                                         * forma parte de la
                                                         * PROPUESTA.
                                                         *
                                                         * Desde este punto se
                                                         * representa únicamente
                                                         * mediante sections[] /
                                                         * classes[] para evitar
                                                         * reenviarla también
                                                         * como new_section /
                                                         * new_class.
                                                         *
                                                         * En una PREVIEW
                                                         * inválida estas filas
                                                         * no se limpian y siguen
                                                         * siendo sticky.
                                                         */
                                                        unset(
                                                                $form_input[
                                                                        'new_section'
                                                                ],
                                                                $form_input[
                                                                        'new_class'
                                                                ]
                                                        );

                                                        $field_errors =
                                                                array();
                                                }
                                        }
                                }
                        }
                }


                /*
                 * APPLY
                 *
                 * El checkbox es intención humana de UI. Los fingerprints,
                 * capability y orquestador constituyen la frontera real.
                 */
                if ( 'apply' === $editor_action ) {

                        $proposal =
                                bitacora_build_instance_structure_proposal_from_admin_input(
                                        $input,
                                        $snapshot['definition']
                                );

                        if ( is_wp_error( $proposal ) ) {
                                $capture_editor_error(
                                        $proposal
                                );
                        } else {
                                $form_definition =
                                        $proposal;

                                if (
                                        empty(
                                                $_POST[
                                                        'confirm_apply'
                                                ]
                                        )
                                ) {
                                        $preview_errors[] =
                                                'Confirmá explícitamente la aplicación de los cambios.';
                                } else {
                                        $apply_result =
                                                bitacora_apply_instance_structure_from_admin_input(
                                                        $input,
                                                        $expected_fingerprint,
                                                        $reviewed_proposal_fingerprint,
                                                        get_current_user_id()
                                                );

                                        if (
                                                is_wp_error(
                                                        $apply_result
                                                )
                                        ) {
                                                $capture_editor_error(
                                                        $apply_result
                                                );

                                                $apply_result =
                                                        null;
                                        } else {
                                                $snapshot =
                                                        bitacora_get_instance_structure_snapshot();

                                                if (
                                                        is_wp_error(
                                                                $snapshot
                                                        )
                                                ) {
                                                        wp_die(
                                                                esc_html(
                                                                        $snapshot->get_error_message()
                                                                )
                                                        );
                                                }

                                                $form_definition =
                                                        $snapshot[
                                                                'definition'
                                                        ];

                                                $reviewed_proposal_fingerprint =
                                                        '';

                                                $preview =
                                                        null;

                                                $form_input =
                                                        array();

                                                $field_errors =
                                                        array();
                                        }
                                }
                        }
                }
        }


        /*
         * Un fallo recuperable vuelve ACTIVE.
         *
         * Si el orquestador dejó PAUSED porque no pudo demostrar PRE/POST,
         * no seguimos ofreciendo controles estructurales en esta respuesta.
         */
        $runtime_after_request =
                bitacora_get_instance_runtime_state();

        if ( 'active' !== $runtime_after_request ) {
                ?>
                <div class="wrap bitacora-instance-editor">
                        <h1>Editar instancia</h1>

                        <div class="notice notice-error inline">
                                <p>
                                        <strong>
                                                La instancia permanece
                                                <?php
                                                echo esc_html(
                                                        strtoupper(
                                                                $runtime_after_request
                                                        )
                                                );
                                                ?>.
                                        </strong>
                                </p>

                                <?php
                                foreach (
                                        $preview_errors
                                        as $message
                                ) :
                                        ?>
                                        <p>
                                                <?php
                                                echo esc_html(
                                                        $message
                                                );
                                                ?>
                                        </p>
                                <?php endforeach; ?>

                                <p>
                                        No se ofrecen nuevas operaciones
                                        estructurales hasta verificar y
                                        recuperar el estado de mantenimiento.
                                </p>
                        </div>

                        <p>
                                <a
                                        href="<?php echo esc_url(
                                                bitacora_get_profiles_admin_url()
                                        ); ?>"
                                        class="button button-secondary"
                                >Volver a Perfiles</a>
                        </p>
                </div>
                <?php
                return;
        }


        $section_states = array(
                'active'   => 'Activa',
                'hidden'   => 'Oculta',
                'archived' => 'Archivada',
        );

        $class_states = array(
                'active'   => 'Activo',
                'hidden'   => 'Oculto',
                'archived' => 'Archivado',
        );

        $areas = array(
                'main' => 'Principal',
                'more' => 'Más',
        );

        $sections =
                $form_definition['sections'];

        uasort(
                $sections,
                static function ( $a, $b ) {

                        $a_order =
                                isset( $a['order'] )
                                        ? (int) $a['order']
                                        : 0;

                        $b_order =
                                isset( $b['order'] )
                                        ? (int) $b['order']
                                        : 0;

                        if ( $a_order !== $b_order ) {
                                return $a_order <=> $b_order;
                        }

                        return strcasecmp(
                                isset( $a['name'] )
                                        ? (string) $a['name']
                                        : '',
                                isset( $b['name'] )
                                        ? (string) $b['name']
                                        : ''
                        );
                }
        );

        $classes =
                $form_definition['classes'];

        uasort(
                $classes,
                static function ( $a, $b ) {

                        $a_scope =
                                isset( $a['scope_id'] )
                                        ? (string) $a['scope_id']
                                        : '';

                        $b_scope =
                                isset( $b['scope_id'] )
                                        ? (string) $b['scope_id']
                                        : '';

                        if ( $a_scope !== $b_scope ) {
                                return strcasecmp(
                                        $a_scope,
                                        $b_scope
                                );
                        }

                        $a_order =
                                isset( $a['order'] )
                                        ? (int) $a['order']
                                        : 0;

                        $b_order =
                                isset( $b['order'] )
                                        ? (int) $b['order']
                                        : 0;

                        if ( $a_order !== $b_order ) {
                                return $a_order <=> $b_order;
                        }

                        return strcasecmp(
                                isset( $a['name'] )
                                        ? (string) $a['name']
                                        : '',
                                isset( $b['name'] )
                                        ? (string) $b['name']
                                        : ''
                        );
                }
        );

        $scope_options = array(
                'notas' =>
                        isset(
                                $form_definition['core']['name']
                        )
                                ? (string)
                                  $form_definition['core']['name']
                                : 'Bitácora',
        );

        foreach (
                $sections
                as $slug => $section
        ) {
                $scope_options[ $slug ] =
                        isset( $section['name'] )
                                ? (string) $section['name']
                                : $slug;
        }

        $current_profile_id =
                bitacora_get_configured_profile_id();

        ?>
        <div class="wrap bitacora-instance-editor">
                <h1>Editar instancia</h1>

                <p>
                        <a
                                href="<?php echo esc_url(
                                        bitacora_get_profiles_admin_url()
                                ); ?>"
                        >← Volver a Perfiles</a>
                </p>

                <div class="notice notice-info inline">
                        <p>
                                Estás editando la
                                <strong>instancia materializada</strong>.
                                Esta operación no modifica el Profile
                                <code><?php
                                echo esc_html(
                                        $current_profile_id
                                );
                                ?></code>
                                de la biblioteca.
                        </p>

                        <p>
                                Los slugs son identidad técnica y no se
                                editan. Para retirar una estructura existente
                                se cambia su estado; no se elimina.
                        </p>
                </div>

                <p>
                        <strong>PRE:</strong>
                        <code><?php
                        echo esc_html(
                                $snapshot['fingerprint']
                        );
                        ?></code>
                </p>

                <?php if ( is_array( $apply_result ) ) : ?>

                        <div class="notice notice-success inline">
                                <p>
                                        <strong>
                                                Cambios estructurales aplicados.
                                        </strong>
                                        La instancia volvió a ACTIVE y la
                                        estructura resultante fue verificada
                                        por el Engine.
                                </p>
                        </div>

                <?php endif; ?>

                <?php if ( ! empty( $preview_errors ) ) : ?>

                        <div class="notice notice-error inline">
                                <p>
                                        <strong>
                                                La PROPUESTA no puede
                                                previsualizarse:
                                        </strong>
                                </p>

                                <ul>
                                        <?php
                                        foreach (
                                                $preview_errors
                                                as $message
                                        ) :
                                                ?>
                                                <li>
                                                        <?php
                                                        echo esc_html(
                                                                $message
                                                        );
                                                        ?>
                                                </li>
                                        <?php endforeach; ?>
                                </ul>
                        </div>

                <?php elseif ( is_array( $preview ) ) : ?>

                        <div class="notice notice-success inline">
                                <h2>Vista previa de reconciliación</h2>

                                <?php
                                $comparison =
                                        $preview['comparison'];
                                ?>

                                <?php
                                if (
                                        0
                                        === (int)
                                        $preview['action_count']
                                ) :
                                        ?>
                                        <p>
                                                La PROPUESTA no produce
                                                cambios estructurales.
                                        </p>
                                <?php else : ?>
                                        <p>
                                                El Engine preparó
                                                <strong><?php
                                                echo esc_html(
                                                        number_format_i18n(
                                                                $preview[
                                                                        'action_count'
                                                                ]
                                                        )
                                                );
                                                ?></strong>
                                                acciones físicas.
                                                <strong>
                                                        No se aplicó ninguna.
                                                </strong>
                                        </p>

                                        <p>
                                                Secciones modificadas:
                                                <strong><?php
                                                echo esc_html(
                                                        number_format_i18n(
                                                                count(
                                                                        $comparison[
                                                                                'sections'
                                                                        ][
                                                                                'modified'
                                                                        ]
                                                                )
                                                        )
                                                );
                                                ?></strong>
                                                · Tipos modificados:
                                                <strong><?php
                                                echo esc_html(
                                                        number_format_i18n(
                                                                count(
                                                                        $comparison[
                                                                                'classes'
                                                                        ][
                                                                                'modified'
                                                                        ]
                                                                )
                                                        )
                                                );
                                                ?></strong>
                                        </p>

                                        <table class="widefat striped">
                                                <thead>
                                                        <tr>
                                                                <th>#</th>
                                                                <th>Acción</th>
                                                                <th>Identidad</th>
                                                        </tr>
                                                </thead>
                                                <tbody>
                                                        <?php
                                                        foreach (
                                                                $preview[
                                                                        'actions'
                                                                ]
                                                                as $action
                                                        ) :
                                                                ?>
                                                                <tr>
                                                                        <td>
                                                                                <?php
                                                                                echo esc_html(
                                                                                        (string)
                                                                                        $action[
                                                                                                'sequence'
                                                                                        ]
                                                                                );
                                                                                ?>
                                                                        </td>
                                                                        <td>
                                                                                <code><?php
                                                                                echo esc_html(
                                                                                        (string)
                                                                                        $action[
                                                                                                'type'
                                                                                        ]
                                                                                );
                                                                                ?></code>
                                                                        </td>
                                                                        <td>
                                                                                <code><?php
                                                                                echo esc_html(
                                                                                        (string)
                                                                                        $action[
                                                                                                'identity'
                                                                                        ]
                                                                                );
                                                                                ?></code>
                                                                        </td>
                                                                </tr>
                                                        <?php endforeach; ?>
                                                </tbody>
                                        </table>
                                <?php endif; ?>
                        </div>

                <?php endif; ?>

                <style>
                        .bitacora-instance-editor .bitacora-structure-table {
                                margin-top: 12px;
                        }

                        .bitacora-instance-editor .bitacora-structure-table input[type="text"] {
                                width: 100%;
                                min-width: 120px;
                        }

                        .bitacora-instance-editor .bitacora-structure-table input[type="number"] {
                                width: 80px;
                        }

                        .bitacora-instance-editor .bitacora-structure-table select {
                                min-width: 120px;
                        }

                        .bitacora-instance-editor .bitacora-feature-cell {
                                text-align: center;
                        }

                        .bitacora-instance-editor details {
                                margin: 18px 0;
                                padding: 10px 14px;
                                border: 1px solid #c3c4c7;
                                background: #fff;
                        }

                        .bitacora-instance-editor summary {
                                cursor: pointer;
                                font-weight: 600;
                        }

                        .bitacora-instance-editor .bitacora-editor-warning {
                                color: #996800;
                                font-weight: 600;
                        }

                        .bitacora-instance-editor .bitacora-field-error {
                                border-color: #d63638 !important;
                                box-shadow: 0 0 0 1px #d63638;
                        }
                </style>

                <form
                        method="post"
                        action="<?php echo esc_url(
                                bitacora_get_instance_editor_admin_url()
                        ); ?>"
                >
                        <input
                                type="hidden"
                                name="pre_fingerprint"
                                value="<?php echo esc_attr(
                                        $snapshot['fingerprint']
                                ); ?>"
                        >

                        <?php if ( '' !== $reviewed_proposal_fingerprint ) : ?>
                                <input
                                        type="hidden"
                                        name="reviewed_proposal_fingerprint"
                                        value="<?php echo esc_attr(
                                                $reviewed_proposal_fingerprint
                                        ); ?>"
                                >
                        <?php endif; ?>

                        <?php
                        wp_nonce_field(
                                'bitacora_edit_instance_structure',
                                'bitacora_instance_editor_nonce'
                        );
                        ?>

                        <h2>Bitácora / Notas</h2>

                        <input
                                type="hidden"
                                name="instance[core][_present]"
                                value="1"
                        >

                        <table class="form-table">
                                <tbody>
                                        <?php
                                        foreach (
                                                array(
                                                        'name' =>
                                                                'Nombre',
                                                        'singular' =>
                                                                'Singular',
                                                        'plural' =>
                                                                'Plural',
                                                        'subtitle' =>
                                                                'Subtítulo',
                                                        'new_label' =>
                                                                'Etiqueta nueva entrada',
                                                )
                                                as $key => $label
                                        ) :
                                                ?>
                                                <tr>
                                                        <th scope="row">
                                                                <?php
                                                                echo esc_html(
                                                                        $label
                                                                );
                                                                ?>
                                                        </th>
                                                        <td>
                                                                <input
                                                                        type="text"
                                                                        class="regular-text"
                                                                        name="instance[core][<?php
                                                                        echo esc_attr(
                                                                                $key
                                                                        );
                                                                        ?>]"
                                                                        value="<?php
                                                                        echo esc_attr(
                                                                                isset(
                                                                                        $form_definition[
                                                                                                'core'
                                                                                        ][
                                                                                                $key
                                                                                        ]
                                                                                )
                                                                                        ? $form_definition[
                                                                                                'core'
                                                                                        ][
                                                                                                $key
                                                                                        ]
                                                                                        : ''
                                                                        );
                                                                        ?>"
                                                                >
                                                        </td>
                                                </tr>
                                        <?php endforeach; ?>

                                        <tr>
                                                <th scope="row">
                                                        Features
                                                </th>
                                                <td>
                                                        <?php
                                                        foreach (
                                                                array(
                                                                        'feature_file' =>
                                                                                'Archivo',
                                                                        'feature_location' =>
                                                                                'Ubicación',
                                                                        'feature_comments' =>
                                                                                'Comentarios',
                                                                )
                                                                as $key => $label
                                                        ) :
                                                                ?>
                                                                <label
                                                                        style="margin-right:20px"
                                                                >
                                                                        <input
                                                                                type="checkbox"
                                                                                name="instance[core][<?php
                                                                                echo esc_attr(
                                                                                        $key
                                                                                );
                                                                                ?>]"
                                                                                value="1"
                                                                                <?php
                                                                                checked(
                                                                                        ! empty(
                                                                                                $form_definition[
                                                                                                        'core'
                                                                                                ][
                                                                                                        $key
                                                                                                ]
                                                                                        )
                                                                                );
                                                                                ?>
                                                                        >
                                                                        <?php
                                                                        echo esc_html(
                                                                                $label
                                                                        );
                                                                        ?>
                                                                </label>
                                                        <?php endforeach; ?>
                                                </td>
                                        </tr>
                                </tbody>
                        </table>

                        <h2>Secciones</h2>

                        <p>
                                El slug se conserva como identidad.
                                Estado <strong>Oculta</strong> retira la
                                sección del uso normal conservando su historia.
                        </p>

                        <?php
                        foreach (
                                $sections
                                as $slug => $section
                        ) :
                                ?>

                                <details>
                                        <summary>
                                                <?php
                                                echo esc_html(
                                                        isset(
                                                                $section[
                                                                        'name'
                                                                ]
                                                        )
                                                                ? $section[
                                                                        'name'
                                                                ]
                                                                : $slug
                                                );
                                                ?>
                                                —
                                                <code><?php
                                                echo esc_html(
                                                        $slug
                                                );
                                                ?></code>
                                        </summary>

                                        <input
                                                type="hidden"
                                                name="instance[sections][<?php
                                                echo esc_attr( $slug );
                                                ?>][_present]"
                                                value="1"
                                        >

                                        <table class="form-table">
                                                <tbody>
                                                        <tr>
                                                                <th scope="row">
                                                                        Identidad
                                                                </th>
                                                                <td>
                                                                        <code><?php
                                                                        echo esc_html(
                                                                                $slug
                                                                        );
                                                                        ?></code>
                                                                </td>
                                                        </tr>

                                                        <?php
                                                        foreach (
                                                                array(
                                                                        'name' =>
                                                                                'Nombre',
                                                                        'singular' =>
                                                                                'Singular',
                                                                        'plural' =>
                                                                                'Plural',
                                                                        'subtitle' =>
                                                                                'Subtítulo',
                                                                        'new_label' =>
                                                                                'Etiqueta nueva entrada',
                                                                )
                                                                as $key => $label
                                                        ) :
                                                                ?>
                                                                <tr>
                                                                        <th scope="row">
                                                                                <?php
                                                                                echo esc_html(
                                                                                        $label
                                                                                );
                                                                                ?>
                                                                        </th>
                                                                        <td>
                                                                                <input
                                                                                        type="text"
                                                                                        class="regular-text"
                                                                                        name="instance[sections][<?php
                                                                                        echo esc_attr(
                                                                                                $slug
                                                                                        );
                                                                                        ?>][<?php
                                                                                        echo esc_attr(
                                                                                                $key
                                                                                        );
                                                                                        ?>]"
                                                                                        value="<?php
                                                                                        echo esc_attr(
                                                                                                isset(
                                                                                                        $section[
                                                                                                                $key
                                                                                                        ]
                                                                                                )
                                                                                                        ? $section[
                                                                                                                $key
                                                                                                        ]
                                                                                                        : ''
                                                                                        );
                                                                                        ?>"
                                                                                >
                                                                        </td>
                                                                </tr>
                                                        <?php endforeach; ?>

                                                        <tr>
                                                                <th scope="row">
                                                                        Orden
                                                                </th>
                                                                <td>
                                                                        <input
                                                                                type="number"
                                                                                name="instance[sections][<?php
                                                                                echo esc_attr(
                                                                                        $slug
                                                                                );
                                                                                ?>][order]"
                                                                                value="<?php
                                                                                echo esc_attr(
                                                                                        isset(
                                                                                                $section[
                                                                                                        'order'
                                                                                                ]
                                                                                        )
                                                                                                ? (int)
                                                                                                  $section[
                                                                                                        'order'
                                                                                                  ]
                                                                                                : 0
                                                                                );
                                                                                ?>"
                                                                        >
                                                                </td>
                                                        </tr>

                                                        <tr>
                                                                <th scope="row">
                                                                        Área
                                                                </th>
                                                                <td>
                                                                        <select
                                                                                name="instance[sections][<?php
                                                                                echo esc_attr(
                                                                                        $slug
                                                                                );
                                                                                ?>][area]"
                                                                        >
                                                                                <?php
                                                                                foreach (
                                                                                        $areas
                                                                                        as $value => $label
                                                                                ) :
                                                                                        ?>
                                                                                        <option
                                                                                                value="<?php
                                                                                                echo esc_attr(
                                                                                                        $value
                                                                                                );
                                                                                                ?>"
                                                                                                <?php
                                                                                                selected(
                                                                                                        isset(
                                                                                                                $section[
                                                                                                                        'area'
                                                                                                                ]
                                                                                                        )
                                                                                                                ? $section[
                                                                                                                        'area'
                                                                                                                ]
                                                                                                                : '',
                                                                                                        $value
                                                                                                );
                                                                                                ?>
                                                                                        >
                                                                                                <?php
                                                                                                echo esc_html(
                                                                                                        $label
                                                                                                );
                                                                                                ?>
                                                                                        </option>
                                                                                <?php endforeach; ?>
                                                                        </select>
                                                                </td>
                                                        </tr>

                                                        <tr>
                                                                <th scope="row">
                                                                        Estado
                                                                </th>
                                                                <td>
                                                                        <select
                                                                                name="instance[sections][<?php
                                                                                echo esc_attr(
                                                                                        $slug
                                                                                );
                                                                                ?>][state]"
                                                                        >
                                                                                <?php
                                                                                foreach (
                                                                                        $section_states
                                                                                        as $value => $label
                                                                                ) :
                                                                                        ?>
                                                                                        <option
                                                                                                value="<?php
                                                                                                echo esc_attr(
                                                                                                        $value
                                                                                                );
                                                                                                ?>"
                                                                                                <?php
                                                                                                selected(
                                                                                                        isset(
                                                                                                                $section[
                                                                                                                        'state'
                                                                                                                ]
                                                                                                        )
                                                                                                                ? $section[
                                                                                                                        'state'
                                                                                                                ]
                                                                                                                : 'active',
                                                                                                        $value
                                                                                                );
                                                                                                ?>
                                                                                        >
                                                                                                <?php
                                                                                                echo esc_html(
                                                                                                        $label
                                                                                                );
                                                                                                ?>
                                                                                        </option>
                                                                                <?php endforeach; ?>
                                                                        </select>
                                                                </td>
                                                        </tr>

                                                        <tr>
                                                                <th scope="row">
                                                                        Features
                                                                </th>
                                                                <td>
                                                                        <?php
                                                                        foreach (
                                                                                array(
                                                                                        'feature_file' =>
                                                                                                'Archivo',
                                                                                        'feature_location' =>
                                                                                                'Ubicación',
                                                                                        'feature_comments' =>
                                                                                                'Comentarios',
                                                                                )
                                                                                as $key => $label
                                                                        ) :
                                                                                ?>
                                                                                <label
                                                                                        style="margin-right:20px"
                                                                                >
                                                                                        <input
                                                                                                type="checkbox"
                                                                                                name="instance[sections][<?php
                                                                                                echo esc_attr(
                                                                                                        $slug
                                                                                                );
                                                                                                ?>][<?php
                                                                                                echo esc_attr(
                                                                                                        $key
                                                                                                );
                                                                                                ?>]"
                                                                                                value="1"
                                                                                                <?php
                                                                                                checked(
                                                                                                        ! empty(
                                                                                                                $section[
                                                                                                                        $key
                                                                                                                ]
                                                                                                        )
                                                                                                );
                                                                                                ?>
                                                                                        >
                                                                                        <?php
                                                                                        echo esc_html(
                                                                                                $label
                                                                                        );
                                                                                        ?>
                                                                                </label>
                                                                        <?php endforeach; ?>
                                                                </td>
                                                        </tr>
                                                </tbody>
                                        </table>
                                </details>

                        <?php endforeach; ?>

                        <h2>Tipos</h2>

                        <p>
                                Los Tipos usados pueden renombrarse,
                                reordenarse o cambiar de estado. Si se propone
                                mover un Tipo con historia a otra sección, el
                                Engine rechazará la PROPUESTA.
                        </p>

                        <table
                                class="widefat striped bitacora-structure-table"
                        >
                                <thead>
                                        <tr>
                                                <th>Tipo</th>
                                                <th>Slug</th>
                                                <th>Sección</th>
                                                <th>Orden</th>
                                                <th>Estado</th>
                                        </tr>
                                </thead>

                                <tbody>
                                        <?php
                                        foreach (
                                                $classes
                                                as $slug => $class
                                        ) :
                                                ?>
                                                <tr>
                                                        <td>
                                                                <input
                                                                        type="hidden"
                                                                        name="instance[classes][<?php
                                                                        echo esc_attr(
                                                                                $slug
                                                                        );
                                                                        ?>][_present]"
                                                                        value="1"
                                                                >

                                                                <input
                                                                        type="text"
                                                                        name="instance[classes][<?php
                                                                        echo esc_attr(
                                                                                $slug
                                                                        );
                                                                        ?>][name]"
                                                                        value="<?php
                                                                        echo esc_attr(
                                                                                isset(
                                                                                        $class[
                                                                                                'name'
                                                                                        ]
                                                                                )
                                                                                        ? $class[
                                                                                                'name'
                                                                                        ]
                                                                                        : ''
                                                                        );
                                                                        ?>"
                                                                >
                                                        </td>

                                                        <td>
                                                                <code><?php
                                                                echo esc_html(
                                                                        $slug
                                                                );
                                                                ?></code>
                                                        </td>

                                                        <td>
                                                                <select
                                                                        name="instance[classes][<?php
                                                                        echo esc_attr(
                                                                                $slug
                                                                        );
                                                                        ?>][scope_id]"
                                                                >
                                                                        <?php
                                                                        foreach (
                                                                                $scope_options
                                                                                as $scope_slug => $scope_label
                                                                        ) :
                                                                                ?>
                                                                                <option
                                                                                        value="<?php
                                                                                        echo esc_attr(
                                                                                                $scope_slug
                                                                                        );
                                                                                        ?>"
                                                                                        <?php
                                                                                        selected(
                                                                                                isset(
                                                                                                        $class[
                                                                                                                'scope_id'
                                                                                                        ]
                                                                                                )
                                                                                                        ? $class[
                                                                                                                'scope_id'
                                                                                                        ]
                                                                                                        : '',
                                                                                                $scope_slug
                                                                                        );
                                                                                        ?>
                                                                                >
                                                                                        <?php
                                                                                        echo esc_html(
                                                                                                $scope_label
                                                                                        );
                                                                                        ?>
                                                                                        (<?php
                                                                                        echo esc_html(
                                                                                                $scope_slug
                                                                                        );
                                                                                        ?>)
                                                                                </option>
                                                                        <?php endforeach; ?>
                                                                </select>
                                                        </td>

                                                        <td>
                                                                <input
                                                                        type="number"
                                                                        name="instance[classes][<?php
                                                                        echo esc_attr(
                                                                                $slug
                                                                        );
                                                                        ?>][order]"
                                                                        value="<?php
                                                                        echo esc_attr(
                                                                                isset(
                                                                                        $class[
                                                                                                'order'
                                                                                        ]
                                                                                )
                                                                                        ? (int)
                                                                                          $class[
                                                                                                'order'
                                                                                          ]
                                                                                        : 0
                                                                        );
                                                                        ?>"
                                                                >
                                                        </td>

                                                        <td>
                                                                <select
                                                                        name="instance[classes][<?php
                                                                        echo esc_attr(
                                                                                $slug
                                                                        );
                                                                        ?>][state]"
                                                                >
                                                                        <?php
                                                                        foreach (
                                                                                $class_states
                                                                                as $value => $label
                                                                        ) :
                                                                                ?>
                                                                                <option
                                                                                        value="<?php
                                                                                        echo esc_attr(
                                                                                                $value
                                                                                        );
                                                                                        ?>"
                                                                                        <?php
                                                                                        selected(
                                                                                                isset(
                                                                                                        $class[
                                                                                                                'state'
                                                                                                        ]
                                                                                                )
                                                                                                        ? $class[
                                                                                                                'state'
                                                                                                        ]
                                                                                                        : 'active',
                                                                                                $value
                                                                                        );
                                                                                        ?>
                                                                                >
                                                                                        <?php
                                                                                        echo esc_html(
                                                                                                $label
                                                                                        );
                                                                                        ?>
                                                                                </option>
                                                                        <?php endforeach; ?>
                                                                </select>
                                                        </td>
                                                </tr>
                                        <?php endforeach; ?>
                                </tbody>
                        </table>

                        <h2>Agregar sección</h2>

                        <p>
                                La nueva sección nace con un slug técnico que,
                                una vez incorporado a la PROPUESTA, queda fijo.
                        </p>

                        <table class="form-table">
                                <tbody>
                                        <tr>
                                                <th scope="row">
                                                        Nombre
                                                </th>
                                                <td>
                                                        <input
                                                                type="text"
                                                                class="regular-text<?php
                                                                echo $field_has_error(
                                                                        'new_section.name'
                                                                )
                                                                        ? ' bitacora-field-error'
                                                                        : '';
                                                                ?>"
                                                                id="bitacora-new-section-name"
                                                                name="instance[new_section][name]"
                                                                value="<?php
                                                                echo esc_attr(
                                                                        (string)
                                                                        $get_form_input(
                                                                                'new_section',
                                                                                'name',
                                                                                ''
                                                                        )
                                                                );
                                                                ?>"
                                                                aria-invalid="<?php
                                                                echo $field_has_error(
                                                                        'new_section.name'
                                                                )
                                                                        ? 'true'
                                                                        : 'false';
                                                                ?>"
                                                        >
                                                </td>
                                        </tr>

                                        <tr>
                                                <th scope="row">
                                                        Slug
                                                </th>
                                                <td>
                                                        <input
                                                                type="text"
                                                                class="regular-text<?php
                                                                echo $field_has_error(
                                                                        'new_section.slug'
                                                                )
                                                                        ? ' bitacora-field-error'
                                                                        : '';
                                                                ?>"
                                                                id="bitacora-new-section-slug"
                                                                name="instance[new_section][slug]"
                                                                value="<?php
                                                                echo esc_attr(
                                                                        (string)
                                                                        $get_form_input(
                                                                                'new_section',
                                                                                'slug',
                                                                                ''
                                                                        )
                                                                );
                                                                ?>"
                                                                placeholder="Ej.: otra-seccion"
                                                                aria-invalid="<?php
                                                                echo $field_has_error(
                                                                        'new_section.slug'
                                                                )
                                                                        ? 'true'
                                                                        : 'false';
                                                                ?>"
                                                        >
                                                </td>
                                        </tr>

                                        <tr>
                                                <th scope="row">
                                                        Orden
                                                </th>
                                                <td>
                                                        <input
                                                                type="number"
                                                                name="instance[new_section][order]"
                                                                value="<?php
                                                                echo esc_attr(
                                                                        (string)
                                                                        $get_form_input(
                                                                                'new_section',
                                                                                'order',
                                                                                '100'
                                                                        )
                                                                );
                                                                ?>"
                                                        >
                                                </td>
                                        </tr>

                                        <tr>
                                                <th scope="row">
                                                        Área
                                                </th>
                                                <td>
                                                        <select
                                                                name="instance[new_section][area]"
                                                        >
                                                                <option
                                                                        value="main"
                                                                        <?php
                                                                        selected(
                                                                                'main',
                                                                                (string)
                                                                                $get_form_input(
                                                                                        'new_section',
                                                                                        'area',
                                                                                        'more'
                                                                                )
                                                                        );
                                                                        ?>
                                                                >
                                                                        Principal
                                                                </option>
                                                                <option
                                                                        value="more"
                                                                        <?php
                                                                        selected(
                                                                                'more',
                                                                                (string)
                                                                                $get_form_input(
                                                                                        'new_section',
                                                                                        'area',
                                                                                        'more'
                                                                                )
                                                                        );
                                                                        ?>
                                                                >
                                                                        Más
                                                                </option>
                                                        </select>
                                                </td>
                                        </tr>

                                        <tr>
                                                <th scope="row">
                                                        Estado
                                                </th>
                                                <td>
                                                        <select
                                                                name="instance[new_section][state]"
                                                        >
                                                                <option
                                                                        value="active"
                                                                        <?php
                                                                        selected(
                                                                                'active',
                                                                                (string)
                                                                                $get_form_input(
                                                                                        'new_section',
                                                                                        'state',
                                                                                        'active'
                                                                                )
                                                                        );
                                                                        ?>
                                                                >
                                                                        Activa
                                                                </option>
                                                                <option
                                                                        value="hidden"
                                                                        <?php
                                                                        selected(
                                                                                'hidden',
                                                                                (string)
                                                                                $get_form_input(
                                                                                        'new_section',
                                                                                        'state',
                                                                                        'active'
                                                                                )
                                                                        );
                                                                        ?>
                                                                >
                                                                        Oculta
                                                                </option>
                                                                <option
                                                                        value="archived"
                                                                        <?php
                                                                        selected(
                                                                                'archived',
                                                                                (string)
                                                                                $get_form_input(
                                                                                        'new_section',
                                                                                        'state',
                                                                                        'active'
                                                                                )
                                                                        );
                                                                        ?>
                                                                >
                                                                        Archivada
                                                                </option>
                                                        </select>
                                                </td>
                                        </tr>

                                        <tr>
                                                <th scope="row">
                                                        Features
                                                </th>
                                                <td>
                                                        <label
                                                                style="margin-right:20px"
                                                        >
                                                                <input
                                                                        type="checkbox"
                                                                        name="instance[new_section][feature_file]"
                                                                        value="1"
                                                                        <?php
                                                                        checked(
                                                                                ! empty(
                                                                                        $form_input[
                                                                                                'new_section'
                                                                                        ][
                                                                                                'feature_file'
                                                                                        ]
                                                                                )
                                                                        );
                                                                        ?>
                                                                >
                                                                Archivo
                                                        </label>

                                                        <label
                                                                style="margin-right:20px"
                                                        >
                                                                <input
                                                                        type="checkbox"
                                                                        name="instance[new_section][feature_location]"
                                                                        value="1"
                                                                        <?php
                                                                        checked(
                                                                                ! empty(
                                                                                        $form_input[
                                                                                                'new_section'
                                                                                        ][
                                                                                                'feature_location'
                                                                                        ]
                                                                                )
                                                                        );
                                                                        ?>
                                                                >
                                                                Ubicación
                                                        </label>

                                                        <label>
                                                                <input
                                                                        type="checkbox"
                                                                        name="instance[new_section][feature_comments]"
                                                                        value="1"
                                                                        <?php
                                                                        checked(
                                                                                ! empty(
                                                                                        $form_input[
                                                                                                'new_section'
                                                                                        ][
                                                                                                'feature_comments'
                                                                                        ]
                                                                                )
                                                                        );
                                                                        ?>
                                                                >
                                                                Comentarios
                                                        </label>
                                                </td>
                                        </tr>
                                </tbody>
                        </table>


                        <h2>Agregar Tipo</h2>

                        <p>
                                El nuevo Tipo puede asociarse a una sección
                                existente o a la nueva sección que estés
                                preparando arriba. Cuando la nueva sección
                                tenga Nombre y Slug, aparecerá en esta lista
                                marcada como <strong>— nueva</strong>.
                        </p>

                        <table class="form-table">
                                <tbody>
                                        <tr>
                                                <th scope="row">
                                                        Nombre
                                                </th>
                                                <td>
                                                        <input
                                                                type="text"
                                                                class="regular-text<?php
                                                                echo $field_has_error(
                                                                        'new_class.name'
                                                                )
                                                                        ? ' bitacora-field-error'
                                                                        : '';
                                                                ?>"
                                                                id="bitacora-new-class-name"
                                                                name="instance[new_class][name]"
                                                                value="<?php
                                                                echo esc_attr(
                                                                        (string)
                                                                        $get_form_input(
                                                                                'new_class',
                                                                                'name',
                                                                                ''
                                                                        )
                                                                );
                                                                ?>"
                                                                aria-invalid="<?php
                                                                echo $field_has_error(
                                                                        'new_class.name'
                                                                )
                                                                        ? 'true'
                                                                        : 'false';
                                                                ?>"
                                                        >
                                                </td>
                                        </tr>

                                        <tr>
                                                <th scope="row">
                                                        Slug
                                                </th>
                                                <td>
                                                        <input
                                                                type="text"
                                                                class="regular-text<?php
                                                                echo $field_has_error(
                                                                        'new_class.slug'
                                                                )
                                                                        ? ' bitacora-field-error'
                                                                        : '';
                                                                ?>"
                                                                id="bitacora-new-class-slug"
                                                                name="instance[new_class][slug]"
                                                                value="<?php
                                                                echo esc_attr(
                                                                        (string)
                                                                        $get_form_input(
                                                                                'new_class',
                                                                                'slug',
                                                                                ''
                                                                        )
                                                                );
                                                                ?>"
                                                                placeholder="Ej.: otra-seccion-tipo"
                                                                aria-invalid="<?php
                                                                echo $field_has_error(
                                                                        'new_class.slug'
                                                                )
                                                                        ? 'true'
                                                                        : 'false';
                                                                ?>"
                                                        >
                                                </td>
                                        </tr>

                                        <tr>
                                                <th scope="row">
                                                        Sección
                                                </th>
                                                <td>
                                                        <select
                                                                id="bitacora-new-class-scope"
                                                                class="<?php
                                                                echo $field_has_error(
                                                                        'new_class.scope_id'
                                                                )
                                                                        ? 'bitacora-field-error'
                                                                        : '';
                                                                ?>"
                                                                name="instance[new_class][scope_id]"
                                                                data-requested-scope="<?php
                                                                echo esc_attr(
                                                                        (string)
                                                                        $get_form_input(
                                                                                'new_class',
                                                                                'scope_id',
                                                                                ''
                                                                        )
                                                                );
                                                                ?>"
                                                                aria-invalid="<?php
                                                                echo $field_has_error(
                                                                        'new_class.scope_id'
                                                                )
                                                                        ? 'true'
                                                                        : 'false';
                                                                ?>"
                                                        >
                                                                <option
                                                                        value=""
                                                                        <?php
                                                                        selected(
                                                                                '',
                                                                                (string)
                                                                                $get_form_input(
                                                                                        'new_class',
                                                                                        'scope_id',
                                                                                        ''
                                                                                )
                                                                        );
                                                                        ?>
                                                                >
                                                                        — Seleccioná una sección —
                                                                </option>
                                                                <?php
                                                                foreach (
                                                                        $scope_options
                                                                        as $scope_slug => $scope_label
                                                                ) :
                                                                        ?>
                                                                        <option
                                                                                value="<?php
                                                                                echo esc_attr(
                                                                                        $scope_slug
                                                                                );
                                                                                ?>"
                                                                                <?php
                                                                                selected(
                                                                                        (string)
                                                                                        $scope_slug,
                                                                                        (string)
                                                                                        $get_form_input(
                                                                                                'new_class',
                                                                                                'scope_id',
                                                                                                ''
                                                                                        )
                                                                                );
                                                                                ?>
                                                                        >
                                                                                <?php
                                                                                echo esc_html(
                                                                                        $scope_label
                                                                                );
                                                                                ?>
                                                                                (<?php
                                                                                echo esc_html(
                                                                                        $scope_slug
                                                                                );
                                                                                ?>)
                                                                        </option>
                                                                <?php endforeach; ?>
                                                        </select>

                                                        <p
                                                                id="bitacora-new-class-scope-help"
                                                                class="description"
                                                                aria-live="polite"
                                                        >
                                                                Si este Tipo pertenece a una
                                                                Sección nueva, completá arriba
                                                                el Nombre y el Slug de esa
                                                                Sección.
                                                        </p>
                                                </td>
                                        </tr>

                                        <tr>
                                                <th scope="row">
                                                        Orden
                                                </th>
                                                <td>
                                                        <input
                                                                type="number"
                                                                name="instance[new_class][order]"
                                                                value="<?php
                                                                echo esc_attr(
                                                                        (string)
                                                                        $get_form_input(
                                                                                'new_class',
                                                                                'order',
                                                                                '100'
                                                                        )
                                                                );
                                                                ?>"
                                                        >
                                                </td>
                                        </tr>

                                        <tr>
                                                <th scope="row">
                                                        Estado
                                                </th>
                                                <td>
                                                        <select
                                                                name="instance[new_class][state]"
                                                        >
                                                                <option
                                                                        value="active"
                                                                        <?php
                                                                        selected(
                                                                                'active',
                                                                                (string)
                                                                                $get_form_input(
                                                                                        'new_class',
                                                                                        'state',
                                                                                        'active'
                                                                                )
                                                                        );
                                                                        ?>
                                                                >
                                                                        Activo
                                                                </option>
                                                                <option
                                                                        value="hidden"
                                                                        <?php
                                                                        selected(
                                                                                'hidden',
                                                                                (string)
                                                                                $get_form_input(
                                                                                        'new_class',
                                                                                        'state',
                                                                                        'active'
                                                                                )
                                                                        );
                                                                        ?>
                                                                >
                                                                        Oculto
                                                                </option>
                                                                <option
                                                                        value="archived"
                                                                        <?php
                                                                        selected(
                                                                                'archived',
                                                                                (string)
                                                                                $get_form_input(
                                                                                        'new_class',
                                                                                        'state',
                                                                                        'active'
                                                                                )
                                                                        );
                                                                        ?>
                                                                >
                                                                        Archivado
                                                                </option>
                                                        </select>
                                                </td>
                                        </tr>
                                </tbody>
                        </table>


                        <?php
                        if (
                                is_array( $preview )
                                && ! empty(
                                        $preview['action_count']
                                )
                        ) :
                                ?>
                                <div class="notice notice-warning inline">
                                        <p>
                                                <strong>
                                                        Revisá la vista previa
                                                        antes de aplicar.
                                                </strong>
                                        </p>

                                        <p>
                                                APPLY ejecutará exactamente
                                                la PROPUESTA cuyo fingerprint
                                                fue revisado. Si modificás
                                                cualquier campo, el Engine
                                                exigirá una nueva vista previa.
                                        </p>

                                        <p>
                                                <label>
                                                        <input
                                                                type="checkbox"
                                                                name="confirm_apply"
                                                                value="1"
                                                        >
                                                        Confirmo que revisé
                                                        estos cambios y deseo
                                                        aplicarlos.
                                                </label>
                                        </p>
                                </div>
                        <?php endif; ?>

                        <p class="submit">
                                <button
                                        type="submit"
                                        class="button button-primary"
                                        name="bitacora_instance_editor_action"
                                        value="preview"
                                >PREVISUALIZAR CAMBIOS</button>

                                <?php
                                if (
                                        is_array( $preview )
                                        && ! empty(
                                                $preview['action_count']
                                        )
                                ) :
                                        ?>
                                        <button
                                                type="submit"
                                                class="button button-secondary"
                                                name="bitacora_instance_editor_action"
                                                value="apply"
                                                style="margin-left:8px;"
                                        >APLICAR CAMBIOS</button>
                                <?php endif; ?>

                                <a
                                        class="button button-secondary"
                                        href="<?php echo esc_url(
                                                bitacora_get_profiles_admin_url()
                                        ); ?>"
                                        style="margin-left:8px;"
                                >Cancelar</a>
                        </p>
                </form>

                <script>
                (function() {
                        const sectionName =
                                document.getElementById(
                                        'bitacora-new-section-name'
                                );
                        const sectionSlug =
                                document.getElementById(
                                        'bitacora-new-section-slug'
                                );
                        const className =
                                document.getElementById(
                                        'bitacora-new-class-name'
                                );
                        const classSlug =
                                document.getElementById(
                                        'bitacora-new-class-slug'
                                );
                        const scope =
                                document.getElementById(
                                        'bitacora-new-class-scope'
                                );
                        const help =
                                document.getElementById(
                                        'bitacora-new-class-scope-help'
                                );

                        let requestedScope =
                                scope
                                        ? (
                                                scope.getAttribute(
                                                        'data-requested-scope'
                                                )
                                                || ''
                                        )
                                        : '';

                        if (
                                ! sectionName
                                || ! sectionSlug
                                || ! className
                                || ! classSlug
                                || ! scope
                                || ! help
                        ) {
                                return;
                        }


                        function hasValue( fields ) {

                                return fields.some(
                                        function( field ) {

                                                return (
                                                        ''
                                                        !== field.value.trim()
                                                );
                                        }
                                );
                        }


                        function setHelp( message, warning ) {

                                help.textContent = message;

                                help.classList.toggle(
                                        'bitacora-editor-warning',
                                        Boolean( warning )
                                );
                        }


                        function refreshScope() {

                                const name =
                                        sectionName.value.trim();

                                const slug =
                                        sectionSlug.value.trim();

                                const oldDynamic =
                                        scope.querySelector(
                                                'option[data-bitacora-new-section="1"]'
                                        );

                                const wasDynamicSelected =
                                        (
                                                oldDynamic
                                                && scope.value
                                                        === oldDynamic.value
                                        );

                                if ( oldDynamic ) {
                                        oldDynamic.remove();
                                }


                                if (
                                        '' !== name
                                        && '' !== slug
                                ) {

                                        const duplicate =
                                                Array.from(
                                                        scope.options
                                                ).some(
                                                        function( option ) {

                                                                return (
                                                                        option.value
                                                                        === slug
                                                                );
                                                        }
                                                );

                                        if ( duplicate ) {
                                                setHelp(
                                                        'Ese Slug ya pertenece '
                                                        + 'a una Sección '
                                                        + 'existente.',
                                                        true
                                                );

                                                return;
                                        }


                                        const option =
                                                new Option(
                                                        name
                                                        + ' ('
                                                        + slug
                                                        + ') — nueva',
                                                        slug
                                                );

                                        option.setAttribute(
                                                'data-bitacora-new-section',
                                                '1'
                                        );

                                        scope.appendChild(
                                                option
                                        );

                                        if (
                                                wasDynamicSelected
                                                || requestedScope === slug
                                        ) {
                                                scope.value = slug;
                                                requestedScope = '';
                                        }


                                        if ( scope.value === slug ) {
                                                setHelp(
                                                        'Este Tipo se asociará '
                                                        + 'a la nueva Sección '
                                                        + name
                                                        + ' ('
                                                        + slug
                                                        + ').',
                                                        false
                                                );

                                                return;
                                        }


                                        if (
                                                hasValue(
                                                        [
                                                                className,
                                                                classSlug,
                                                        ]
                                                )
                                                && '' === scope.value
                                        ) {
                                                setHelp(
                                                        'La nueva Sección ya '
                                                        + 'está disponible. '
                                                        + 'Seleccionála para '
                                                        + 'asociar este Tipo.',
                                                        true
                                                );

                                                return;
                                        }


                                        setHelp(
                                                'La nueva Sección '
                                                + name
                                                + ' ('
                                                + slug
                                                + ') ya está disponible '
                                                + 'en la lista.',
                                                false
                                        );

                                        return;
                                }


                                if (
                                        hasValue(
                                                [
                                                        className,
                                                        classSlug,
                                                ]
                                        )
                                        && '' === scope.value
                                ) {

                                        if (
                                                hasValue(
                                                        [
                                                                sectionName,
                                                                sectionSlug,
                                                        ]
                                                )
                                        ) {
                                                setHelp(
                                                        'Completá Nombre y '
                                                        + 'Slug de la nueva '
                                                        + 'Sección para poder '
                                                        + 'asociarle este Tipo.',
                                                        true
                                                );
                                        } else {
                                                setHelp(
                                                        'Elegí una Sección. '
                                                        + 'Si todavía no '
                                                        + 'existe, completá '
                                                        + 'arriba su Nombre '
                                                        + 'y Slug.',
                                                        true
                                                );
                                        }

                                        return;
                                }


                                setHelp(
                                        'Si este Tipo pertenece a una '
                                        + 'Sección nueva, completá arriba '
                                        + 'el Nombre y el Slug de esa '
                                        + 'Sección.',
                                        false
                                );
                        }


                        [
                                sectionName,
                                sectionSlug,
                                className,
                                classSlug
                        ].forEach(
                                function( field ) {

                                        field.addEventListener(
                                                'input',
                                                refreshScope
                                        );
                                }
                        );

                        scope.addEventListener(
                                'change',
                                function() {

                                        requestedScope = '';
                                        refreshScope();
                                }
                        );

                        refreshScope();
                }());
                </script>

                <p>
                        <em>
                                APPLY sólo se habilita después de una
                                vista previa válida y ejecuta exactamente
                                la PROPUESTA revisada.
                        </em>
                </p>
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


        $catalog = bitacora_get_profile_catalog();

        $current_profile_id = bitacora_get_configured_profile_id();
        $configured_at      = bitacora_get_configured_at();

        $configured_at_label = $configured_at
            ? wp_date(
                'd/m/Y H:i T',
                $configured_at
            )
            : '';

        $close_now_label = wp_date(
            'd/m/Y H:i T',
            time()
        );

        $change_impact = bitacora_get_profile_change_impact();

        $confirm_close = (
            '' !== $current_profile_id
            && isset( $_GET['confirm_close'] )
            && '1' === sanitize_text_field(
                wp_unslash( $_GET['confirm_close'] )
            )
        );

        $confirm_delete_profile_id = isset(
            $_GET['confirm_delete_profile']
        )
            ? sanitize_key(
                wp_unslash( $_GET['confirm_delete_profile'] )
            )
            : '';

        $current_profile_label  = '';
        $confirm_delete_profile = false;

        foreach ( $catalog as $catalog_profile ) {

            if (
                $current_profile_id === $catalog_profile['id']
            ) {
                $current_profile_label =
                    (string) $catalog_profile['label'];
            }

            if (
                $confirm_delete_profile_id === $catalog_profile['id']
                && ! empty( $catalog_profile['deletable'] )
            ) {
                $confirm_delete_profile = $catalog_profile;
            }
        }


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

                <?php if ( 'used' === $notice ) : ?>

                        <div class="notice notice-success inline">
                                <p>Perfil puesto en uso.</p>
                        </div>

                <?php elseif ( 'use_error' === $notice ) : ?>

                        <div class="notice notice-error inline">
                                <p>
                                        <?php
                                        if (
                                            'bitacora_profile_state_changed'
                                            === $error_code
                                        ) {
                                            echo esc_html(
                                                'El perfil en uso cambió. Revisá el estado actual antes de continuar.'
                                            );
                                        } elseif (
                                            'bitacora_profile_content_changed'
                                            === $error_code
                                        ) {
                                            echo esc_html(
                                                'El contenido de la Bitácora cambió desde la confirmación. Revisá los nuevos conteos antes de continuar.'
                                            );
                                        } else {
                                            echo esc_html(
                                                'No se pudo poner el perfil en uso.'
                                            );
                                        }
                                        ?>
                                </p>
                        </div>

                <?php endif; ?>

                <?php if ( 'closed' === $notice ) : ?>

                        <div class="notice notice-success inline">
                                <p>Bitácora cerrada. La instalación quedó sin un perfil en uso.</p>
                        </div>

                <?php elseif ( 'close_error' === $notice ) : ?>

                        <div class="notice notice-error inline">
                                <p>
                                        <?php
                                        if (
                                            'bitacora_profile_state_changed'
                                            === $error_code
                                        ) {
                                            echo esc_html(
                                                'El perfil en uso cambió. Revisá el estado actual antes de continuar.'
                                            );
                                        } elseif (
                                            'bitacora_profile_content_changed'
                                            === $error_code
                                        ) {
                                            echo esc_html(
                                                'El contenido de la Bitácora cambió desde la confirmación. Revisá los nuevos conteos antes de continuar.'
                                            );
                                        } else {
                                            echo esc_html(
                                                'No se pudo cerrar completamente la Bitácora.'
                                            );
                                        }
                                        ?>
                                </p>
                        </div>

                <?php endif; ?>

                <?php if ( 'deleted' === $notice ) : ?>

                        <div class="notice notice-success inline">
                                <p>Perfil eliminado.</p>
                        </div>

                <?php elseif ( 'delete_error' === $notice ) : ?>

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

                <?php if ( $confirm_close ) : ?>

                        <div class="notice notice-warning inline">

                                <p>
                                        <strong>
                                                LEÉ ATENTAMENTE ANTES DE CONTINUAR
                                        </strong>
                                </p>

                                <h2>
                                        Cerrar Bitácora
                                        <?php if ( '' !== $current_profile_label ) : ?>
                                                — <?php echo esc_html( $current_profile_label ); ?>
                                        <?php endif; ?>
                                </h2>

                                <p>
                                        Vas a cerrar definitivamente la Bitácora actualmente en uso.
                                </p>

                                <p>
                                        <strong>Período de actividad</strong><br>
                                        Desde:
                                        <strong><?php
                                        echo esc_html(
                                            $configured_at_label
                                                ?: 'fecha no disponible'
                                        );
                                        ?></strong><br>
                                        Hasta:
                                        <strong><?php
                                        echo esc_html(
                                            $close_now_label
                                        );
                                        ?></strong>
                                </p>

                                <p>
                                        Todo el contenido generado durante este período
                                        será eliminado definitivamente.
                                </p>

                                <p>
                                        Se eliminarán:
                                </p>

                                <ul>
                                        <li>
                                                <strong><?php
                                                echo esc_html(
                                                    number_format_i18n(
                                                        $change_impact['items']
                                                    )
                                                );
                                                ?></strong>
                                                entradas
                                        </li>

                                        <li>
                                                <strong><?php
                                                echo esc_html(
                                                    number_format_i18n(
                                                        $change_impact['comments']
                                                    )
                                                );
                                                ?></strong>
                                                comentarios
                                        </li>

                                        <li>
                                                <strong><?php
                                                echo esc_html(
                                                    number_format_i18n(
                                                        $change_impact['attachments']
                                                    )
                                                );
                                                ?></strong>
                                                archivos adjuntos
                                        </li>
                                </ul>

                                <p>
                                        También se eliminarán las secciones,
                                        tipos y páginas materializadas del perfil
                                        actual.
                                </p>

                                <p>
                                        <strong>
                                                Esta es una acción destructiva e
                                                irreversible desde Bitácora.
                                        </strong>
                                </p>

                                <p>
                                        Los archivos adjuntos también se borrarán
                                        físicamente del servidor.
                                </p>

                                <p>
                                        Después del cierre, Bitácora permanecerá
                                        instalada y funcionando, pero quedará sin
                                        ningún Profile en uso. Para volver a utilizar
                                        sus funciones normales deberás seleccionar
                                        explícitamente uno de los Profiles disponibles.
                                </p>

                                <p>
                                        Si necesitás conservar una copia documental
                                        de esta instancia, cancelá esta operación y
                                        utilizá <strong>RESPALDO DOCUMENTAL</strong>
                                        desde la pantalla de perfiles.
                                </p>

                                <p>
                                        El Profile
                                        <strong><?php
                                        echo esc_html(
                                            $current_profile_label
                                                ?: 'actual'
                                        );
                                        ?></strong>
                                        permanecerá disponible en la biblioteca.
                                        Lo que se perderá definitivamente es el
                                        contenido producido por esta instancia.
                                </p>

                                <form
                                        method="post"
                                        action="<?php echo esc_url(
                                            admin_url( 'admin-post.php' )
                                        ); ?>"
                                >
                                        <input
                                                type="hidden"
                                                name="action"
                                                value="bitacora_close_bitacora"
                                        >

                                        <input
                                                type="hidden"
                                                name="current_profile_id"
                                                value="<?php echo esc_attr(
                                                    $current_profile_id
                                                ); ?>"
                                        >

                                        <input
                                                type="hidden"
                                                name="expected_items"
                                                value="<?php echo esc_attr(
                                                    $change_impact['items']
                                                ); ?>"
                                        >

                                        <input
                                                type="hidden"
                                                name="expected_comments"
                                                value="<?php echo esc_attr(
                                                    $change_impact['comments']
                                                ); ?>"
                                        >

                                        <input
                                                type="hidden"
                                                name="expected_attachments"
                                                value="<?php echo esc_attr(
                                                    $change_impact['attachments']
                                                ); ?>"
                                        >

                                        <?php
                                        wp_nonce_field(
                                            'bitacora_close_bitacora',
                                            'bitacora_close_bitacora_nonce'
                                        );
                                        ?>

                                        <p>
                                                <a
                                                        href="<?php echo esc_url(
                                                            bitacora_get_profiles_admin_url()
                                                        ); ?>"
                                                        class="button button-secondary"
                                                >CANCELAR</a>

                                                <?php
                                                submit_button(
                                                    'CERRAR BITÁCORA',
                                                    'delete',
                                                    'submit',
                                                    false
                                                );
                                                ?>
                                        </p>
                                </form>
                        </div>

                <?php endif; ?>


                <?php if ( $confirm_delete_profile ) : ?>

                        <div class="notice notice-warning inline">

                                <h2>Confirmar eliminación de perfil</h2>

                                <p>
                                        Vas a eliminar el perfil
                                        <strong><?php
                                        echo esc_html(
                                            $confirm_delete_profile['label']
                                        );
                                        ?></strong>.
                                </p>

                                <p>
                                        <strong>
                                                Se eliminará únicamente su
                                                definición persistida.
                                                Esta operación no se puede
                                                deshacer.
                                        </strong>
                                </p>

                                <form
                                        method="post"
                                        action="<?php echo esc_url(
                                            admin_url( 'admin-post.php' )
                                        ); ?>"
                                >
                                        <input
                                                type="hidden"
                                                name="action"
                                                value="bitacora_delete_profile"
                                        >

                                        <input
                                                type="hidden"
                                                name="profile_id"
                                                value="<?php echo esc_attr(
                                                    $confirm_delete_profile['id']
                                                ); ?>"
                                        >

                                        <?php
                                        wp_nonce_field(
                                            'bitacora_delete_profile_'
                                                . $confirm_delete_profile['id'],
                                            'bitacora_delete_profile_nonce'
                                        );
                                        ?>

                                        <p>
                                                <?php
                                                submit_button(
                                                    'Eliminar perfil',
                                                    'secondary',
                                                    'submit',
                                                    false
                                                );
                                                ?>

                                                <a
                                                        href="<?php echo esc_url(
                                                            bitacora_get_profiles_admin_url()
                                                        ); ?>"
                                                        class="button button-secondary"
                                                >Cancelar</a>
                                        </p>
                                </form>
                        </div>

                <?php endif; ?>


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
                                                <th scope="col">Acción</th>
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

								<?php if ( ! empty( $profile['deletable'] ) ) : ?>
									<div>
										<a
											href="<?php echo esc_url(
												add_query_arg(
													'confirm_delete_profile',
													$profile['id'],
													bitacora_get_profiles_admin_url()
												)
											); ?>"
										>Eliminar</a>
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

                                                        </td>

                                                        <td>

                                                                <?php
                                                                if (
                                                                    'in_use'
                                                                    === $status['key']
                                                                ) :
                                                                    ?>

                                                                        <form
                                                                                method="post"
                                                                                action="<?php echo esc_url(
                                                                                    admin_url(
                                                                                        'admin-post.php'
                                                                                    )
                                                                                ); ?>"
                                                                                style="display:inline"
                                                                        >
                                                                                <input
                                                                                        type="hidden"
                                                                                        name="action"
                                                                                        value="bitacora_documental_backup"
                                                                                >

                                                                                <input
                                                                                        type="hidden"
                                                                                        name="profile_id"
                                                                                        value="<?php echo esc_attr(
                                                                                            $current_profile_id
                                                                                        ); ?>"
                                                                                >

                                                                                <?php
                                                                                wp_nonce_field(
                                                                                    'bitacora_documental_backup',
                                                                                    'bitacora_documental_backup_nonce'
                                                                                );
                                                                                ?>

                                                                                <button
                                                                                        type="submit"
                                                                                        class="button button-primary"
                                                                                        title="Este respaldo no genera un paquete reutilizable directamente por Bitácora."
                                                                                >RESPALDO DOCUMENTAL ?</button>
                                                                        </form>

                                                                        <?php
                                                                        if (
                                                                            current_user_can(
                                                                                'maintain_bitacora_instance'
                                                                            )
                                                                        ) :
                                                                            ?>
                                                                                <a
                                                                                        href="<?php echo esc_url(
                                                                                            bitacora_get_instance_editor_admin_url()
                                                                                        ); ?>"
                                                                                        class="button button-secondary"
                                                                                        title="Edita la estructura de la instancia activa."
                                                                                >EDITAR INSTANCIA</a>
                                                                        <?php endif; ?>

                                                                        <a
                                                                                href="<?php echo esc_url(
                                                                                    add_query_arg(
                                                                                        'confirm_close',
                                                                                        '1',
                                                                                        bitacora_get_profiles_admin_url()
                                                                                    )
                                                                                ); ?>"
                                                                                class="button button-secondary"
                                                                                style="border-color:#b32d2e;color:#b32d2e;"
                                                                                title="Cierra definitivamente la instancia actual."
                                                                        >CERRAR</a>

                                                                <?php
                                                                elseif (
                                                                    'ready'
                                                                    === $status['key']
                                                                    && current_user_can(
                                                                        'use_bitacora_profiles'
                                                                    )
                                                                    && ''
                                                                    === $current_profile_id
                                                                ) :
                                                                    ?>

                                                                        <form
                                                                                method="post"
                                                                                action="<?php echo esc_url(
                                                                                    admin_url(
                                                                                        'admin-post.php'
                                                                                    )
                                                                                ); ?>"
                                                                        >
                                                                                <input
                                                                                        type="hidden"
                                                                                        name="action"
                                                                                        value="bitacora_use_profile"
                                                                                >

                                                                                <input
                                                                                        type="hidden"
                                                                                        name="profile_id"
                                                                                        value="<?php echo esc_attr(
                                                                                            $profile['id']
                                                                                        ); ?>"
                                                                                >

                                                                                <?php
                                                                                wp_nonce_field(
                                                                                    'bitacora_use_profile',
                                                                                    'bitacora_use_profile_nonce'
                                                                                );

                                                                                submit_button(
                                                                                    'Usar este perfil',
                                                                                    'secondary',
                                                                                    'submit',
                                                                                    false
                                                                                );
                                                                                ?>
                                                                        </form>

                                                                <?php else : ?>

                                                                        &mdash;

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
