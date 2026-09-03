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
