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
        'bitacora_item',
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
 * Bloquear cambios de autoría para usuarios no administradores.
 *
 * En contenido nuevo, el autor es el usuario que lo crea.
 * En contenido existente, se conserva siempre el autor original.
 */
add_filter( 'wp_insert_post_data', 'obras_preserve_post_author_for_non_admin', 99, 2 );
function obras_preserve_post_author_for_non_admin( $data, $postarr ) {
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

    $post_id = isset( $postarr['ID'] )
        ? absint( $postarr['ID'] )
        : 0;

    if ( $post_id ) {
        $existing_post = get_post( $post_id );

        if ( $existing_post instanceof WP_Post ) {
            $data['post_author'] = (int) $existing_post->post_author;

            return $data;
        }
    }

    $data['post_author'] = get_current_user_id();

    return $data;
}
