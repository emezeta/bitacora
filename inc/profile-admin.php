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

                                                                        <button
                                                                                type="button"
                                                                                class="button button-secondary"
                                                                                disabled
                                                                                title="PAUSAR se incorporará en una etapa posterior."
                                                                        >PAUSAR</button>

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
