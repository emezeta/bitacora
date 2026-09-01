<?php
/**
 * Bitácora - Personalización Login/Register
 * Archivo: branding.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Carga el CSS común del child theme también en wp-login.php
 * para que login/register usen los estilos definidos en custom.css.
 */
add_action( 'login_enqueue_scripts', 'obras_login_enqueue_custom_css', 5 );
function obras_login_enqueue_custom_css() {
    $custom_style_path = get_stylesheet_directory() . '/css/custom.css';

    wp_enqueue_style(
        'obras-login-custom',
        get_stylesheet_directory_uri() . '/css/custom.css',
                     array(),
                     file_exists( $custom_style_path ) ? filemtime( $custom_style_path ) : wp_get_theme()->get( 'Version' )
    );
}

/**
 * URL del logo en pantalla de login.
 */
add_filter( 'login_headerurl', 'obras_login_header_url' );
function obras_login_header_url() {
    return home_url( '/' );
}

/**
 * Texto accesible del logo en pantalla de login/register.
 */
add_filter( 'login_headertext', 'obras_login_header_text' );
function obras_login_header_text() {
    $action = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : 'login';

    if ( $action === 'register' ) {
        return get_bloginfo( 'name' ) . ' - Registro a Bitácora';
    }

    return get_bloginfo( 'name' ) . ' - Acceso a Bitácora';
}

/**
 * Footer inferior en login/register.
 */
add_action( 'login_footer', 'obras_login_footer_branding' );
function obras_login_footer_branding() {
    echo '<div style="text-align:center; margin-top:30px; color:#666; font-size:12px;"><p>Bitácora</p></div>';
}

/**
 * Footer del admin.
 */
add_filter( 'admin_footer_text', 'obras_admin_footer_text' );
function obras_admin_footer_text() {
    return 'Gracias por usar <strong>Bitácora</strong> | <a href="' . esc_url( admin_url( 'profile.php' ) ) . '">Mi Perfil</a>';
}
