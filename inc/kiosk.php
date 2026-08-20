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
