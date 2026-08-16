<?php
/**
 * Bitácora de Obra - Restricciones de acceso
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Post types protegidos del sistema.
 */
if ( ! function_exists( 'obras_get_protected_post_types' ) ) {
    function obras_get_protected_post_types() {
        return array(
            'bitacora',
            'bitacora_item',
            'documento_obra',
            'material_obra',
            'catalogo_obra',
            'plano_obra',
        );
    }
}

/**
 * Determina si una publicación protegida puede verse públicamente.
 *
 * Regla:
 * - Sólo aplica a publicaciones individuales de los ndmcp.
 * - Sólo permite contenido publicado.
 * - Sólo permite contenido marcado explícitamente con:
 *
 *     acceso_publico = 1
 *
 * Todo lo demás sigue requiriendo login.
 */
if ( ! function_exists( 'obras_is_public_single_content' ) ) {
    function obras_is_public_single_content() {
        if ( ! is_singular( obras_get_protected_post_types() ) ) {
            return false;
        }

        $post_id = get_queried_object_id();
        if ( ! $post_id ) {
            return false;
        }

        if ( 'publish' !== get_post_status( $post_id ) ) {
            return false;
        }

        return '1' === (string) get_post_meta( $post_id, 'acceso_publico', true );
    }
}

add_action( 'template_redirect', 'obras_restrict_pages' );
function obras_restrict_pages() {

    // =========================================================================
    // Bloquear acceso directo a singles de los ndmcp si no está logueado,
    // excepto publicaciones publicadas marcadas explícitamente como públicas.
    // =========================================================================
    if ( is_singular( obras_get_protected_post_types() ) ) {
        if ( obras_is_public_single_content() ) {
            return;
        }

        if ( ! is_user_logged_in() ) {
            wp_safe_redirect( wp_login_url( get_permalink() ) );
            exit;
        }
    }

    // =========================================================================
    // Restricciones para páginas protegidas por _allowed_users
    // =========================================================================
    if ( ! is_page() ) {
        return;
    }

    $page = get_queried_object();
    if ( ! $page instanceof WP_Post ) {
        return;
    }

    $parent_id = (int) get_option( 'obras_parent_page_id', 0 );

    if ( $parent_id && ( (int) $page->ID === $parent_id || (int) $page->post_parent === $parent_id ) ) {
        $allowed = get_post_meta( $page->ID, '_allowed_users', true );

        if ( ! empty( $allowed ) ) {
            $allowed = array_map( 'intval', (array) $allowed );

            if ( ! is_user_logged_in() || ! in_array( get_current_user_id(), $allowed, true ) ) {
                wp_safe_redirect( wp_login_url( get_permalink() ) );
                exit;
            }
        }
    }
}


// ===================================================================
//
// Oculta del menú las páginas restringidas por _allowed_users
// No mostrar páginas hijas/protegidas del sector restringido a
// usuarios que no estén autorizados.
//
// ===================================================================

add_filter( 'wp_nav_menu_objects', 'obras_filter_menu', 10, 2 );
function obras_filter_menu( $items, $args ) {
    $parent_id = (int) get_option( 'obras_parent_page_id', 0 );

    if ( ! $parent_id ) {
        return $items;
    }

    foreach ( $items as $key => $item ) {
        if ( ! isset( $item->object ) || 'page' !== $item->object ) {
            continue;
        }

        $object_id = isset( $item->object_id ) ? (int) $item->object_id : 0;
        if ( ! $object_id ) {
            continue;
        }

        $linked_page = get_post( $object_id );
        if ( ! $linked_page instanceof WP_Post ) {
            continue;
        }

        if ( $object_id === $parent_id || (int) $linked_page->post_parent === $parent_id ) {
            $allowed = get_post_meta( $object_id, '_allowed_users', true );

            if ( ! empty( $allowed ) ) {
                $allowed = array_map( 'intval', (array) $allowed );

                if ( ! is_user_logged_in() || ! in_array( get_current_user_id(), $allowed, true ) ) {
                    unset( $items[ $key ] );
                }
            }
        }
    }

    return $items;
}
