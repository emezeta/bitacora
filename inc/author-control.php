<?php
/**
 * Bitácora de Obra - Control de autoría
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Post types de Bitácora.
 */
function obras_author_locked_post_types() {
    return array(
        'bitacora',
        'documento_obra',
        'material_obra',
        'catalogo_obra',
        'plano_obra',
    );
}

/**
 * Ocultar metabox "Autor" para no-admins.
 */
add_action( 'add_meta_boxes', 'obras_hide_author_metabox_for_non_admin', 99, 2 );
function obras_hide_author_metabox_for_non_admin( $post_type, $post ) {
    if ( ! is_admin() ) {
        return;
    }

    if ( current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( ! in_array( $post_type, obras_author_locked_post_types(), true ) ) {
        return;
    }

    remove_meta_box( 'authordiv', $post_type, 'normal' );
    remove_meta_box( 'authordiv', $post_type, 'side' );
    remove_meta_box( 'authordiv', $post_type, 'advanced' );
}

/**
 * Forzar autor = usuario logueado para no-admins.
 */
add_filter( 'wp_insert_post_data', 'obras_force_current_user_as_author', 99, 2 );
function obras_force_current_user_as_author( $data, $postarr ) {
    if ( ! is_admin() ) {
        return $data;
    }

    if ( current_user_can( 'manage_options' ) ) {
        return $data;
    }

    if ( empty( $data['post_type'] ) ) {
        return $data;
    }

    if ( ! in_array( $data['post_type'], obras_author_locked_post_types(), true ) ) {
        return $data;
    }

    if ( ! is_user_logged_in() ) {
        return $data;
    }

    $data['post_author'] = get_current_user_id();

    return $data;
}
