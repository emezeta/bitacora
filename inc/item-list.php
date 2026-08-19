<?php
/**
 * Bitácora - Listado genérico de ítems por sección.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Devuelve los bitacora_item visibles en una sección.
 *
 * Mantiene la política actual de los listados frontend:
 * - todos los publicados;
 * - borradores y privados propios;
 * - orden descendente por fecha.
 *
 * $section puede ser un slug o un WP_Term.
 */
function bitacora_get_section_items(
    $section,
    $posts_per_page = 50
) {

    if ( is_string( $section ) ) {
        $section = bitacora_get_section( $section );
    }

    if (
        ! $section
        || is_wp_error( $section )
        || ! $section instanceof WP_Term
        || 'bitacora_section' !== $section->taxonomy
    ) {
        return new WP_Error(
            'bitacora_invalid_section',
            'La sección indicada no es válida.'
        );
    }

    $posts_per_page = max(
        1,
        (int) $posts_per_page
    );

    $tax_query = array(
        array(
            'taxonomy' => 'bitacora_section',
            'field'    => 'term_id',
            'terms'    => array(
                (int) $section->term_id,
            ),
        ),
    );

    $published_posts = get_posts(
        array(
            'post_type'              => 'bitacora_item',
            'post_status'            => array( 'publish' ),
            'posts_per_page'         => $posts_per_page,
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'tax_query'              => $tax_query,
            'suppress_filters'       => false,
            'no_found_rows'          => true,
            'ignore_sticky_posts'    => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        )
    );

    $own_unpublished_posts = array();
    $current_user_id       = get_current_user_id();

    if ( $current_user_id ) {

        $own_unpublished_posts = get_posts(
            array(
                'post_type'              => 'bitacora_item',
                'post_status'            => array(
                    'draft',
                    'private',
                ),
                'author'                 => $current_user_id,
                'posts_per_page'         => $posts_per_page,
                'orderby'                => 'date',
                'order'                  => 'DESC',
                'tax_query'              => $tax_query,
                'suppress_filters'       => false,
                'no_found_rows'          => true,
                'ignore_sticky_posts'    => true,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => true,
            )
        );
    }

    $merged = array();

    foreach (
        array_merge(
            $published_posts,
            $own_unpublished_posts
        ) as $post
    ) {
        if ( $post instanceof WP_Post ) {
            $merged[ $post->ID ] = $post;
        }
    }

    uasort(
        $merged,
        function( $a, $b ) {

            $time_a = strtotime(
                $a->post_date_gmt
                    ?: $a->post_date
            );

            $time_b = strtotime(
                $b->post_date_gmt
                    ?: $b->post_date
            );

            if ( $time_a === $time_b ) {
                return 0;
            }

            return ( $time_a > $time_b )
                ? -1
                : 1;
        }
    );

    return array_slice(
        array_values( $merged ),
        0,
        $posts_per_page
    );
}


/**
 * Devuelve la etiqueta de clase de un bitacora_item.
 */
function bitacora_get_item_class_label( $post_id ) {

    $class = bitacora_get_item_class(
        (int) $post_id
    );

    if ( ! $class ) {
        return '';
    }

    return $class->name;
}


/**
 * Renderiza la clase del ítem.
 */
function bitacora_render_item_class_badge( $post_id ) {

    $label = bitacora_get_item_class_label(
        $post_id
    );

    if ( '' === $label ) {
        return;
    }

    echo '<span class="tipo">'
        . esc_html( $label )
        . '</span>';
}


/**
 * Renderiza metadatos opcionales de features de un bitacora_item
 * en los listados de sección.
 */
function bitacora_render_item_feature_meta( $post_id ) {

    $post_id = (int) $post_id;

    $section = bitacora_get_item_section( $post_id );

    if ( ! $section ) {
        return;
    }

    $parts = array();

    if ( bitacora_section_has_feature( $section, 'file' ) ) {

        $file_id = absint(
            get_post_meta(
                $post_id,
                'bitacora_item_file',
                true
            )
        );

        if ( $file_id ) {

            $file_url = wp_get_attachment_url( $file_id );

            if ( $file_url ) {

                $file_label = trim(
                    (string) get_the_title( $file_id )
                );

                if ( '' === $file_label ) {
                    $file_label = wp_basename( $file_url );
                }

                $parts[] =
                    '📎 <a href="'
                    . esc_url( $file_url )
                    . '" target="_blank" rel="noopener">'
                    . esc_html( $file_label )
                    . '</a>';
            }
        }
    }

    if ( bitacora_section_has_feature( $section, 'location' ) ) {

        $location = trim(
            (string) get_post_meta(
                $post_id,
                'bitacora_item_location',
                true
            )
        );

        if ( '' !== $location ) {
            $parts[] = '📍 ' . esc_html( $location );
        }
    }

    if ( empty( $parts ) ) {
        return;
    }

    echo '<div class="bitacora-item-feature-meta"'
        . ' style="margin-top:8px; font-size:0.9em;">'
        . implode(
            ' <span aria-hidden="true">·</span> ',
            $parts
        )
        . '</div>';
}


/**
 * URL para crear un nuevo ítem dentro de una sección.
 */
function bitacora_get_new_section_item_url( $section ) {

    if ( is_string( $section ) ) {
        $section = bitacora_get_section( $section );
    }

    if (
        ! $section
        || is_wp_error( $section )
    ) {
        return '';
    }

    return add_query_arg(
        array(
            'post_type'        => 'bitacora_item',
            'bitacora_section' => $section->slug,
        ),
        admin_url( 'post-new.php' )
    );
}


/**
 * Shortcode:
 *
 * [bitacora_section slug="materiales"]
 */
add_shortcode(
    'bitacora_section',
    'bitacora_render_section_list_shortcode'
);

function bitacora_render_section_list_shortcode(
    $atts = array()
) {

    if ( ! is_user_logged_in() ) {
        return '<p>Debes <a href="'
            . esc_url(
                wp_login_url(
                    get_permalink()
                )
            )
            . '">iniciar sesión</a> para ver el contenido.</p>';
    }

    $atts = shortcode_atts(
        array(
            'slug' => '',
        ),
        $atts,
        'bitacora_section'
    );

    $slug = sanitize_title(
        $atts['slug']
    );

    if ( '' === $slug ) {
        return '<p>Sección no especificada.</p>';
    }

    $section = bitacora_get_section(
        $slug
    );

    if ( ! $section ) {
        return '<p>La sección indicada no existe.</p>';
    }

    if (
        'active' !== bitacora_get_section_meta(
            $section,
            'bitacora_section_state',
            ''
        )
    ) {
        return '<p>La sección no está activa.</p>';
    }

    $items = bitacora_get_section_items(
        $section,
        50
    );

    if ( is_wp_error( $items ) ) {
        return '<p>No fue posible cargar la sección.</p>';
    }

    $plural = bitacora_get_section_meta(
        $section,
        'bitacora_section_plural',
        $section->name
    );

    $singular = bitacora_get_section_meta(
        $section,
        'bitacora_section_singular',
        $section->name
    );

    $subtitle = bitacora_get_section_meta(
        $section,
        'bitacora_section_subtitle',
        ''
    );

    $new_label = bitacora_get_section_meta(
        $section,
        'bitacora_section_new_label',
        ''
    );

    if ( '' === $new_label ) {
        $new_label = 'Nuevo '
            . strtolower(
                (string) $singular
            );
    }

    $new_url = bitacora_get_new_section_item_url(
        $section
    );

    ob_start();
    ?>
    <div class="obras-lista">

        <?php if ( '' !== $subtitle ) : ?>
            <p class="subtitle">
                <?php echo esc_html( $subtitle ); ?>
            </p>
        <?php endif; ?>

        <?php
        obras_render_lista_actions(
            $new_url,
            $new_label
        );
        ?>

        <?php if ( ! empty( $items ) ) : ?>

            <?php foreach ( $items as $list_post ) : ?>

                <div class="item">

                    <?php
                    obras_render_list_item_title(
                        $list_post->ID,
                        $singular . ' sin título'
                    );
                    ?>

                    <?php
                    obras_render_post_meta_line(
                        $list_post->ID
                    );
                    ?>

                    <?php
                    bitacora_render_item_class_badge(
                        $list_post->ID
                    );
                    ?>

                    <?php
                    obras_render_post_status_badge(
                        $list_post->ID
                    );
                    ?>

                    <?php
                    bitacora_render_item_feature_meta(
                        $list_post->ID
                    );
                    ?>

                    <?php
                    obras_render_item_actions(
                        $list_post->ID
                    );
                    ?>

                </div>

            <?php endforeach; ?>

        <?php else : ?>

            <p class="empty">
                Aún no hay contenidos en esta sección.
            </p>

        <?php endif; ?>

    </div>
    <?php

    return ob_get_clean();
}
