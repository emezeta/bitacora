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
 * Render de la pantalla inicial de perfiles.
 *
 * Esta primera iteración es deliberadamente sólo lectura.
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
