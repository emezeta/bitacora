<?php
/**
 * Bitácora - Comentarios en Notas.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'obras_comments_managed_post_types' ) ) {
    function obras_comments_managed_post_types() {
        return array(
            'bitacora',
            'bitacora_item',
        );
    }
}

add_action( 'init', 'obras_configure_comments_support', 20 );
function obras_configure_comments_support() {
    // Notas: comentarios sí, trackbacks no.
    add_post_type_support( 'bitacora', 'comments' );
    remove_post_type_support( 'bitacora', 'trackbacks' );

    // Resto de ndmcp: sin comentarios ni trackbacks.
    foreach ( array( 'bitacora_item' ) as $post_type ) {
        remove_post_type_support( $post_type, 'comments' );
        remove_post_type_support( $post_type, 'trackbacks' );
    }
}

add_filter( 'wp_insert_post_data', 'obras_default_comment_status_for_ndmcp', 15, 2 );
function obras_default_comment_status_for_ndmcp( $data, $postarr ) {
    if ( empty( $data['post_type'] ) || ! in_array( $data['post_type'], obras_comments_managed_post_types(), true ) ) {
        return $data;
    }

    // Pingbacks / trackbacks siempre cerrados.
    $data['ping_status'] = 'closed';

    if ( 'bitacora' === $data['post_type'] ) {
        // En Notas, si no viene definido, default = comentarios abiertos.
        if ( empty( $data['comment_status'] ) ) {
            $data['comment_status'] = 'open';
        }
    } else {
        $data['comment_status'] = 'closed';
    }

    return $data;
}

add_filter( 'comments_open', 'obras_comments_open_policy', 20, 2 );
function obras_comments_open_policy( $open, $post_id ) {
    $post = get_post( $post_id );

    if ( ! $post instanceof WP_Post ) {
        return $open;
    }

    if ( ! in_array( $post->post_type, obras_comments_managed_post_types(), true ) ) {
        return $open;
    }

    if ( 'bitacora' !== $post->post_type ) {
        return false;
    }

    if ( ! is_user_logged_in() ) {
        return false;
    }

    return ( 'open' === $post->comment_status );
}

add_filter( 'pings_open', '__return_false', 20 );

/**
 * Ajustes de metaboxes de discusión/comentarios en wp-admin.
 * - Notas: mantener sólo la caja Discusión (comentarios), sin trackbacks, y sin el metabox de comentarios.
 * - Resto de ndmcp: ocultar todo lo relativo a discusión/comentarios.
 */
add_action( 'add_meta_boxes', 'obras_adjust_discussion_metaboxes', 99 );
function obras_adjust_discussion_metaboxes() {
    // Notas: mantener Discusión, quitar trackbacks y el metabox de comentarios.
    remove_meta_box( 'trackbacksdiv', 'bitacora', 'normal' );
    remove_meta_box( 'commentsdiv', 'bitacora', 'normal' );

    // Resto de ndmcp: sin discusión/comentarios.
    foreach ( array( 'bitacora_item' ) as $post_type ) {
        remove_meta_box( 'commentstatusdiv', $post_type, 'normal' );
        remove_meta_box( 'trackbacksdiv', $post_type, 'normal' );
        remove_meta_box( 'commentsdiv', $post_type, 'normal' );
    }
}

/**
 * En Notas, ocultar sólo el checkbox de "Permitir trackbacks y pingbacks",
 * manteniendo visible "Permitir comentarios".
 */
add_action( 'admin_head-post.php', 'obras_hide_ping_status_ui_in_bitacora' );
add_action( 'admin_head-post-new.php', 'obras_hide_ping_status_ui_in_bitacora' );
function obras_hide_ping_status_ui_in_bitacora() {
    if ( ! function_exists( 'get_current_screen' ) ) {
        return;
    }

    $screen = get_current_screen();
    if ( ! $screen || 'bitacora' !== $screen->post_type ) {
        return;
    }
    ?>
    <style>
        #commentstatusdiv label[for="ping_status"],
        #commentstatusdiv input#ping_status {
            display: none !important;
        }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var pingInput = document.getElementById('ping_status');
        if (pingInput) {
            var label = pingInput.closest('label');
            if (label) {
                var next = label.nextElementSibling;
                label.remove();
                if (next && next.tagName === 'BR') {
                    next.remove();
                }
            } else {
                pingInput.remove();
            }
        }

        var box = document.getElementById('commentstatusdiv');
        if (!box) {
            return;
        }

        box.querySelectorAll('a').forEach(function (link) {
            var text = (link.textContent || '').toLowerCase();
            if (text.includes('trackback') || text.includes('pingback')) {
                var parent = link.closest('p, div, span');
                if (parent) {
                    parent.style.display = 'none';
                } else {
                    link.style.display = 'none';
                }
            }
        });
    });
    </script>
    <?php
}

/**
 * Ocultar la barra de etiquetas HTML en la edición de comentarios del admin.
 * Se mantiene el textarea, pero sin toolbar de quicktags.
 */
add_action( 'admin_head-comment.php', 'obras_hide_comment_quicktags_toolbar' );
function obras_hide_comment_quicktags_toolbar() {
    ?>
    <style>
        #qt_comment_toolbar,
        .comment-php .quicktags-toolbar {
            display: none !important;
        }
    </style>
    <?php
}

if ( ! function_exists( 'obras_get_comment_parent_depth' ) ) {
    function obras_get_comment_parent_depth( $comment_parent ) {
        $depth = 0;

        while ( $comment_parent ) {
            $parent = get_comment( $comment_parent );
            if ( ! $parent ) {
                break;
            }

            $depth++;
            $comment_parent = (int) $parent->comment_parent;
        }

        return $depth;
    }
}

add_filter( 'preprocess_comment', 'obras_validate_bitacora_comments' );
function obras_validate_bitacora_comments( $commentdata ) {
    $post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
    $post    = $post_id ? get_post( $post_id ) : null;

    if ( ! $post instanceof WP_Post ) {
        return $commentdata;
    }

    if ( ! in_array( $post->post_type, obras_comments_managed_post_types(), true ) ) {
        return $commentdata;
    }

    if ( 'bitacora' !== $post->post_type ) {
        wp_die( 'Los comentarios sólo están habilitados en Notas.' );
    }

    if ( ! is_user_logged_in() ) {
        wp_die( 'Debes iniciar sesión para comentar en una Nota.' );
    }

    $parent_id       = isset( $commentdata['comment_parent'] ) ? (int) $commentdata['comment_parent'] : 0;
    $resulting_depth = $parent_id ? obras_get_comment_parent_depth( $parent_id ) + 1 : 1;

    // Máximo permitido = 3 (1 = comentario raíz, 2 = respuesta, 3 = respuesta a respuesta).
    if ( $resulting_depth > 3 ) {
        wp_die( 'La profundidad máxima de respuestas es 3.' );
    }

    return $commentdata;
}

add_action( 'wp_enqueue_scripts', 'obras_enqueue_comment_reply_for_bitacora' );
function obras_enqueue_comment_reply_for_bitacora() {
    if ( is_singular( 'bitacora' ) && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}

add_filter( 'the_content', 'obras_append_comments_to_bitacora_content', 99 );
function obras_append_comments_to_bitacora_content( $content ) {
    if ( is_admin() || ! is_main_query() || ! is_singular( 'bitacora' ) ) {
        return $content;
    }

    if ( post_password_required() ) {
        return $content;
    }

    ob_start();
    comments_template();
    $comments_html = trim( ob_get_clean() );

    if ( '' === $comments_html ) {
        return $content;
    }

    return $content . $comments_html;
}
