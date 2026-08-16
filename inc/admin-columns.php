<?php
/**
 * Bitácora de Obra - Columnas admin
 *
 * Agrega columna "Estado" en los listados wp-admin de los ndmcp.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CPTs del sistema Bitácora de Obra.
 */
function obras_admin_columns_post_types() {
    return array(
        'bitacora',
        'bitacora_item',
        'documento_obra',
        'material_obra',
        'catalogo_obra',
        'plano_obra',
    );
}

/**
 * Inserta la columna Estado después del título.
 */
function obras_admin_add_estado_column( $columns ) {
    $new_columns = array();

    foreach ( $columns as $key => $label ) {
        $new_columns[ $key ] = $label;

        if ( 'title' === $key ) {
            $new_columns['obras_estado'] = 'Estado';
        }
    }

    if ( ! isset( $new_columns['obras_estado'] ) ) {
        $new_columns['obras_estado'] = 'Estado';
    }

    return $new_columns;
}

/**
 * Renderiza el contenido de la columna Estado.
 */
function obras_admin_render_estado_column( $column, $post_id ) {
    if ( 'obras_estado' !== $column ) {
        return;
    }

    $status = get_post_status( $post_id );

    $labels = array(
        'publish' => 'Publicado',
        'draft'   => 'Borrador',
        'pending' => 'Pendiente de revisión',
        'private' => 'Privado',
        'future'  => 'Programado',
        'trash'   => 'Papelera',
        'auto-draft' => 'Borrador automático',
    );

    $label = isset( $labels[ $status ] ) ? $labels[ $status ] : $status;

    echo '<span class="obras-admin-estado obras-admin-estado--' . esc_attr( $status ) . '">';
    echo esc_html( $label );
    echo '</span>';
}

/**
 * CSS mínimo para que la columna sea legible.
 */
function obras_admin_estado_column_styles() {
    $screen = get_current_screen();

    if ( ! $screen || 'edit' !== $screen->base ) {
        return;
    }

    if ( ! in_array( $screen->post_type, obras_admin_columns_post_types(), true ) ) {
        return;
    }
    ?>
    <style>
        .column-obras_estado {
            width: 120px;
        }

        .obras-admin-estado {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 12px;
            line-height: 1.4;
            font-weight: 600;
            background: #f0f0f1;
            color: #1d2327;
            white-space: nowrap;
        }

        .obras-admin-estado--publish {
            background: #e7f5ea;
            color: #0a5f20;
        }

        .obras-admin-estado--draft,
        .obras-admin-estado--auto-draft {
            background: #f6f7f7;
            color: #50575e;
        }

        .obras-admin-estado--pending {
            background: #fff8e5;
            color: #7a4b00;
        }

        .obras-admin-estado--private {
            background: #eef2ff;
            color: #2b3a67;
        }

        .obras-admin-estado--future {
            background: #e5f5fa;
            color: #00506a;
        }

        .obras-admin-estado--trash {
            background: #fbeaea;
            color: #8a2424;
        }
    </style>
    <?php
}

/**
 * Registra hooks para cada CPT.
 */
foreach ( obras_admin_columns_post_types() as $post_type ) {
    add_filter( "manage_{$post_type}_posts_columns", 'obras_admin_add_estado_column' );
    add_action( "manage_{$post_type}_posts_custom_column", 'obras_admin_render_estado_column', 10, 2 );
}

add_action( 'admin_head-edit.php', 'obras_admin_estado_column_styles' );
