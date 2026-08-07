<?php
/**
 * Bitácora de Obra - Admin access control
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_init', 'obras_block_admin_access', 1 );
function obras_block_admin_access() {
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }

    $current = basename( $_SERVER['PHP_SELF'] );

    // Soportar GET y POST
    $post_type = '';
    if ( isset( $_REQUEST['post_type'] ) ) {
        $post_type = sanitize_key( wp_unslash( $_REQUEST['post_type'] ) );
    }

    $post_id = 0;
    if ( isset( $_REQUEST['post'] ) ) {
        $post_id = absint( $_REQUEST['post'] );
    } elseif ( isset( $_REQUEST['post_ID'] ) ) {
        $post_id = absint( $_REQUEST['post_ID'] );
    }

    $allowed_post_types = array(
        'bitacora',
        'documento_obra',
        'material_obra',
        'catalogo_obra',
        'plano_obra',
    );

    // =========================================================================
    // Páginas base permitidas para usuarios no admin
    // =========================================================================
    $allowed_pages = array(
        'profile.php',
        'user-edit.php',

        // Biblioteca / subida de medios
        'upload.php',
        'media-new.php',
        'async-upload.php',
        'media-upload.php',
        'admin-ajax.php',
        'admin-post.php',
    );

    if ( in_array( $current, $allowed_pages, true ) ) {
        return;
    }

    // =========================================================================
    // Crear contenido de los CPT del sistema
    // =========================================================================
    if ( $current === 'post-new.php' && in_array( $post_type, $allowed_post_types, true ) ) {
        return;
    }

    // =========================================================================
    // Guardar / editar contenido existente de los CPT del sistema
    // =========================================================================
    if ( $current === 'post.php' && $post_id ) {
        $edit_post_type = get_post_type( $post_id );

        if ( in_array( $edit_post_type, $allowed_post_types, true ) ) {
            return;
        }
    }

    // =========================================================================
    // Guardar por post_type cuando todavía no hay post_id firme
    // =========================================================================
    if ( $current === 'post.php' && in_array( $post_type, $allowed_post_types, true ) ) {
        return;
    }

    // =========================================================================
    // Listados admin de los CPT del sistema
    // =========================================================================
    if ( $current === 'edit.php' && in_array( $post_type, $allowed_post_types, true ) ) {
        return;
    }

    // =========================================================================
    // Adjuntar / seleccionar medios desde pantallas de edición de los CPT
    // =========================================================================
    if ( $current === 'post.php' || $current === 'post-new.php' ) {
        if ( $post_id ) {
            $edit_post_type = get_post_type( $post_id );

            if ( in_array( $edit_post_type, $allowed_post_types, true ) ) {
                return;
            }
        }

        if ( in_array( $post_type, $allowed_post_types, true ) ) {
            return;
        }
    }

    // =========================================================================
    // Cualquier otra cosa: afuera
    // =========================================================================
    if ( ! headers_sent() ) {
        wp_redirect( home_url( '/' ) );
        exit;
    }
}
