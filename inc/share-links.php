<?php
/**
 * Bitácora - Enlaces compartibles para contenido privado.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const BITACORA_SHARE_TOKEN_META_KEY = '_bitacora_share_token';

/**
 * Determina si el usuario actual puede gestionar el enlace compartible
 * de un bitacora_item privado.
 */
function bitacora_user_can_manage_share_link( $post_id ) {
    $post_id = absint( $post_id );

    if ( ! $post_id || ! is_user_logged_in() ) {
        return false;
    }

    $post = get_post( $post_id );

    if (
        ! $post instanceof WP_Post
        || 'bitacora_item' !== $post->post_type
        || 'private' !== $post->post_status
    ) {
        return false;
    }

    return current_user_can( 'edit_post', $post_id );
}

/**
 * Devuelve el token activo de un post, si existe y tiene formato válido.
 */
function bitacora_get_share_token( $post_id ) {
    $token = trim(
        (string) get_post_meta(
            absint( $post_id ),
            BITACORA_SHARE_TOKEN_META_KEY,
            true
        )
    );

    if ( ! preg_match( '/\A[a-f0-9]{64}\z/', $token ) ) {
        return '';
    }

    return $token;
}

/**
 * Construye la URL compartible para un token.
 */
function bitacora_get_share_url( $token ) {
    if ( ! preg_match( '/\A[a-f0-9]{64}\z/', $token ) ) {
        return '';
    }

    return add_query_arg(
        'bitacora_share',
        rawurlencode( $token ),
        home_url( '/' )
    );
}

/**
 * Localiza un único bitacora_item privado por token.
 *
 * La consulta evita deliberadamente las comprobaciones ordinarias de
 * capabilities: el token válido constituye la excepción explícita de lectura.
 */
function bitacora_find_shared_post_by_token( $token ) {
    if ( ! preg_match( '/\A[a-f0-9]{64}\z/', $token ) ) {
        return 0;
    }

    global $wpdb;

    $post_ids = $wpdb->get_col(
        $wpdb->prepare(
            "
            SELECT p.ID
            FROM {$wpdb->posts} AS p
            INNER JOIN {$wpdb->postmeta} AS pm
                ON pm.post_id = p.ID
            WHERE p.post_type = %s
              AND p.post_status = %s
              AND pm.meta_key = %s
              AND pm.meta_value = %s
            LIMIT 2
            ",
            'bitacora_item',
            'private',
            BITACORA_SHARE_TOKEN_META_KEY,
            $token
        )
    );

    if ( 1 !== count( $post_ids ) ) {
        return 0;
    }

    return absint( $post_ids[0] );
}

/**
 * Genera un token nuevo y comprueba defensivamente que no exista.
 */
function bitacora_generate_share_token() {
    for ( $attempt = 0; $attempt < 5; $attempt++ ) {
        $token = bin2hex( random_bytes( 32 ) );

        if ( ! bitacora_find_shared_post_by_token( $token ) ) {
            return $token;
        }
    }

    return new WP_Error(
        'bitacora_share_token_generation_failed',
        'No fue posible generar un enlace seguro.'
    );
}

/**
 * Verifica la solicitud autenticada de gestión de un enlace.
 */
function bitacora_verify_share_request() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error(
            array( 'message' => 'Acceso no autorizado.' ),
            403
        );
    }

    $post_id = isset( $_POST['post_id'] )
        ? absint( $_POST['post_id'] )
        : 0;

    if ( ! $post_id ) {
        wp_send_json_error(
            array( 'message' => 'Contenido inválido.' ),
            400
        );
    }

    $nonce = isset( $_POST['_wpnonce'] )
        ? sanitize_text_field(
            wp_unslash( $_POST['_wpnonce'] )
        )
        : '';

    if (
        ! $nonce
        || ! wp_verify_nonce(
            $nonce,
            'bitacora_share_' . $post_id
        )
    ) {
        wp_send_json_error(
            array( 'message' => 'Solicitud no válida.' ),
            403
        );
    }

    if ( ! bitacora_user_can_manage_share_link( $post_id ) ) {
        wp_send_json_error(
            array( 'message' => 'No tenés permiso para compartir este contenido.' ),
            403
        );
    }

    return $post_id;
}

/**
 * Crea el enlace si todavía no existe, o devuelve el ya existente.
 */
add_action(
    'admin_post_bitacora_create_share_link',
    'bitacora_handle_create_share_link'
);

function bitacora_handle_create_share_link() {
    $post_id = bitacora_verify_share_request();

    $token = bitacora_get_share_token( $post_id );

    if ( '' === $token ) {
        $token = bitacora_generate_share_token();

        if ( is_wp_error( $token ) ) {
            wp_send_json_error(
                array(
                    'message' => $token->get_error_message(),
                ),
                500
            );
        }

        if (
            false === update_post_meta(
                $post_id,
                BITACORA_SHARE_TOKEN_META_KEY,
                $token
            )
        ) {
            wp_send_json_error(
                array( 'message' => 'No fue posible guardar el enlace.' ),
                500
            );
        }
    }

    wp_send_json_success(
        array(
            'url' => bitacora_get_share_url( $token ),
        )
    );
}

/**
 * Revoca inmediatamente el enlace activo.
 */
add_action(
    'admin_post_bitacora_revoke_share_link',
    'bitacora_handle_revoke_share_link'
);

function bitacora_handle_revoke_share_link() {
    $post_id = bitacora_verify_share_request();

    delete_post_meta(
        $post_id,
        BITACORA_SHARE_TOKEN_META_KEY
    );

    wp_send_json_success();
}

/**
 * Si un ítem deja de ser privado, cualquier enlace compartible desaparece.
 *
 * Así un token antiguo no puede reactivarse accidentalmente si el contenido
 * vuelve a estado private más adelante.
 */
add_action(
    'transition_post_status',
    'bitacora_revoke_share_link_when_leaving_private',
    10,
    3
);

function bitacora_revoke_share_link_when_leaving_private(
    $new_status,
    $old_status,
    $post
) {
    if (
        ! $post instanceof WP_Post
        || 'bitacora_item' !== $post->post_type
        || 'private' !== $old_status
        || 'private' === $new_status
    ) {
        return;
    }

    delete_post_meta(
        $post->ID,
        BITACORA_SHARE_TOKEN_META_KEY
    );
}

/**
 * Renderiza los controles de compartir dentro del listado NDMCP.
 */
function bitacora_render_share_controls( $post_id ) {
    $post_id = absint( $post_id );

    if ( ! bitacora_user_can_manage_share_link( $post_id ) ) {
        return;
    }

    $token = bitacora_get_share_token( $post_id );
    $url   = $token
        ? bitacora_get_share_url( $token )
        : '';

    $script_path = get_theme_file_path(
        '/js/share-links.js'
    );

    wp_enqueue_script(
        'bitacora-share-links',
        get_theme_file_uri( '/js/share-links.js' ),
        array(),
        file_exists( $script_path )
            ? filemtime( $script_path )
            : null,
        true
    );

    $nonce = wp_create_nonce(
        'bitacora_share_' . $post_id
    );
    ?>
    <div
        class="bitacora-share-controls"
        data-endpoint="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
        data-post-id="<?php echo esc_attr( $post_id ); ?>"
        data-nonce="<?php echo esc_attr( $nonce ); ?>"
    >
        <div class="bitacora-share-heading">
            Crear enlace para compartir
        </div>

        <div class="bitacora-share-actions">
            <button
                type="button"
                class="bitacora-share-create"
                data-action="bitacora_create_share_link"
            >
                <?php echo $url ? 'Copiar enlace' : 'Compartir'; ?>
            </button>

            <button
                type="button"
                class="bitacora-share-revoke"
                data-action="bitacora_revoke_share_link"
                <?php disabled( '' === $url ); ?>
            >
                Eliminar
            </button>

        </div>

        <input
            type="text"
            class="bitacora-share-url"
            value="<?php echo esc_attr( $url ); ?>"
            readonly
            aria-label="Enlace compartible"
        >

        <div
            class="bitacora-share-status"
            role="status"
            aria-live="polite"
        ></div>
    </div>
    <?php
}

/**
 * Devuelve los datos físicos que forman parte del contenido compartido.
 */
function bitacora_get_shared_feature_html( $post_id ) {
    $section  = bitacora_get_item_section( $post_id );
    $location = '';
    $file_id  = 0;

    if (
        $section
        && bitacora_section_has_feature(
            $section,
            'location'
        )
    ) {
        $location = trim(
            (string) get_post_meta(
                $post_id,
                'bitacora_item_location',
                true
            )
        );
    }

    if (
        $section
        && bitacora_section_has_feature(
            $section,
            'file'
        )
    ) {
        $file_id = absint(
            get_post_meta(
                $post_id,
                'bitacora_item_file',
                true
            )
        );
    }

    $html = '';

    if ( '' !== $location ) {
        $html .= '<p><strong>📍 Ubicación:</strong> '
            . esc_html( $location )
            . '</p>';
    }

    if ( $file_id ) {
        $file_url = wp_get_attachment_url( $file_id );

        if ( $file_url ) {
            $file_label = trim(
                (string) get_the_title( $file_id )
            );

            if ( '' === $file_label ) {
                $file_label = basename( $file_url );
            }

            $html .= '<p><strong>📎 Archivo:</strong> '
                . '<a href="'
                . esc_url( $file_url )
                . '" rel="noreferrer noopener">'
                . esc_html( $file_label )
                . '</a></p>';
        }
    }

    if ( '' === $html ) {
        return '';
    }

    return '<div class="bitacora-shared-features">'
        . $html
        . '</div>';
}

/**
 * Emite una respuesta mínima para tokens inexistentes o revocados.
 */
function bitacora_render_invalid_share_link() {
    status_header( 404 );
    nocache_headers();

    header(
        'X-Robots-Tag: noindex, nofollow, noarchive',
        true
    );
    header(
        'Referrer-Policy: no-referrer',
        true
    );

    show_admin_bar( false );
    ?>
    <!doctype html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >
        <meta
            name="robots"
            content="noindex,nofollow,noarchive"
        >
        <title>Enlace no disponible</title>
        <?php wp_head(); ?>
        <style>
            body.bitacora-shared-view {
                margin: 0;
                background: #f6f7f7;
            }

            .bitacora-shared-document {
                box-sizing: border-box;
                width: min(920px, calc(100% - 32px));
                margin: 40px auto;
                padding: 28px;
                background: #fff;
            }
        </style>
    </head>
    <body class="bitacora-shared-view">
        <main class="bitacora-shared-document">
            <h1>Enlace no disponible</h1>
            <p>El enlace no es válido o fue eliminado.</p>
        </main>
        <?php wp_footer(); ?>
    </body>
    </html>
    <?php
}

/**
 * Emite la vista compartida deliberadamente mínima.
 */
function bitacora_render_shared_post( $post ) {
    nocache_headers();

    header(
        'X-Robots-Tag: noindex, nofollow, noarchive',
        true
    );
    header(
        'Referrer-Policy: no-referrer',
        true
    );

    show_admin_bar( false );

    $GLOBALS['post'] = $post;
    setup_postdata( $post );

    /*
     * La URL compartible usa la raíz del sitio:
     *
     *     /?bitacora_share=TOKEN
     *
     * WordPress conserva por ello el contexto is_front_page().
     * Para visitantes anónimos, el filtro de landing sustituiría
     * el contenido real por la pantalla de acceso.
     *
     * Se retira únicamente durante el procesamiento del contenido
     * compartido y se restaura inmediatamente después.
     */
    $landing_filter_priority = has_filter(
        'the_content',
        'obras_landing_content_filter'
    );

    if ( false !== $landing_filter_priority ) {
        remove_filter(
            'the_content',
            'obras_landing_content_filter',
            $landing_filter_priority
        );
    }

    $content = apply_filters(
        'the_content',
        $post->post_content
    );

    if ( false !== $landing_filter_priority ) {
        add_filter(
            'the_content',
            'obras_landing_content_filter',
            $landing_filter_priority
        );
    }

    $title = (string) $post->post_title;

    $thumbnail = get_the_post_thumbnail(
        $post->ID,
        'large'
    );

    $features = bitacora_get_shared_feature_html(
        $post->ID
    );
    ?>
    <!doctype html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >
        <meta
            name="robots"
            content="noindex,nofollow,noarchive"
        >
        <title><?php echo esc_html( $title ); ?></title>
        <?php wp_head(); ?>
        <style>
            body.bitacora-shared-view {
                margin: 0;
                background: #f6f7f7;
            }

            .bitacora-shared-document {
                box-sizing: border-box;
                width: min(920px, calc(100% - 32px));
                margin: 40px auto;
                padding: 28px;
                background: #fff;
            }

            .bitacora-shared-document img {
                max-width: 100%;
                height: auto;
            }

            .bitacora-shared-thumbnail {
                margin: 0 0 24px;
            }

            .bitacora-shared-features {
                margin-top: 24px;
            }
        </style>
    </head>
    <body class="bitacora-shared-view">
        <main class="bitacora-shared-document">
            <article>
                <h1><?php echo esc_html( $title ); ?></h1>

                <?php if ( $thumbnail ) : ?>
                    <div class="bitacora-shared-thumbnail">
                        <?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                <?php endif; ?>

                <div class="bitacora-shared-content">
                    <?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>

                <?php echo $features; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </article>
        </main>

        <?php wp_footer(); ?>
    </body>
    </html>
    <?php

    wp_reset_postdata();
}

/**
 * Resuelve /?bitacora_share=TOKEN antes de la restricción ordinaria de singles.
 */
add_action(
    'template_redirect',
    'bitacora_handle_shared_view',
    1
);

function bitacora_handle_shared_view() {
    if ( ! isset( $_GET['bitacora_share'] ) ) {
        return;
    }

    $token = strtolower(
        sanitize_text_field(
            wp_unslash(
                $_GET['bitacora_share']
            )
        )
    );

    if ( ! preg_match( '/\A[a-f0-9]{64}\z/', $token ) ) {
        bitacora_render_invalid_share_link();
        exit;
    }

    $post_id = bitacora_find_shared_post_by_token(
        $token
    );

    if ( ! $post_id ) {
        bitacora_render_invalid_share_link();
        exit;
    }

    $post = get_post( $post_id );

    if (
        ! $post instanceof WP_Post
        || 'bitacora_item' !== $post->post_type
        || 'private' !== $post->post_status
    ) {
        bitacora_render_invalid_share_link();
        exit;
    }

    status_header( 200 );

    bitacora_render_shared_post( $post );
    exit;
}
