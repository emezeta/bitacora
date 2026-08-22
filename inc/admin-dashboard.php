<?php
/*
 * Bitácora - Dashboard Admin Personalizado + Navegación auxiliar
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * URL única de Inicio = Dashboard frontend.
 */
if ( ! function_exists( 'obras_get_dashboard_url' ) ) {
    function obras_get_dashboard_url() {
        return home_url( '/' );
    }
}

/**
 * Devuelve contexto útil del ndmcp actual.
 */
if ( ! function_exists( 'obras_get_ndmcp_context' ) ) {
    function obras_get_ndmcp_context( $post = null ) {
        $post_id   = 0;
        $post_type = '';

        if ( is_numeric( $post ) ) {
            $post = get_post( (int) $post );
        }

        if ( $post instanceof WP_Post ) {
            $post_id   = (int) $post->ID;
            $post_type = $post->post_type;
        } else {
            if ( isset( $_GET['post'] ) ) {
                $post_id = absint( $_GET['post'] );
            } elseif ( isset( $_GET['post_ID'] ) ) {
                $post_id = absint( $_GET['post_ID'] );
            }

            if ( isset( $_GET['post_type'] ) ) {
                $post_type = sanitize_key( wp_unslash( $_GET['post_type'] ) );
            } elseif ( $post_id ) {
                $post_type = get_post_type( $post_id );
            }
        }

        return array(
            'post_id'   => $post_id,
            'post_type' => $post_type,
        );
    }
}

/**
 * URL contextual de la lista frontend.
 */
if ( ! function_exists( 'obras_get_list_url' ) ) {
    function obras_get_list_url( $post = null ) {
        $ctx = obras_get_ndmcp_context( $post );

        switch ( $ctx['post_type'] ) {
            case 'bitacora':
                return home_url( '/entradas/' );

            case 'documento_obra':
                return home_url( '/documentos/' );

            case 'material_obra':
                return home_url( '/materiales/' );

            case 'catalogo_obra':
                return home_url( '/catalogos/' );

            case 'plano_obra':
                return home_url( '/planos/' );

            case 'bitacora_item':
                if ( function_exists( 'bitacora_get_item_editor_section' ) ) {
                    $section = bitacora_get_item_editor_section(
                        $ctx['post_id']
                    );

                    if ( $section ) {
                        return home_url(
                            '/' . $section->slug . '/'
                        );
                    }
                }
                break;
        }

        return obras_get_dashboard_url();
    }
}

/**
 * Etiqueta contextual de la lista frontend.
 */
if ( ! function_exists( 'obras_get_list_label' ) ) {
    function obras_get_list_label( $post = null ) {
        $ctx = obras_get_ndmcp_context( $post );

        switch ( $ctx['post_type'] ) {
            case 'bitacora':
                return 'Notas';

            case 'documento_obra':
                return 'Documentos';

            case 'material_obra':
                return 'Materiales';

            case 'catalogo_obra':
                return 'Catálogos';

            case 'plano_obra':
                return 'Planos';

            case 'bitacora_item':
                if ( function_exists( 'bitacora_get_item_editor_section' ) ) {
                    $section = bitacora_get_item_editor_section(
                        $ctx['post_id']
                    );

                    if ( $section ) {
                        return bitacora_get_section_meta(
                            $section,
                            'bitacora_section_plural',
                            $section->name
                        );
                    }
                }
                break;
        }

        return 'Inicio';
    }
}

/**
 * Submenú admin interno.
 */
add_action( 'admin_menu', 'obras_add_dashboard_page' );
function obras_add_dashboard_page() {
    add_submenu_page(
        'edit.php?post_type=bitacora',
        'Panel Bitácora',
        'Panel Bitácora',
        'read',
        'bitacora-dashboard',
        'obras_render_dashboard'
    );
}

/**
 * Ocultar menú Bitácora en wp-admin para no-admin.
 */
add_action( 'admin_menu', 'obras_hide_bitacora_menu_for_non_admin', 999 );
function obras_hide_bitacora_menu_for_non_admin() {
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }

    remove_menu_page( 'edit.php?post_type=bitacora' );
}

/**
 * Panel interno wp-admin para admin.
 */
function obras_render_dashboard() {
    ?>
    <div class="wrap">
    <h1>Panel Bitácora</h1>
    <p>¡Hola, <?php echo esc_html( wp_get_current_user()->display_name ); ?>!</p>

    <div style="display:flex; gap:20px; flex-wrap:wrap; margin-top:30px;">
    <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=bitacora' ) ); ?>" class="button button-primary button-hero">✍️<br>Nueva Nota</a>
    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=bitacora' ) ); ?>" class="button button-secondary button-hero">📋<br>Notas</a>
    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=documento_obra' ) ); ?>" class="button button-secondary button-hero">📄<br>Documentos</a>
    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=material_obra' ) ); ?>" class="button button-secondary button-hero">🧰<br>Materiales</a>
    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=catalogo_obra' ) ); ?>" class="button button-secondary button-hero">📚<br>Catálogos</a>
    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=plano_obra' ) ); ?>" class="button button-secondary button-hero">📐<br>Planos</a>
    <a href="<?php echo esc_url( obras_get_dashboard_url() ); ?>" class="button button-secondary button-hero">🏠<br>Inicio</a>
    </div>
    </div>
    <?php
}

/**
 * Botones de navegación dentro del editor clásico.
 */
add_action( 'edit_form_after_title', 'obras_render_editor_navigation_buttons' );
function obras_render_editor_navigation_buttons( $post ) {
    if ( ! is_admin() ) {
        return;
    }

    if ( ! $post instanceof WP_Post ) {
        return;
    }

    $allowed_post_types = array(
        'bitacora',
        'documento_obra',
        'material_obra',
        'catalogo_obra',
        'plano_obra',
        'bitacora_item',
    );

    if ( ! in_array( $post->post_type, $allowed_post_types, true ) ) {
        return;
    }

    $list_url   = obras_get_list_url( $post );
    $list_label = obras_get_list_label( $post );
    $home_url   = obras_get_dashboard_url();
    ?>
    <div class="obras-single-actions obras-single-actions-aux">
    <a href="<?php echo esc_url( $list_url ); ?>" class="obras-aux-single-btn obras-aux-single-btn-primary">← <?php echo esc_html( $list_label ); ?></a>
    <a href="<?php echo esc_url( $home_url ); ?>" class="obras-aux-single-btn obras-aux-single-btn-secondary">🏠 Inicio</a>
    </div>
    <?php
}
