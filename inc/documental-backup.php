<?php
/**
 * Respaldo documental de la instancia Bitácora.
 *
 * Este módulo produce una representación documental de la instancia
 * actualmente materializada.
 *
 * No implementa restauración, importación de Profiles ni Templates.
 */

defined( 'ABSPATH' ) || exit;


/**
 * Devuelve todos los metadatos de un post preservando valores múltiples.
 */
function bitacora_documental_get_post_meta( $post_id ) {

    $meta = get_post_meta( $post_id );

    return is_array( $meta )
        ? $meta
        : array();
}


/**
 * Devuelve todos los metadatos de un comentario.
 */
function bitacora_documental_get_comment_meta( $comment_id ) {

    $meta = get_comment_meta( $comment_id );

    return is_array( $meta )
        ? $meta
        : array();
}


/**
 * Devuelve todos los metadatos de un término.
 */
function bitacora_documental_get_term_meta( $term_id ) {

    $meta = get_term_meta( $term_id );

    return is_array( $meta )
        ? $meta
        : array();
}


/**
 * Convierte un término materializado a una representación documental.
 */
function bitacora_documental_collect_term( $term ) {

    return array(
        'term_id'     => (int) $term->term_id,
        'taxonomy'    => (string) $term->taxonomy,
        'slug'        => (string) $term->slug,
        'name'        => (string) $term->name,
        'description' => (string) $term->description,
        'parent'      => (int) $term->parent,
        'count'       => (int) $term->count,
        'meta'        => bitacora_documental_get_term_meta(
            $term->term_id
        ),
    );
}


/**
 * Devuelve autor básico asociado a una entrada.
 *
 * Se conserva identidad documental suficiente sin exportar credenciales
 * ni información privada de la cuenta.
 */
function bitacora_documental_collect_author( $author_id ) {

    $author_id = absint( $author_id );

    if ( ! $author_id ) {
        return null;
    }

    $user = get_userdata( $author_id );

    if ( ! $user ) {
        return array(
            'id' => $author_id,
        );
    }

    return array(
        'id'           => $author_id,
        'display_name' => (string) $user->display_name,
    );
}


/**
 * Convierte un bitacora_item a representación documental.
 */
function bitacora_documental_collect_item( $post ) {

    $terms = array();

    foreach (
        array(
            'bitacora_section',
            'bitacora_class',
        ) as $taxonomy
    ) {
        $assigned = wp_get_object_terms(
            $post->ID,
            $taxonomy
        );

        if ( is_wp_error( $assigned ) ) {
            $terms[ $taxonomy ] = array();
            continue;
        }

        $terms[ $taxonomy ] = array_values(
            array_map(
                static function ( $term ) {
                    return array(
                        'term_id' => (int) $term->term_id,
                        'slug'    => (string) $term->slug,
                        'name'    => (string) $term->name,
                    );
                },
                $assigned
            )
        );
    }

    return array(
        'id'             => (int) $post->ID,
        'status'         => (string) $post->post_status,
        'author'         => bitacora_documental_collect_author(
            $post->post_author
        ),
        'date'           => (string) $post->post_date,
        'date_gmt'       => (string) $post->post_date_gmt,
        'modified'       => (string) $post->post_modified,
        'modified_gmt'   => (string) $post->post_modified_gmt,
        'slug'           => (string) $post->post_name,
        'title'          => (string) $post->post_title,
        'excerpt'        => (string) $post->post_excerpt,
        'content'        => (string) $post->post_content,
        'comment_status' => (string) $post->comment_status,
        'menu_order'     => (int) $post->menu_order,
        'taxonomies'     => $terms,
        'meta'           => bitacora_documental_get_post_meta(
            $post->ID
        ),
    );
}


/**
 * Convierte un comentario asociado a bitacora_item.
 */
function bitacora_documental_collect_comment( $comment ) {

    return array(
        'id'           => (int) $comment->comment_ID,
        'item_id'      => (int) $comment->comment_post_ID,
        'parent'       => (int) $comment->comment_parent,
        'author'       => (string) $comment->comment_author,
        'date'         => (string) $comment->comment_date,
        'date_gmt'     => (string) $comment->comment_date_gmt,
        'approved'     => (string) $comment->comment_approved,
        'type'         => (string) $comment->comment_type,
        'content'      => (string) $comment->comment_content,
        'user_id'      => (int) $comment->user_id,
        'meta'         => bitacora_documental_get_comment_meta(
            $comment->comment_ID
        ),
    );
}


/**
 * Convierte un attachment a representación documental.
 *
 * No exporta rutas absolutas del servidor. La ruta relativa dentro de
 * uploads es suficiente para documentación y para el empaquetador.
 */
function bitacora_documental_collect_attachment( $post ) {

    $attachment_id = (int) $post->ID;
    $absolute_file = get_attached_file( $attachment_id );
    $relative_file = '';

    $uploads = wp_upload_dir();

    if (
        is_string( $absolute_file )
        && '' !== $absolute_file
        && ! empty( $uploads['basedir'] )
    ) {
        $base = wp_normalize_path(
            trailingslashit( $uploads['basedir'] )
        );

        $file = wp_normalize_path(
            $absolute_file
        );

        if ( 0 === strpos( $file, $base ) ) {
            $relative_file = substr(
                $file,
                strlen( $base )
            );
        }
    }

    $exists = (
        is_string( $absolute_file )
        && '' !== $absolute_file
        && is_file( $absolute_file )
    );

    return array(
        'id'              => $attachment_id,
        'parent'          => (int) $post->post_parent,
        'status'          => (string) $post->post_status,
        'date'            => (string) $post->post_date,
        'date_gmt'        => (string) $post->post_date_gmt,
        'modified'        => (string) $post->post_modified,
        'modified_gmt'    => (string) $post->post_modified_gmt,
        'slug'            => (string) $post->post_name,
        'title'           => (string) $post->post_title,
        'excerpt'         => (string) $post->post_excerpt,
        'description'     => (string) $post->post_content,
        'mime_type'       => (string) $post->post_mime_type,
        'relative_file'   => (string) $relative_file,
        'file_exists'     => $exists,
        'file_size'       => $exists
            ? (int) filesize( $absolute_file )
            : null,
        'sha256'          => $exists
            ? hash_file( 'sha256', $absolute_file )
            : null,
        'attachment_meta' => wp_get_attachment_metadata(
            $attachment_id
        ),
        'meta'            => bitacora_documental_get_post_meta(
            $attachment_id
        ),
    );
}


/**
 * Devuelve las páginas de sección pertenecientes a la instancia materializada.
 *
 * Se utiliza la misma frontera que emplea el desmontaje de Bitácora.
 */
function bitacora_documental_collect_materialized_pages() {

    $all_post_statuses = array_values(
        get_post_stati( array(), 'names' )
    );

    $pages = get_posts(
        array(
            'post_type'      => 'page',
            'post_status'    => $all_post_statuses,
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        )
    );

    $result = array();

    foreach ( $pages as $page ) {

        if (
            ! preg_match(
                '/\[bitacora_section(?:\s|\])/i',
                (string) $page->post_content
            )
        ) {
            continue;
        }

        $result[] = array(
            'id'           => (int) $page->ID,
            'status'       => (string) $page->post_status,
            'date'         => (string) $page->post_date,
            'date_gmt'     => (string) $page->post_date_gmt,
            'modified'     => (string) $page->post_modified,
            'modified_gmt' => (string) $page->post_modified_gmt,
            'slug'         => (string) $page->post_name,
            'title'        => (string) $page->post_title,
            'content'      => (string) $page->post_content,
            'meta'         => bitacora_documental_get_post_meta(
                $page->ID
            ),
        );
    }

    return $result;
}


/**
 * Recolecta una fotografía documental completa de la instancia actual.
 *
 * El resultado es deliberadamente descriptivo.
 * No constituye un formato de restauración.
 *
 * @return array|WP_Error
 */
function bitacora_collect_documental_backup() {

    $profile_id = bitacora_get_configured_profile_id();

    if ( '' === $profile_id ) {
        return new WP_Error(
            'bitacora_documental_not_configured',
            'Bitácora no tiene una instancia en uso para respaldar.'
        );
    }

    $profile = bitacora_load_profile( $profile_id );

    if ( ! is_array( $profile ) ) {
        return new WP_Error(
            'bitacora_documental_profile_not_found',
            'No se pudo cargar el Profile de la instancia actual.'
        );
    }

    $configured_at = bitacora_get_configured_at();
    $generated_at  = time();

    $all_post_statuses = array_values(
        get_post_stati( array(), 'names' )
    );

    /*
     * Estructura materializada.
     */
    $sections = get_terms(
        array(
            'taxonomy'   => 'bitacora_section',
            'hide_empty' => false,
        )
    );

    if ( is_wp_error( $sections ) ) {
        return $sections;
    }

    $classes = get_terms(
        array(
            'taxonomy'   => 'bitacora_class',
            'hide_empty' => false,
        )
    );

    if ( is_wp_error( $classes ) ) {
        return $classes;
    }

    $sections = array_values(
        array_map(
            'bitacora_documental_collect_term',
            $sections
        )
    );

    $classes = array_values(
        array_map(
            'bitacora_documental_collect_term',
            $classes
        )
    );

    $pages = bitacora_documental_collect_materialized_pages();

    /*
     * Contenido producido.
     */
    $posts = get_posts(
        array(
            'post_type'      => 'bitacora_item',
            'post_status'    => $all_post_statuses,
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        )
    );

    $items = array_values(
        array_map(
            'bitacora_documental_collect_item',
            $posts
        )
    );

    $item_ids = array_values(
        array_map(
            static function ( $post ) {
                return (int) $post->ID;
            },
            $posts
        )
    );

    $comments = array();

    if ( ! empty( $item_ids ) ) {

        $comment_objects = get_comments(
            array(
                'post__in' => $item_ids,
                'status'   => 'all',
                'orderby'  => 'comment_ID',
                'order'    => 'ASC',
            )
        );

        $comments = array_values(
            array_map(
                'bitacora_documental_collect_comment',
                $comment_objects
            )
        );
    }

    /*
     * La frontera de medios coincide deliberadamente con CERRAR:
     * todos los attachments pertenecen a la única instancia materializada.
     */
    $attachment_posts = get_posts(
        array(
            'post_type'      => 'attachment',
            'post_status'    => $all_post_statuses,
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        )
    );

    $attachments = array_values(
        array_map(
            'bitacora_documental_collect_attachment',
            $attachment_posts
        )
    );

    $theme = wp_get_theme();

    return array(
        'manifest' => array(
            'format'         => 'bitacora-documental-backup',
            'format_version' => 1,
            'purpose'        => 'documental',
            'restorable'     => false,
            'importable'     => false,
            'notice'         =>
                'Este respaldo no genera un paquete reutilizable directamente por Bitácora.',
            'bitacora'       => array(
                'version' => (string) $theme->get( 'Version' ),
            ),
            'instance'       => array(
                'profile_id' => $profile_id,
                'configured_at' => array(
                    'timestamp' => $configured_at
                        ? (int) $configured_at
                        : null,
                    'utc' => $configured_at
                        ? gmdate( 'c', $configured_at )
                        : null,
                    'source' => $configured_at
                        ? 'bitacora_configured_at'
                        : 'unavailable',
                ),
                'backup_generated_at' => array(
                    'timestamp' => (int) $generated_at,
                    'utc'       => gmdate(
                        'c',
                        $generated_at
                    ),
                ),
            ),
            'counts' => array(
                'sections'    => count( $sections ),
                'classes'     => count( $classes ),
                'pages'       => count( $pages ),
                'items'       => count( $items ),
                'comments'    => count( $comments ),
                'attachments' => count( $attachments ),
            ),
        ),

        'profile' => array(
            'id'         => $profile_id,
            'label'      => isset( $profile['label'] )
                ? (string) $profile['label']
                : '',
            'definition' => $profile,
        ),

        'structure' => array(
            'sections' => $sections,
            'classes'  => $classes,
            'pages'    => $pages,
        ),

        'items'       => $items,
        'comments'    => $comments,
        'attachments' => $attachments,
    );
}
