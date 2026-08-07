<?php
/**
 * Bitácora de Obra - Sección auxiliar comodín
 *
 * CPT físico único:
 * - aux_section
 *
 * Uso previsto:
 * - secciones lógicas internas mediante aux_group
 * - ocultamiento/archivo lógico mediante aux_archivado
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ================================================================
 * Helpers de configuración
 * ================================================================
 */

function obras_aux_get_sections() {
    $sections = get_option( 'obras_aux_sections', array() );

    if ( ! is_array( $sections ) || empty( $sections ) ) {
        $sections = array(
            'general' => array(
                'singular'  => 'Idea',
                'plural'    => 'Ideas',
                'slug'      => 'paisajismo',
                'title'     => 'Paisajismo',
                'subtitle'  => 'Lluvia de ideas',
                'new_label' => 'Nueva idea',
                'visible'   => 1,
                'archivada' => 0,
            ),
        );
    }

    return $sections;
}

function obras_aux_get_default_group() {
    $sections = obras_aux_get_sections();

    foreach ( $sections as $key => $section ) {
        if ( ! empty( $section['visible'] ) && empty( $section['archivada'] ) ) {
            return sanitize_key( $key );
        }
    }

    return 'general';
}

function obras_aux_get_section( $group = '' ) {
    $group    = $group ? sanitize_key( $group ) : obras_aux_get_default_group();
    $sections = obras_aux_get_sections();

    if ( isset( $sections[ $group ] ) && is_array( $sections[ $group ] ) ) {
        return $sections[ $group ];
    }

    return null;
}

function obras_aux_get_section_label( $group = '', $plural = true ) {
    $section = obras_aux_get_section( $group );

    if ( ! $section ) {
        return $plural ? 'Auxiliares' : 'Auxiliar';
    }

    if ( $plural ) {
        return ! empty( $section['plural'] ) ? $section['plural'] : 'Auxiliares';
    }

    return ! empty( $section['singular'] ) ? $section['singular'] : 'Auxiliar';
}


function obras_aux_get_section_title( $group = '' ) {
    $section = obras_aux_get_section( $group );

    if ( ! $section ) {
        return obras_aux_get_section_label( $group, true );
    }

    return ! empty( $section['title'] )
        ? $section['title']
        : obras_aux_get_section_label( $group, true );
}

function obras_aux_get_new_label( $group = '' ) {
    $section = obras_aux_get_section( $group );

    if ( $section && ! empty( $section['new_label'] ) ) {
        return $section['new_label'];
    }

    return 'Nuevo ' . obras_aux_get_section_label( $group, false );
}

function obras_aux_get_section_slug( $group = '' ) {
    $section = obras_aux_get_section( $group );

    if ( ! $section || empty( $section['slug'] ) ) {
        return 'auxiliar';
    }

    return sanitize_title( $section['slug'] );
}

function obras_aux_get_section_choices() {
    $sections = obras_aux_get_sections();
    $choices  = array();

    foreach ( $sections as $key => $section ) {
        $key = sanitize_key( $key );

        if ( empty( $key ) ) {
            continue;
        }

        $choices[ $key ] = ! empty( $section['plural'] )
            ? $section['plural']
            : ucfirst( str_replace( '_', ' ', $key ) );
    }

    if ( empty( $choices ) ) {
        $choices['general'] = 'Auxiliares';
    }

    return $choices;
}

function obras_aux_user_can_manage_post( $post_id ) {
    $post = get_post( $post_id );

    if ( ! $post ) {
        return false;
    }

    if ( current_user_can( 'manage_options' ) ) {
        return true;
    }

    return (int) $post->post_author === get_current_user_id();
}

function obras_aux_get_class_label( $post_id ) {
    $class = get_post_meta( $post_id, 'clase_auxiliar', true );

    if ( empty( $class ) ) {
        return 'Sin clasificar';
    }

    $labels = array(
        'observacion'  => 'Observación',
        'novedad'      => 'Novedad',
        'explicacion'  => 'Explicación',
        'referencia'   => 'Referencia',
        'dato_tecnico' => 'Dato técnico',
    );

    return isset( $labels[ $class ] ) ? $labels[ $class ] : esc_html( $class );
}

function obras_aux_get_attachment_url( $post_id ) {
    $file = get_post_meta( $post_id, 'archivo_auxiliar', true );

    if ( empty( $file ) ) {
        return '';
    }

    if ( is_numeric( $file ) ) {
        return wp_get_attachment_url( (int) $file );
    }

    if ( is_array( $file ) && ! empty( $file['url'] ) ) {
        return esc_url_raw( $file['url'] );
    }

    if ( is_string( $file ) ) {
        return esc_url_raw( $file );
    }

    return '';
}

/**
 * ================================================================
 * CPT aux_section
 * ================================================================
 */

add_action( 'init', 'obras_aux_register_cpt' );

function obras_aux_register_cpt() {
    $singular = obras_aux_get_section_label( '', false );
    $plural   = obras_aux_get_section_label( '', true );

    $labels = array(
        'name'                  => $plural,
        'singular_name'         => $singular,
        'menu_name'             => $plural,
        'name_admin_bar'        => $singular,
        'add_new'               => 'Agregar nuevo',
        'add_new_item'          => 'Agregar ' . $singular,
        'new_item'              => 'Nuevo ' . $singular,
        'edit_item'             => 'Editar ' . $singular,
        'view_item'             => 'Ver ' . $singular,
        'all_items'             => 'Todos',
        'search_items'          => 'Buscar',
        'not_found'             => 'No se encontraron elementos.',
        'not_found_in_trash'    => 'No hay elementos en la papelera.',
        'featured_image'        => 'Imagen destacada',
        'set_featured_image'    => 'Definir imagen destacada',
        'remove_featured_image' => 'Quitar imagen destacada',
    );

    register_post_type(
        'aux_section',
        array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'exclude_from_search' => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => false,
            'has_archive'         => false,
            'rewrite'             => array(
                'slug'       => 'auxiliar',
                'with_front' => false,
            ),
            'menu_icon'           => 'dashicons-portfolio',
            'menu_position'       => 27,
            'supports'            => array(
                'title',
                'editor',
                'author',
                'thumbnail',
            ),
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
        )
    );
}

/**
 * ================================================================
 * ACF local fields
 * ================================================================
 */

add_action( 'acf/init', 'obras_aux_register_acf_fields' );

function obras_aux_register_acf_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group(
        array(
            'key'      => 'group_obras_aux_section',
            'title'    => 'Datos de la sección auxiliar',
            'fields'   => array(
                array(
                    'key'           => 'field_obras_aux_group',
                    'label'         => 'Sección auxiliar',
                    'name'          => 'aux_group',
                    'type'          => 'select',
                    'choices'       => obras_aux_get_section_choices(),
                    'default_value' => obras_aux_get_default_group(),
                    'allow_null'    => 0,
                    'multiple'      => 0,
                    'ui'            => 0,
                    'return_format' => 'value',
                ),
                array(
                    'key'           => 'field_obras_aux_class',
                    'label'         => 'Clase del contenido',
                    'name'          => 'clase_auxiliar',
                    'type'          => 'select',
                    'choices'       => array(
                        ''             => 'Sin clasificar',
                        'observacion'  => 'Observación',
                        'novedad'      => 'Novedad',
                        'explicacion'  => 'Explicación',
                        'referencia'   => 'Referencia',
                        'dato_tecnico' => 'Dato técnico',
                    ),
                    'default_value' => '',
                    'allow_null'    => 0,
                    'multiple'      => 0,
                    'ui'            => 0,
                    'return_format' => 'value',
                ),
                array(
                    'key'           => 'field_obras_aux_file',
                    'label'         => 'Archivo adjunto',
                    'name'          => 'archivo_auxiliar',
                    'type'          => 'file',
                    'return_format' => 'id',
                    'library'       => 'all',
                ),
                array(
                    'key'           => 'field_obras_aux_archived',
                    'label'         => 'Archivado',
                    'name'          => 'aux_archivado',
                    'type'          => 'true_false',
                    'default_value' => 0,
                    'ui'            => 1,
                    'message'       => 'Ocultar de los listados normales',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'aux_section',
                    ),
                ),
            ),
            'position' => 'normal',
            'style'    => 'default',
        )
    );
}

/**
 * Actualiza dinámicamente las opciones del select aux_group.
 */
add_filter( 'acf/load_field/name=aux_group', 'obras_aux_acf_load_group_choices' );

function obras_aux_acf_load_group_choices( $field ) {
    $field['choices'] = obras_aux_get_section_choices();
    return $field;
}

/**
 * Si se llega desde post-new.php?post_type=aux_section&aux_group=algo,
 * se preselecciona ese grupo.
 */
add_filter( 'acf/load_value/name=aux_group', 'obras_aux_acf_default_group_value', 10, 3 );

function obras_aux_acf_default_group_value( $value, $post_id, $field ) {
    if ( ! empty( $value ) ) {
        return $value;
    }

    if ( isset( $_GET['aux_group'] ) ) {
        return sanitize_key( wp_unslash( $_GET['aux_group'] ) );
    }

    return obras_aux_get_default_group();
}

/**
 * Oculta campos técnicos para reducir ruido visual.
 * Se mantienen activos, pero no se muestran al usuario común.
 */
add_action( 'admin_head-post.php', 'obras_aux_admin_hide_technical_fields' );
add_action( 'admin_head-post-new.php', 'obras_aux_admin_hide_technical_fields' );

function obras_aux_admin_hide_technical_fields() {
    $screen = get_current_screen();

    if ( ! $screen || 'aux_section' !== $screen->post_type ) {
        return;
    }

    ?>
    <style>
        .acf-field[data-name="aux_group"],
        .acf-field[data-name="aux_archivado"] {
            display: none !important;
        }
    </style>
    <?php
}

/**
 * ================================================================
 * Restricción de acceso a singles aux_section
 * ================================================================
 */

add_action( 'template_redirect', 'obras_aux_restrict_single_access', 5 );

function obras_aux_restrict_single_access() {
    if ( ! is_singular( 'aux_section' ) ) {
        return;
    }

    $post_id     = get_queried_object_id();
    $is_archived = get_post_meta( $post_id, 'aux_archivado', true );

    if ( is_user_logged_in() ) {
        if ( $is_archived && ! obras_aux_user_can_manage_post( $post_id ) ) {
            wp_safe_redirect( home_url( '/' ) );
            exit;
        }

        return;
    }

    $public_access = get_post_meta( $post_id, 'acceso_publico', true );

    if ( 'publish' === get_post_status( $post_id ) && '1' === (string) $public_access && ! $is_archived ) {
        return;
    }

    wp_safe_redirect( wp_login_url( get_permalink( $post_id ) ) );
    exit;
}

/**
 * ================================================================
 * Shortcode de listado
 *
 * Uso:
 * [obras_lista_aux section="general"]
 * ================================================================
 */

add_shortcode( 'obras_lista_aux', 'obras_aux_shortcode_list' );

function obras_aux_shortcode_list( $atts ) {
    if ( ! is_user_logged_in() ) {
        return '<p>Debes iniciar sesión para ver este contenido.</p>';
    }

    $atts = shortcode_atts(
        array(
            'section'    => obras_aux_get_default_group(),
            'archivadas' => 'no',
        ),
        $atts,
        'obras_lista_aux'
    );

    $group        = sanitize_key( $atts['section'] );
    $section      = obras_aux_get_section( $group );
    $show_archive = 'yes' === strtolower( (string) $atts['archivadas'] );

    if ( ! $section ) {
        return '<p>La sección auxiliar indicada no existe.</p>';
    }

    if ( empty( $section['visible'] ) && ! current_user_can( 'manage_options' ) ) {
        return '<p>Esta sección no está disponible.</p>';
    }

    $plural    = obras_aux_get_section_label( $group, true );
    $singular  = obras_aux_get_section_label( $group, false );
    $new_label = obras_aux_get_new_label( $group );

    $group_query = array(
        'key'     => 'aux_group',
        'value'   => $group,
        'compare' => '=',
    );

    if ( 'general' === $group ) {
        $group_query = array(
            'relation' => 'OR',
            array(
                'key'     => 'aux_group',
                'value'   => 'general',
                'compare' => '=',
            ),
            array(
                'key'     => 'aux_group',
                'compare' => 'NOT EXISTS',
            ),
        );
    }

    $meta_query = array(
        'relation' => 'AND',
        $group_query,
    );

    if ( ! $show_archive ) {
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key'     => 'aux_archivado',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => 'aux_archivado',
                'value'   => '1',
                'compare' => '!=',
            ),
        );
    }

    $query = new WP_Query(
        array(
            'post_type'      => 'aux_section',
            'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
            'posts_per_page' => 50,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => $meta_query,
        )
    );

    $new_url = add_query_arg(
        array(
            'post_type' => 'aux_section',
            'aux_group' => $group,
        ),
        admin_url( 'post-new.php' )
    );

    ob_start();
    ?>
    <div class="obras-lista">
    <h1>🧩 <?php echo esc_html( $plural ); ?></h1>

    <?php
    echo '<div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:center; margin:0 0 25px;">';

    echo '<a href="' . esc_url( $new_url ) . '" style="display:inline-block; padding:12px 20px; background:#2271b1; color:#fff; text-decoration:none; border-radius:8px; font-weight:600;"">' . esc_html( $new_label ) . '</a>';

    echo '<a href="' . esc_url( home_url( '/auxiliar/' ) ) . '" style="display:inline-block; padding:12px 20px; background:#eef3f5; color:#00506a; text-decoration:none; border-radius:8px; font-weight:600; border:1px solid #ccd8dd;">Más secciones</a>';

    echo '<a href="' . esc_url( home_url( '/' ) ) . '" style="display:inline-block; padding:12px 20px; background:#6c757d; color:#fff; text-decoration:none; border-radius:8px; font-weight:600;">Volver al inicio</a>';

    echo '</div>';
    ?>

    <?php
    $items_rendered = 0;

    if ( $query->have_posts() ) :
        while ( $query->have_posts() ) :
            $query->the_post();

            $post_id    = get_the_ID();
            $status     = get_post_status( $post_id );
            $can_manage = obras_aux_user_can_manage_post( $post_id );

            if ( 'publish' !== $status && ! $can_manage ) {
                continue;
            }

            $items_rendered++;
            ?>
            <div class="item">
                <?php
                if ( function_exists( 'obras_render_list_item_title' ) ) {
                    obras_render_list_item_title( $post_id, $singular . ' sin título' );
                } else {
                    echo '<h3><a href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html( get_the_title( $post_id ) ) . '</a></h3>';
                }

                if ( function_exists( 'obras_render_post_meta_line' ) ) {
                    obras_render_post_meta_line( $post_id );
                } else {
                    $author_id   = (int) get_post_field( 'post_author', $post_id );
                    $author_name = get_the_author_meta( 'display_name', $author_id );
                    echo '<p class="meta">📅 ' . esc_html( get_the_date( get_option( 'date_format' ), $post_id ) ) . ' ✍ ' . esc_html( $author_name ) . '</p>';
                }

                echo '<span class="badge">' . esc_html( obras_aux_get_class_label( $post_id ) ) . '</span>';

                if ( function_exists( 'obras_render_post_status_badge' ) ) {
                    obras_render_post_status_badge( $post_id );
                }

                if ( function_exists( 'obras_render_item_actions' ) ) {
                    obras_render_item_actions( $post_id );
                }
                ?>
            </div>
            <?php
        endwhile;
    endif;

    wp_reset_postdata();

    if ( 0 === $items_rendered ) :
        ?>
        <p class="empty">Aún no hay elementos registrados.</p>
        <?php
    endif;
    ?>
    </div>
    <?php

    return ob_get_clean();
}



/**
 * ================================================================
 * Datos al final del single
 * ================================================================
 */

add_filter( 'the_content', 'obras_aux_single_content_meta', 30 );

function obras_aux_single_content_meta( $content ) {
    if ( ! is_singular( 'aux_section' ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    $post_id     = get_the_ID();
    $group       = get_post_meta( $post_id, 'aux_group', true );
    $group       = $group ? sanitize_key( $group ) : obras_aux_get_default_group();
    $plural      = obras_aux_get_section_label( $group, true );
    $class_label = obras_aux_get_class_label( $post_id );
    $file_url    = obras_aux_get_attachment_url( $post_id );
    $list_url    = home_url( '/' . obras_aux_get_section_slug( $group ) . '/' );

    ob_start();

    echo '<div class="obras-single-meta obras-single-meta-auxiliar">';
    echo '<h3>Datos del contenido</h3>';
    echo '<ul>';
    echo '<li><strong>Fecha:</strong> ' . esc_html( get_the_date( get_option( 'date_format' ), $post_id ) ) . '</li>';
    echo '<li><strong>Autor:</strong> ' . esc_html( get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) ) ) . '</li>';
    echo '<li><strong>Clase:</strong> ' . esc_html( $class_label ) . '</li>';

    if ( $file_url ) {
        echo '<li><strong>Archivo adjunto:</strong> <a href="' . esc_url( $file_url ) . '" target="_blank" rel="noopener">Abrir archivo</a></li>';
    } else {
        echo '<li><strong>Archivo adjunto:</strong> Sin archivo adjunto</li>';
    }

    echo '</ul>';
    echo '</div>';

    $section_title = obras_aux_get_section_title( $group );

    echo '<div class="obras-single-actions obras-single-actions-aux">';

    echo '<a class="obras-aux-single-btn obras-aux-single-btn-primary" href="' . esc_url( $list_url ) . '">← ' . esc_html( $section_title ) . '</a>';

    echo '<a class="obras-aux-single-btn obras-aux-single-btn-more" href="' . esc_url( home_url( '/auxiliar/' ) ) . '">🧩 Más secciones</a>';

    echo '<a class="obras-aux-single-btn obras-aux-single-btn-secondary" href="' . esc_url( home_url( '/' ) ) . '">🏠 Inicio</a>';

    if ( obras_aux_user_can_manage_post( $post_id ) ) {
        $edit_url = get_edit_post_link( $post_id );

        if ( $edit_url ) {
            echo '<a class="obras-aux-single-btn obras-aux-single-btn-edit" href="' . esc_url( $edit_url ) . '">✏️ Editar</a>';
        }
    }

    echo '</div>';

    return $content . ob_get_clean();
}

/**
 * ================================================================
 * Columnas wp-admin
 * ================================================================
 */

add_filter( 'manage_aux_section_posts_columns', 'obras_aux_admin_columns' );

function obras_aux_admin_columns( $columns ) {
    $new = array();

    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;

        if ( 'title' === $key ) {
            $new['aux_group']     = 'Sección auxiliar';
            $new['aux_archivado'] = 'Archivado';
        }
    }

    return $new;
}

add_action( 'manage_aux_section_posts_custom_column', 'obras_aux_admin_column_content', 10, 2 );

function obras_aux_admin_column_content( $column, $post_id ) {
    if ( 'aux_group' === $column ) {
        $group = get_post_meta( $post_id, 'aux_group', true );
        $group = $group ? sanitize_key( $group ) : 'general';

        echo esc_html( obras_aux_get_section_label( $group, true ) );
    }

    if ( 'aux_archivado' === $column ) {
        $archived = get_post_meta( $post_id, 'aux_archivado', true );

        echo $archived ? 'Sí' : 'No';
    }
}

/**
 * ================================================================
 * Dashboard auxiliar
 *
 * Uso:
 * [obras_aux_dashboard]
 * ================================================================
 */

add_shortcode( 'obras_aux_dashboard', 'obras_aux_shortcode_dashboard' );

function obras_aux_shortcode_dashboard() {
    if ( ! is_user_logged_in() ) {
        return '<p>Debes iniciar sesión para ver este contenido.</p>';
    }

    $sections = obras_aux_get_sections();

    ob_start();

    echo '<div class="obras-dashboard obras-aux-dashboard">';
    echo '<h1>Más secciones</h1>';
    echo '<p>Elegí una sección auxiliar.</p>';

    echo '<div class="obras-buttons">';

    $rendered = 0;

    foreach ( $sections as $group => $section ) {
        $group = sanitize_key( $group );

        if ( empty( $group ) ) {
            continue;
        }

        if ( empty( $section['visible'] ) || ! empty( $section['archivada'] ) ) {
            continue;
        }

        $title    = obras_aux_get_section_title( $group );
        $subtitle = ! empty( $section['subtitle'] ) ? $section['subtitle'] : '';
        $url      = home_url( '/' . obras_aux_get_section_slug( $group ) . '/' );

        echo '<a href="' . esc_url( $url ) . '" class="obras-button secondary">';
        echo '<span class="icon">🧩</span>';
        echo '<span class="obras-aux-card-title">' . esc_html( $title ) . '</span>';

        if ( ! empty( $subtitle ) ) {
            echo '<span class="obras-aux-card-subtitle">' . esc_html( $subtitle ) . '</span>';
        }

        echo '</a>';

        $rendered++;
    }

    echo '</div>';

    if ( 0 === $rendered ) {
        echo '<p>No hay secciones auxiliares disponibles.</p>';
    }

    echo '<div class="obras-dashboard-more">';
    echo '<a href="' . esc_url( home_url( '/' ) ) . '" class="obras-dashboard-more-link">Inicio</a>';
    echo '</div>';

    echo '</div>';

    return ob_get_clean();
}

/**
 * ================================================================
 * Botones de navegación en el editor de aux_section
 * ================================================================
 */

add_action( 'edit_form_top', 'obras_aux_editor_nav_buttons' );

function obras_aux_editor_nav_buttons( $post ) {
    if ( ! $post || 'aux_section' !== $post->post_type ) {
        return;
    }

    $list_url      = home_url( '/' . obras_aux_get_section_slug( 'general' ) . '/' );
    $more_url      = home_url( '/auxiliar/' );
    $dashboard_url = home_url( '/' );

    echo '<div class="obras-admin-editor-nav obras-admin-editor-nav-aux" style="margin: 12px 0 16px; padding: 10px 12px; background: #f6f7f7; border-left: 4px solid #00506a;">';

    echo '<a class="button button-secondary" style="margin-right: 8px;" href="' . esc_url( $list_url ) . '">← ' . esc_html( obras_aux_get_section_label( 'general', true ) ) . '</a>';

    echo '<a class="button button-secondary" style="margin-right: 8px;" href="' . esc_url( $more_url ) . '">🧩 Más secciones</a>';

    echo '<a class="button button-secondary" href="' . esc_url( $dashboard_url ) . '">🏠 Inicio</a>';

    echo '</div>';
}
