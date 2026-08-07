<?php
/**
 * Bitácora de Obra - Auth / Redirects
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ocultar admin bar para no-admins
add_filter( 'show_admin_bar', function( $show ) {
    return current_user_can( 'manage_options' ) ? $show : false;
} );

// Redirect después de login
add_filter( 'login_redirect', 'obras_frontend_login_redirect', 10, 3 );
function obras_frontend_login_redirect( $redirect_to, $request, $user ) {
    if ( current_user_can( 'manage_options' ) ) {
        return admin_url();
    }
    return home_url( '/' );
}

// Redirect después de logout
add_filter( 'logout_redirect', 'obras_logout_redirect_frontend', 10, 3 );
function obras_logout_redirect_frontend( $redirect_to, $requested_redirect_to, $user ) {
    return home_url( '/' );
}
