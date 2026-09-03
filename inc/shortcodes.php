<?php
/**
 * Bitácora - Shortcodes Frontend.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


// ============================================================================
// === ACCIONES FRONTEND SOBRE POSTS ==========================================
// ============================================================================

add_action( 'admin_post_obras_trash_post', 'obras_handle_frontend_trash_post' );
function obras_handle_frontend_trash_post() {
    if ( ! is_user_logged_in() ) {
        wp_die( 'Acceso no autorizado.' );
    }

    $post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
    if ( ! $post_id ) {
        wp_die( 'Post inválido.' );
    }

    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'obras_trash_post_' . $post_id ) ) {
        wp_die( 'Nonce inválido.' );
    }

    $post = get_post( $post_id );
    if ( ! $post ) {
        wp_die( 'Post no encontrado.' );
    }

    $allowed_post_types = array(
        'bitacora_item',
    );

    if ( ! in_array( $post->post_type, $allowed_post_types, true ) ) {
        wp_die( 'Tipo de contenido no permitido.' );
    }

    if ( ! current_user_can( 'delete_post', $post_id ) ) {
        wp_die( 'No tienes permiso para mover este contenido a la papelera.' );
    }

    wp_trash_post( $post_id );

    $redirect = wp_get_referer();
    if ( ! $redirect ) {
        $redirect = home_url( '/' );
    }

    wp_safe_redirect( $redirect );
    exit;
}


if ( ! function_exists( 'obras_user_can_manage_list_item' ) ) {
    function obras_user_can_manage_list_item( $post_id ) {
        $post_id = (int) $post_id;

        if ( ! $post_id || ! is_user_logged_in() ) {
            return false;
        }

        return current_user_can( 'edit_post', $post_id );
    }
}


// ============================================================================
// === SHORTCODES FRONTEND ====================================================
// ============================================================================

/**
 * Barra simple de acciones para listados frontend.
 */
function obras_render_lista_actions( $new_url, $new_label ) {
    ?>
    <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:center; margin:0 0 25px;">
    <a href="<?php echo esc_url( $new_url ); ?>"
    style="display:inline-block; padding:12px 20px; background:#2271b1; color:#fff; text-decoration:none; border-radius:8px; font-weight:600;">
    <?php echo esc_html( $new_label ); ?>
    </a>

    <a href="<?php echo esc_url( home_url( '/' ) ); ?>"
    style="display:inline-block; padding:12px 20px; background:#6c757d; color:#fff; text-decoration:none; border-radius:8px; font-weight:600;">
    Volver al inicio
    </a>
    </div>
    <?php
}


if ( ! function_exists( 'obras_get_frontend_search_query' ) ) {
    function obras_get_frontend_search_query( $param = 'q' ) {
        if ( empty( $_GET[ $param ] ) ) {
            return '';
        }

        return sanitize_text_field( wp_unslash( $_GET[ $param ] ) );
    }
}


/**
 * Renderiza acciones por item para autor o supervisor.
 */
function obras_render_item_actions( $post_id ) {
    $post_id = (int) $post_id;

    if ( ! $post_id ) {
        return;
    }

    $can_edit   = current_user_can( 'edit_post', $post_id );
    $can_delete = current_user_can( 'delete_post', $post_id );

    if ( ! $can_edit && ! $can_delete ) {
        return;
    }

    $edit_url = $can_edit
        ? get_edit_post_link( $post_id )
        : '';

    $trash_url = $can_delete
        ? wp_nonce_url(
            admin_url( 'admin-post.php?action=obras_trash_post&post_id=' . $post_id ),
            'obras_trash_post_' . $post_id
        )
        : '';
    ?>
    <div style="margin-top:10px; display:flex; gap:12px; flex-wrap:wrap;">
    <?php if ( $edit_url ) : ?>
    <a href="<?php echo esc_url( $edit_url ); ?>"
    style="font-size:0.9em; color:#2271b1; text-decoration:none;">
    ✏ Editar
    </a>
    <?php endif; ?>

    <?php if ( $trash_url ) : ?>
    <a href="<?php echo esc_url( $trash_url ); ?>"
    onclick="return confirm('¿Seguro que quieres mover este contenido a la papelera?');"
    style="font-size:0.9em; color:#d63638; text-decoration:none;">
    🗑 Mover a papelera
    </a>
    <?php endif; ?>
    </div>
    <?php
}


function obras_get_post_creation_date_label( $post_id ) {
    $post_id = (int) $post_id;
    if ( ! $post_id ) {
        return '';
    }

    $date_format = get_option( 'date_format' );
    if ( ! $date_format ) {
        $date_format = 'd/m/Y';
    }

    return get_the_date( $date_format, $post_id );
}

function obras_render_post_meta_line( $post_id ) {
    $post_id = (int) $post_id;

    if ( ! $post_id ) {
        return;
    }

    $fecha = obras_get_post_creation_date_label( $post_id );

    /*
     * El autor forma parte de la información de trabajo del contenido.
     * Debe ser visible para cualquier usuario que pueda ver el ítem.
     */
    $autor = get_the_author_meta(
        'display_name',
        (int) get_post_field(
            'post_author',
            $post_id
        )
    );

    echo '<div class="meta">';

    if ( '' !== $fecha ) {
        echo '<span class="fecha">📅 '
            . esc_html( $fecha )
            . '</span>';
    }

    if ( '' !== $autor ) {
        echo '<span class="author">✍ '
            . esc_html( $autor )
            . '</span>';
    }

    echo '</div>';
}


function obras_current_user_can_manage_list_item( $post_id ) {
    return obras_user_can_manage_list_item( $post_id );
}

function obras_get_post_status_label( $post_id ) {
    $status = get_post_status( $post_id );

    switch ( $status ) {
        case 'publish':
            return 'Publicado';
        case 'draft':
            return 'Borrador';
        case 'private':
            return 'Privado';
    }

    if ( is_string( $status ) && '' !== $status ) {
        return ucfirst( $status );
    }

    return '';
}

function obras_render_post_status_badge( $post_id ) {
    $post_id = (int) $post_id;
    if ( ! $post_id || ! obras_current_user_can_manage_list_item( $post_id ) ) {
        return;
    }

    $label = obras_get_post_status_label( $post_id );
    if ( '' === $label ) {
        return;
    }

    if ( 'Publicado' === $label ) {
        return;
    }

    echo '<span class="tipo tipo-estado">' . esc_html( $label ) . '</span>';
}


function obras_get_list_item_url( $post_id ) {
    $post_id = (int) $post_id;
    if ( ! $post_id ) {
        return '';
    }

    $status = get_post_status( $post_id );

    if ( 'publish' !== $status ) {
        $edit_url = get_edit_post_link( $post_id );
        if ( $edit_url ) {
            return $edit_url;
        }
    }

    return get_permalink( $post_id );
}

function obras_render_list_item_title( $post_id, $fallback_title ) {
    $post_id = (int) $post_id;
    if ( ! $post_id ) {
        return;
    }

    $title = get_the_title( $post_id );
    if ( '' === $title ) {
        $title = $fallback_title;
    }

    $url = obras_get_list_item_url( $post_id );

    echo '<h3>';
    if ( $url ) {
        echo '<a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>';
    } else {
        echo esc_html( $title );
    }
    echo '</h3>';
}


// [obras_dashboard]
add_shortcode( 'obras_dashboard', 'obras_render_dashboard_frontend' );
function obras_render_dashboard_frontend() {

    if ( ! is_user_logged_in() ) {
        return '<p>Debes <a href="' . wp_login_url( get_permalink() ) . '">iniciar sesión</a> para ver el contenido.</p>';
    }

    $user = wp_get_current_user();
    $core = bitacora_get_core_section();

    if ( ! $core instanceof WP_Term ) {
        return '<p>No existe una sección principal válida.</p>';
    }

    $core_list_url = home_url(
        '/' . $core->slug . '/'
    );

    $core_new_url = add_query_arg(
        array(
            'post_type'        => 'bitacora_item',
            'bitacora_section' => $core->slug,
        ),
        admin_url( 'post-new.php' )
    );

    $core_plural = bitacora_get_section_meta(
        $core,
        'bitacora_section_plural',
        $core->name
    );

    $core_new_label = bitacora_get_section_meta(
        $core,
        'bitacora_section_new_label',
        'Nuevo contenido'
    );

    ob_start();
    ?>
    <div class="obras-dashboard">
    <h1>Bitácora</h1>
    <p class="welcome">¡Hola, <?php echo esc_html( $user->display_name ); ?>!</p>
    <p>¿Qué querés hacer hoy?</p>

    <div class="obras-buttons">
    <a href="<?php echo esc_url( $core_new_url ); ?>" class="obras-button">
    <span class="icon">✍</span>
    <?php echo esc_html( $core_new_label ); ?>
    </a>

    <a href="<?php echo esc_url( $core_list_url ); ?>" class="obras-button secondary">
    <span class="icon">📋</span>
    <?php echo esc_html( $core_plural ); ?>
    </a>

    <a href="<?php echo esc_url( home_url( '/documentos/' ) ); ?>" class="obras-button secondary">
    <span class="icon">📄</span>
    Documentos
    </a>

    <a href="<?php echo esc_url( home_url( '/materiales/' ) ); ?>" class="obras-button secondary">
    <span class="icon">🧰</span>
    Materiales
    </a>

    <a href="<?php echo esc_url( home_url( '/catalogos/' ) ); ?>" class="obras-button secondary">
    <span class="icon">📚</span>
    Catálogos
    </a>

    <a href="<?php echo esc_url( home_url( '/planos/' ) ); ?>" class="obras-button secondary">
    <span class="icon">📐</span>
    Planos
    </a>
    </div>

    <div class="obras-dashboard-more">
    <a href="<?php echo esc_url( home_url( '/auxiliar/' ) ); ?>" class="obras-dashboard-more-link">Más secciones…</a>

    <?php if ( current_user_can( 'manage_bitacora_profiles' ) ) : ?>
    <a href="<?php echo esc_url( bitacora_get_profiles_admin_url() ); ?>" class="obras-dashboard-more-link">Perfiles disponibles</a>
    <?php endif; ?>
    </div>
    </div>
    <?php

    return ob_get_clean();
}


// [obras_menu_logout]
add_shortcode( 'obras_menu_logout', 'obras_render_menu_logout' );
function obras_render_menu_logout() {
    if ( ! is_user_logged_in() ) {
        return '<a href="' . wp_login_url( get_permalink() ) . '" class="obras-login-link">Iniciar sesión</a>';
    }

    $user = wp_get_current_user();

    ob_start();
    ?>
    <div class="obras-user-menu">
    <div class="obras-user-info" onclick="document.querySelector('.obras-dropdown').classList.toggle('show')">
    👤 <?php echo esc_html( $user->display_name ); ?>
    <span style="font-size: 0.8em;">▼</span>
    </div>

    <div class="obras-dropdown">
    <a href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>">Mi Perfil</a>
    <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="logout">Cerrar sesión</a>
    </div>
    </div>

    <script>
    document.addEventListener('click', function(event) {
        var menu = document.querySelector('.obras-user-menu');
        var dropdown = document.querySelector('.obras-dropdown');
        if (!menu.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });
    </script>
    <?php
    return ob_get_clean();
}


// Barra de Menu de Usuario "Mi Perfil/Cerrar"
add_action( 'wp_footer', 'obras_add_logout_menu_to_frontend' );
function obras_add_logout_menu_to_frontend() {
    if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) {
        echo do_shortcode( '[obras_menu_logout]' );
    }
}


// Shortcode inteligente para Google Sheets
function sc_google_sheet_limpio( $atts ) {
    $a = shortcode_atts(
        array(
            'url'  => '',
            'alto' => '600',
        ),
        $atts
    );

    if ( empty( $a['url'] ) ) {
        return '';
    }

    $url_base = esc_url_raw( $a['url'] );

    $parametros_limpieza = array(
        'headers' => 'false',
        'chrome'  => 'false',
        'widget'  => 'false',
        'single'  => 'true',
    );

    $url_final = add_query_arg( $parametros_limpieza, $url_base );

    return '<iframe src="' . esc_url( $url_final ) . '" width="100%" height="' . esc_attr( $a['alto'] ) . '" frameborder="0" style="border:0;"></iframe>';
}
add_shortcode( 'google_sheet', 'sc_google_sheet_limpio' );
