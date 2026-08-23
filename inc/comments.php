<?php
/**
 * Bitácora - Comentarios gobernados por feature de sección.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * CPT gestionado por la política de comentarios de Bitácora.
 */
if ( ! function_exists( 'obras_comments_managed_post_types' ) ) {
    function obras_comments_managed_post_types() {
        return array(
            'bitacora_item',
        );
    }
}


/**
 * Indica si un bitacora_item pertenece a una sección con comentarios.
 *
 * Durante la creación, cuando todavía no existe relación persistida,
 * puede utilizarse el contexto del editor.
 */
function bitacora_item_comments_feature_enabled(
    $post_id = 0,
    $allow_editor_context = false
) {
    $post_id = (int) $post_id;
    $section = false;

    if ( $post_id ) {
        $section = bitacora_get_item_section(
            $post_id
        );
    }

    if (
        ! $section
        && $allow_editor_context
        && function_exists( 'bitacora_get_item_editor_section' )
    ) {
        $section = bitacora_get_item_editor_section(
            $post_id
        );
    }

    if ( ! $section ) {
        return false;
    }

    return bitacora_section_has_feature(
        $section,
        'comments'
    );
}


/**
 * Soporte físico de WordPress.
 *
 * bitacora_item necesita soporte global para que WordPress disponga
 * de la infraestructura de comentarios. La política real se decide
 * después por feature de sección.
 */
add_action(
    'init',
    'obras_configure_comments_support',
    20
);

function obras_configure_comments_support() {


    // Modelo unificado.
    add_post_type_support(
        'bitacora_item',
        'comments'
    );

    remove_post_type_support(
        'bitacora_item',
        'trackbacks'
    );
}


/**
 * Política de comment_status / ping_status al guardar.
 */
add_filter(
    'wp_insert_post_data',
    'obras_default_comment_status_for_ndmcp',
    15,
    2
);

function obras_default_comment_status_for_ndmcp(
    $data,
    $postarr
) {
    if (
        empty( $data['post_type'] )
        || ! in_array(
            $data['post_type'],
            obras_comments_managed_post_types(),
            true
        )
    ) {
        return $data;
    }

    // Pingbacks / trackbacks siempre cerrados.
    $data['ping_status'] = 'closed';


    $post_id = isset( $postarr['ID'] )
        ? absint( $postarr['ID'] )
        : 0;

    $comments_enabled =
        bitacora_item_comments_feature_enabled(
            $post_id,
            true
        );

    if ( ! $comments_enabled ) {
        $data['comment_status'] = 'closed';
        return $data;
    }

    /*
     * En la creación de un ítem perteneciente a una sección con
     * comentarios, el default es abierto.
     *
     * En actualizaciones se respeta una elección explícita del usuario.
     */
    if (
        ! $post_id
        || empty( $data['comment_status'] )
    ) {
        $data['comment_status'] = 'open';
    }

    return $data;
}


/**
 * Política runtime de comments_open().
 */
add_filter(
    'comments_open',
    'obras_comments_open_policy',
    20,
    2
);

function obras_comments_open_policy(
    $open,
    $post_id
) {
    $post = get_post(
        $post_id
    );

    if ( ! $post instanceof WP_Post ) {
        return $open;
    }

    if (
        ! in_array(
            $post->post_type,
            obras_comments_managed_post_types(),
            true
        )
    ) {
        return $open;
    }

    if ( ! is_user_logged_in() ) {
        return false;
    }


    if (
        ! bitacora_item_comments_feature_enabled(
            $post->ID
        )
    ) {
        return false;
    }

    return (
        'open' === $post->comment_status
    );
}


add_filter(
    'pings_open',
    '__return_false',
    20
);


/**
 * Ajustes del metabox Discusión.
 *
 * - bitacora_item con feature comments: conservar Discusión;
 * - bitacora_item sin feature comments: ocultar la UI de comentarios.
 */
add_action(
    'add_meta_boxes',
    'obras_adjust_discussion_metaboxes',
    99,
    2
);

function obras_adjust_discussion_metaboxes(
    $post_type,
    $post
) {

    if ( 'bitacora_item' !== $post_type ) {
        return;
    }

    remove_meta_box(
        'trackbacksdiv',
        'bitacora_item',
        'normal'
    );

    remove_meta_box(
        'commentsdiv',
        'bitacora_item',
        'normal'
    );

    $post_id = (
        $post instanceof WP_Post
    )
        ? (int) $post->ID
        : 0;

    if (
        ! bitacora_item_comments_feature_enabled(
            $post_id,
            true
        )
    ) {
        remove_meta_box(
            'commentstatusdiv',
            'bitacora_item',
            'normal'
        );
    }
}


/**
 * Oculta la opción de pingbacks/trackbacks dentro de Discusión,
 * manteniendo visible "Permitir comentarios".
 */
add_action(
    'admin_head-post.php',
    'obras_hide_ping_status_ui'
);

add_action(
    'admin_head-post-new.php',
    'obras_hide_ping_status_ui'
);

function obras_hide_ping_status_ui() {

    if ( ! function_exists( 'get_current_screen' ) ) {
        return;
    }

    $screen = get_current_screen();

    if (
        ! $screen
        || ! in_array(
            $screen->post_type,
            obras_comments_managed_post_types(),
            true
        )
    ) {
        return;
    }

    if ( 'bitacora_item' === $screen->post_type ) {

        $post_id = isset( $_GET['post'] )
            ? absint( $_GET['post'] )
            : 0;

        if (
            ! bitacora_item_comments_feature_enabled(
                $post_id,
                true
            )
        ) {
            return;
        }
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

            if (
                text.includes('trackback')
                || text.includes('pingback')
            ) {
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
add_action(
    'admin_head-comment.php',
    'obras_hide_comment_quicktags_toolbar'
);

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
    function obras_get_comment_parent_depth(
        $comment_parent
    ) {
        $depth = 0;

        while ( $comment_parent ) {

            $parent = get_comment(
                $comment_parent
            );

            if ( ! $parent ) {
                break;
            }

            $depth++;
            $comment_parent =
                (int) $parent->comment_parent;
        }

        return $depth;
    }
}


/**
 * Valida comentarios dentro del sistema Bitácora.
 */
add_filter(
    'preprocess_comment',
    'obras_validate_bitacora_comments'
);

function obras_validate_bitacora_comments(
    $commentdata
) {
    $post_id = isset(
        $commentdata['comment_post_ID']
    )
        ? (int) $commentdata['comment_post_ID']
        : 0;

    $post = $post_id
        ? get_post( $post_id )
        : null;

    if ( ! $post instanceof WP_Post ) {
        return $commentdata;
    }

    if (
        ! in_array(
            $post->post_type,
            obras_comments_managed_post_types(),
            true
        )
    ) {
        return $commentdata;
    }

    if (
        'bitacora_item' === $post->post_type
        && ! bitacora_item_comments_feature_enabled(
            $post->ID
        )
    ) {
        wp_die(
            'Los comentarios no están habilitados en este contenido.'
        );
    }

    if ( ! is_user_logged_in() ) {
        wp_die(
            'Debes iniciar sesión para comentar.'
        );
    }

    $parent_id = isset(
        $commentdata['comment_parent']
    )
        ? (int) $commentdata['comment_parent']
        : 0;

    $resulting_depth = $parent_id
        ? obras_get_comment_parent_depth(
            $parent_id
        ) + 1
        : 1;

    // Máximo: raíz + dos niveles de respuesta.
    if ( $resulting_depth > 3 ) {
        wp_die(
            'La profundidad máxima de respuestas es 3. Probá hacer un nuevo comentario sin usar el botón "Responder"'
        );
    }

    return $commentdata;
}


/**
 * Carga comment-reply donde los comentarios están disponibles.
 */
add_action(
    'wp_enqueue_scripts',
    'obras_enqueue_comment_reply'
);

function obras_enqueue_comment_reply() {

    if (
        ! is_singular(
            obras_comments_managed_post_types()
        )
    ) {
        return;
    }

    $post_id = get_queried_object_id();
    $post    = get_post( $post_id );

    if ( ! $post instanceof WP_Post ) {
        return;
    }

    if (
        'bitacora_item' === $post->post_type
        && ! bitacora_item_comments_feature_enabled(
            $post->ID
        )
    ) {
        return;
    }

    if (
        comments_open( $post_id )
        && get_option( 'thread_comments' )
    ) {
        wp_enqueue_script(
            'comment-reply'
        );
    }
}


/**
 * Agrega el template de comentarios al singular correspondiente.
 */
add_filter(
    'the_content',
    'obras_append_comments_to_content',
    99
);

function obras_append_comments_to_content(
    $content
) {
    if (
        is_admin()
        || ! is_main_query()
        || ! is_singular(
            obras_comments_managed_post_types()
        )
    ) {
        return $content;
    }

    $post_id = get_queried_object_id();
    $post    = get_post( $post_id );

    if ( ! $post instanceof WP_Post ) {
        return $content;
    }

    if (
        'bitacora_item' === $post->post_type
        && ! bitacora_item_comments_feature_enabled(
            $post->ID
        )
    ) {
        return $content;
    }

    if ( post_password_required() ) {
        return $content;
    }

    ob_start();

    comments_template();

    $comments_html = trim(
        ob_get_clean()
    );

    if ( '' === $comments_html ) {
        return $content;
    }

    return $content . $comments_html;
}
