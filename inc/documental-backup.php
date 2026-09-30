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


/**
 * Serializa una estructura del respaldo documental como JSON legible.
 *
 * @return string|WP_Error
 */
function bitacora_documental_encode_json( $value, $label ) {

    $json = wp_json_encode(
        $value,
        JSON_PRETTY_PRINT
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );

    if ( false === $json ) {
        return new WP_Error(
            'bitacora_documental_json_error',
            sprintf(
                'No se pudo serializar "%s" como JSON.',
                (string) $label
            )
        );
    }

    return $json . "\n";
}


/**
 * Produce el texto explicativo incluido en cada respaldo.
 */
function bitacora_documental_build_readme( $backup ) {

    $manifest = $backup['manifest'];
    $profile  = $backup['profile'];

    $configured_at = isset(
        $manifest['instance']['configured_at']['utc']
    )
        ? $manifest['instance']['configured_at']['utc']
        : null;

    $generated_at = isset(
        $manifest['instance']['backup_generated_at']['utc']
    )
        ? $manifest['instance']['backup_generated_at']['utc']
        : '';

    $counts = isset( $manifest['counts'] )
        && is_array( $manifest['counts'] )
        ? $manifest['counts']
        : array();

    $lines = array(
        'RESPALDO DOCUMENTAL BITÁCORA',
        '=============================',
        '',
        'Este paquete conserva una representación documental de una',
        'instancia de Bitácora para archivo, auditoría, documentación',
        'o análisis posterior.',
        '',
        'IMPORTANTE',
        '----------',
        'Este respaldo NO es una copia restaurable.',
        'NO es un Template.',
        'NO es una exportación de Profile.',
        'NO puede reutilizarse directamente para reconstruir una Bitácora.',
        '',
        'PROFILE',
        '-------',
        'Nombre: ' . (
            isset( $profile['label'] )
                ? $profile['label']
                : ''
        ),
        'UUID: ' . (
            isset( $profile['id'] )
                ? $profile['id']
                : ''
        ),
        '',
        'INSTANCIA',
        '---------',
        'Puesta en uso: ' . (
            $configured_at
                ? $configured_at
                : 'No disponible'
        ),
        'Respaldo generado: ' . $generated_at,
        '',
        'CONTENIDO',
        '---------',
        'Secciones: ' . (
            isset( $counts['sections'] )
                ? $counts['sections']
                : 0
        ),
        'Tipos: ' . (
            isset( $counts['classes'] )
                ? $counts['classes']
                : 0
        ),
        'Páginas materializadas: ' . (
            isset( $counts['pages'] )
                ? $counts['pages']
                : 0
        ),
        'Entradas: ' . (
            isset( $counts['items'] )
                ? $counts['items']
                : 0
        ),
        'Comentarios: ' . (
            isset( $counts['comments'] )
                ? $counts['comments']
                : 0
        ),
        'Archivos adjuntos: ' . (
            isset( $counts['attachments'] )
                ? $counts['attachments']
                : 0
        ),
        '',
        'ARCHIVOS DEL PAQUETE',
        '--------------------',
        'manifest.json     Metadatos generales del respaldo.',
        'perfil.json       Identidad y definición del Profile utilizado.',
        'estructura.json   Secciones, tipos y páginas materializadas.',
        'entradas.json     Contenido producido por la instancia.',
        'comentarios.json  Comentarios asociados a las entradas.',
        'adjuntos.json     Inventario, metadatos y SHA-256 de adjuntos.',
        'archivos/         Copias de los archivos físicos disponibles.',
        '',
    );

    return implode( "\n", $lines );
}


/**
 * Construye un nombre seguro y descriptivo para el ZIP.
 */
function bitacora_documental_build_filename( $backup ) {

    $label = isset( $backup['profile']['label'] )
        ? (string) $backup['profile']['label']
        : 'bitacora';

    $slug = sanitize_title( $label );

    if ( '' === $slug ) {
        $slug = 'bitacora';
    }

    $timestamp = isset(
        $backup['manifest']['instance']['backup_generated_at']['timestamp']
    )
        ? absint(
            $backup['manifest']['instance']['backup_generated_at']['timestamp']
        )
        : time();

    return sprintf(
        'bitacora-documental-%s-%s.zip',
        $slug,
        gmdate( 'Ymd-His', $timestamp )
    );
}


/**
 * Valida y devuelve la ruta interna del ZIP para un attachment.
 *
 * La ruta física nunca se expone en el paquete.
 */
function bitacora_documental_get_archive_file_path( $attachment ) {

    $relative_file = isset( $attachment['relative_file'] )
        ? wp_normalize_path(
            (string) $attachment['relative_file']
        )
        : '';

    $relative_file = ltrim(
        $relative_file,
        '/'
    );

    if (
        '' === $relative_file
        || false !== strpos( $relative_file, '../' )
        || '..' === $relative_file
    ) {
        return '';
    }

    return 'archivos/' . $relative_file;
}


/**
 * Obtiene nuevamente la ruta física de un attachment al empaquetar.
 *
 * El colector no conserva rutas absolutas del servidor.
 */
function bitacora_documental_get_attachment_source( $attachment ) {

    $attachment_id = isset( $attachment['id'] )
        ? absint( $attachment['id'] )
        : 0;

    if ( ! $attachment_id ) {
        return '';
    }

    $file = get_attached_file( $attachment_id );

    if (
        ! is_string( $file )
        || '' === $file
        || ! is_file( $file )
        || ! is_readable( $file )
    ) {
        return '';
    }

    return $file;
}


/**
 * Empaqueta el respaldo documental actual en un ZIP temporal.
 *
 * El llamador es responsable de entregar y posteriormente eliminar
 * el archivo temporal.
 *
 * @return array|WP_Error
 */
function bitacora_build_documental_backup() {

    if ( ! class_exists( 'ZipArchive' ) ) {
        return new WP_Error(
            'bitacora_documental_zip_unavailable',
            'La extensión ZipArchive no está disponible en el servidor.'
        );
    }

    $backup = bitacora_collect_documental_backup();

    if ( is_wp_error( $backup ) ) {
        return $backup;
    }

    $filename = bitacora_documental_build_filename(
        $backup
    );

    $temp_file = wp_tempnam(
        $filename
    );

    if (
        ! is_string( $temp_file )
        || '' === $temp_file
    ) {
        return new WP_Error(
            'bitacora_documental_tempfile_error',
            'No se pudo crear el archivo temporal del respaldo.'
        );
    }

    $zip = new ZipArchive();

    $opened = $zip->open(
        $temp_file,
        ZipArchive::CREATE
        | ZipArchive::OVERWRITE
    );

    if ( true !== $opened ) {

        @unlink( $temp_file );

        return new WP_Error(
            'bitacora_documental_zip_open_error',
            'No se pudo inicializar el paquete ZIP.'
        );
    }

    $json_files = array(
        'manifest.json' => $backup['manifest'],
        'perfil.json' => $backup['profile'],
        'estructura.json' => $backup['structure'],
        'entradas.json' => $backup['items'],
        'comentarios.json' => $backup['comments'],
        'adjuntos.json' => $backup['attachments'],
    );

    $errors   = array();
    $warnings = array();

    if (
        ! $zip->addFromString(
            'LEEME.txt',
            bitacora_documental_build_readme(
                $backup
            )
        )
    ) {
        $errors[] =
            'No se pudo agregar LEEME.txt al paquete.';
    }

    foreach ( $json_files as $name => $data ) {

        $json = bitacora_documental_encode_json(
            $data,
            $name
        );

        if ( is_wp_error( $json ) ) {
            $errors[] = $json->get_error_message();
            continue;
        }

        if ( ! $zip->addFromString( $name, $json ) ) {
            $errors[] = sprintf(
                'No se pudo agregar %s al paquete.',
                $name
            );
        }
    }

    foreach ( $backup['attachments'] as $attachment ) {

        $attachment_id = isset( $attachment['id'] )
            ? absint( $attachment['id'] )
            : 0;

        $archive_path =
            bitacora_documental_get_archive_file_path(
                $attachment
            );

        $source =
            bitacora_documental_get_attachment_source(
                $attachment
            );

        if (
            '' === $archive_path
            || '' === $source
        ) {
            $warnings[] = sprintf(
                'Attachment %d sin archivo físico disponible.',
                $attachment_id
            );

            continue;
        }

        if (
            ! $zip->addFile(
                $source,
                $archive_path
            )
        ) {
            $errors[] = sprintf(
                'No se pudo agregar el attachment %d al paquete.',
                $attachment_id
            );
        }
    }

    if ( ! empty( $warnings ) ) {

        $warning_text =
            "ADVERTENCIAS DEL RESPALDO\n"
            . "========================\n\n"
            . implode( "\n", $warnings )
            . "\n";

        if (
            ! $zip->addFromString(
                'ADVERTENCIAS.txt',
                $warning_text
            )
        ) {
            $errors[] =
                'No se pudo agregar ADVERTENCIAS.txt al paquete.';
        }
    }

    $closed = $zip->close();

    if (
        ! $closed
        || ! empty( $errors )
    ) {

        @unlink( $temp_file );

        return new WP_Error(
            'bitacora_documental_package_error',
            'No se pudo construir completamente el respaldo documental.',
            array(
                'errors'   => $errors,
                'warnings' => $warnings,
            )
        );
    }

    if (
        ! is_file( $temp_file )
        || ! is_readable( $temp_file )
    ) {
        @unlink( $temp_file );

        return new WP_Error(
            'bitacora_documental_package_missing',
            'El paquete fue generado pero no quedó disponible para lectura.'
        );
    }

    return array(
        'path'      => $temp_file,
        'filename'  => $filename,
        'size'      => (int) filesize( $temp_file ),
        'sha256'    => hash_file(
            'sha256',
            $temp_file
        ),
        'warnings'  => $warnings,
        'manifest'  => $backup['manifest'],
    );
}
