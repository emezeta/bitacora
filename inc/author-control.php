<?php
/**
 * Bitácora de Obra - Control de autoría y fecha
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Post types pertenecientes al modelo de contenido 0.2.0.
 */
function bitacora_controlled_post_types() {
    return array(
        'bitacora',
        'bitacora_item',
    );
}


/**
 * Ocultar el selector nativo de Autor para usuarios que no administran
 * WordPress.
 *
 * El Administrator conserva el selector nativo y puede cambiar autoría.
 * Autor Bitácora y Supervisor no pueden cambiarla.
 */
add_action(
    'add_meta_boxes',
    'obras_hide_author_metabox_for_non_admin',
    99,
    2
);

function obras_hide_author_metabox_for_non_admin(
    $post_type,
    $post
) {
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }

    if (
        ! in_array(
            $post_type,
            bitacora_controlled_post_types(),
            true
        )
    ) {
        return;
    }

    remove_meta_box(
        'authordiv',
        $post_type,
        'normal'
    );

    remove_meta_box(
        'authordiv',
        $post_type,
        'side'
    );

    remove_meta_box(
        'authordiv',
        $post_type,
        'advanced'
    );
}


/**
 * Mostrar al Supervisor el autor real del contenido, sólo como dato.
 *
 * El Administrator ya dispone del control nativo de WordPress.
 * El Autor Bitácora no necesita ver este dato en el editor.
 */
add_action(
    'add_meta_boxes',
    'obras_add_supervisor_author_metabox',
    100,
    2
);

function obras_add_supervisor_author_metabox(
    $post_type,
    $post
) {
    if (
        ! in_array(
            $post_type,
            bitacora_controlled_post_types(),
            true
        )
    ) {
        return;
    }

    if (
        current_user_can( 'manage_options' )
        || ! current_user_can(
            'edit_others_bitacora_contents'
        )
    ) {
        return;
    }

    add_meta_box(
        'bitacora-author-readonly',
        'Autor',
        'obras_render_supervisor_author_metabox',
        $post_type,
        'side',
        'default'
    );
}


/**
 * Contenido del metabox de autor para Supervisor.
 */
function obras_render_supervisor_author_metabox( $post ) {
    $author_id = (
        $post instanceof WP_Post
        && (int) $post->post_author
    )
        ? (int) $post->post_author
        : get_current_user_id();

    $author = get_userdata( $author_id );

    if ( ! $author ) {
        echo '<p>—</p>';
        return;
    }

    echo '<p><strong>'
        . esc_html( $author->display_name )
        . '</strong></p>';
}


/**
 * Bloquear cambios de autoría para todos salvo Administrator.
 *
 * En contenido nuevo, el autor es el usuario que lo crea.
 * En contenido existente, se conserva siempre el autor original.
 */
add_filter(
    'wp_insert_post_data',
    'obras_preserve_post_author_for_non_admin',
    99,
    2
);

function obras_preserve_post_author_for_non_admin(
    $data,
    $postarr
) {
    if ( current_user_can( 'manage_options' ) ) {
        return $data;
    }

    if ( ! is_user_logged_in() ) {
        return $data;
    }

    if ( empty( $data['post_type'] ) ) {
        return $data;
    }

    if (
        ! in_array(
            $data['post_type'],
            bitacora_controlled_post_types(),
            true
        )
    ) {
        return $data;
    }

    $post_id = isset( $postarr['ID'] )
        ? absint( $postarr['ID'] )
        : 0;

    if ( $post_id ) {
        $existing_post = get_post( $post_id );

        if (
            $existing_post instanceof WP_Post
            && in_array(
                $existing_post->post_type,
                bitacora_controlled_post_types(),
                true
            )
        ) {
            $data['post_author'] =
                (int) $existing_post->post_author;

            return $data;
        }
    }

    $data['post_author'] = get_current_user_id();

    return $data;
}


/**
 * Bloquear cambios manuales de fecha para Autor Bitácora.
 *
 * Supervisor y Administrator pueden corregirla.
 *
 * En contenido existente se conserva la fecha real almacenada.
 * En contenido nuevo se utiliza la fecha actual del sistema.
 */
add_filter(
    'wp_insert_post_data',
    'obras_preserve_post_date_for_non_supervisor',
    100,
    2
);

function obras_preserve_post_date_for_non_supervisor(
    $data,
    $postarr
) {
    if ( ! is_user_logged_in() ) {
        return $data;
    }

    if ( empty( $data['post_type'] ) ) {
        return $data;
    }

    if (
        ! in_array(
            $data['post_type'],
            bitacora_controlled_post_types(),
            true
        )
    ) {
        return $data;
    }

    if (
        current_user_can(
            'edit_others_bitacora_contents'
        )
    ) {
        return $data;
    }

    $post_id = isset( $postarr['ID'] )
        ? absint( $postarr['ID'] )
        : 0;

    if ( $post_id ) {
        $existing_post = get_post( $post_id );

        if (
            $existing_post instanceof WP_Post
            && in_array(
                $existing_post->post_type,
                bitacora_controlled_post_types(),
                true
            )
        ) {
            $data['post_date'] =
                $existing_post->post_date;

            $data['post_date_gmt'] =
                $existing_post->post_date_gmt;

            return $data;
        }
    }

    $data['post_date'] =
        current_time( 'mysql' );

    $data['post_date_gmt'] =
        current_time( 'mysql', true );

    return $data;
}


/**
 * Autor Bitácora puede ver la fecha efectiva, pero no editarla.
 *
 * La protección verdadera está en wp_insert_post_data;
 * este CSS sólo simplifica la interfaz.
 */
add_action(
    'admin_head-post.php',
    'obras_lock_post_date_ui_for_author'
);

add_action(
    'admin_head-post-new.php',
    'obras_lock_post_date_ui_for_author'
);

function obras_lock_post_date_ui_for_author() {
    $screen = get_current_screen();

    if (
        ! $screen
        || ! in_array(
            $screen->post_type,
            bitacora_controlled_post_types(),
            true
        )
    ) {
        return;
    }

    if (
        current_user_can(
            'edit_others_bitacora_contents'
        )
    ) {
        return;
    }

    echo '<style>
        #misc-publishing-actions .edit-timestamp,
        #timestampdiv {
            display: none !important;
        }
    </style>';
}
