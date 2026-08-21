<?php
/**
 * Bitácora - Kiosk mode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'obras_kiosk_admin_menu', 999 );
function obras_kiosk_admin_menu() {
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }
    foreach ( array(
        'index.php',
        'edit.php',
        'upload.php',
        'edit.php?post_type=page',
        'users.php',
        'plugins.php',
        'themes.php',
        'tools.php',
        'options-general.php'
    ) as $page ) {
        remove_menu_page( $page );
    }
}

add_action( 'wp_dashboard_setup', 'obras_kiosk_dashboard' );
function obras_kiosk_dashboard() {
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }
    remove_all_actions( 'wp_dashboard_setup' );
}

add_action( 'wp_before_admin_bar_render', 'obras_kiosk_admin_bar' );
function obras_kiosk_admin_bar() {
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }
    global $wp_admin_bar;
    foreach ( array( 'wp-logo', 'updates', 'comments', 'new-content' ) as $item ) {
        $wp_admin_bar->remove_menu( $item );
    }
}

add_filter( 'screen_options_show_screen', 'obras_hide_screen_options' );
function obras_hide_screen_options( $show ) {
    return current_user_can( 'manage_options' ) ? $show : false;
}


// ============================================================================
// === INTERFAZ WP-ADMIN LIMPIA PARA USUARIOS DE CONTENIDO =====================
// ============================================================================

/**
 * Oculta el chrome administrativo de WordPress a los usuarios de contenido.
 *
 * Las pantallas permanecen registradas normalmente y el acceso efectivo sigue
 * gobernado por capabilities y por admin-access.php. Esta capa es sólo visual.
 */
add_action( 'admin_head', 'obras_kiosk_hide_admin_chrome', 999 );

function obras_kiosk_hide_admin_chrome() {

    if (
        ! current_user_can( 'edit_bitacora_contents' )
        || current_user_can( 'manage_options' )
    ) {
        return;
    }

    ?>
    <style id="bitacora-kiosk-admin-chrome">
        #wpadminbar,
        #adminmenumain {
            display: none !important;
        }

        html.wp-toolbar {
            padding-top: 0 !important;
        }

        #wpcontent,
        #wpfooter {
            margin-left: 0 !important;
        }
    </style>
    <?php
}
