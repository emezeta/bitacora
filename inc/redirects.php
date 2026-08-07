<?php
/**
 * Redirecciones administrables desde el editor de páginas.
 */

defined( 'ABSPATH' ) || exit;

const OBRAS_REDIRECT_PAGOS_PAGE_OPTION = 'obras_seguimiento_pagos_page_id';
const OBRAS_REDIRECT_PAGOS_META_KEY    = 'obras_url_destino';

/**
 * Campo ACF visible solamente en la página de Seguimiento de pagos.
 */
add_action( 'acf/init', function () {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    $page_id = (int) get_option( OBRAS_REDIRECT_PAGOS_PAGE_OPTION, 0 );

    if ( $page_id <= 0 ) {
        return;
    }

    acf_add_local_field_group(
        array(
            'key'                   => 'group_obras_redirect_pagos',
            'title'                 => 'Redirección externa',
            'fields'                => array(
                array(
                    'key'           => 'field_obras_redirect_pagos_url',
                    'label'         => 'URL de destino',
                    'name'          => OBRAS_REDIRECT_PAGOS_META_KEY,
                    'type'          => 'url',
                    'instructions'  => 'Al guardar una URL válida, esta página redirige allí mediante una redirección temporal (302). Dejar vacío desactiva la redirección.',
                    'required'      => 0,
                    'default_value' => '',
                ),
            ),
            'location'              => array(
                array(
                    array(
                        'param'    => 'page',
                        'operator' => '==',
                        'value'    => (string) $page_id,
                    ),
                ),
            ),
            'position'              => 'normal',
            'style'                 => 'default',
            'label_placement'      => 'top',
            'instruction_placement'=> 'label',
            'active'                => true,
            'show_in_rest'          => 0,
        )
    );
} );

/**
 * Redirige la página configurada hacia la URL guardada en su campo ACF.
 */
add_action( 'template_redirect', function () {
    $page_id = (int) get_option( OBRAS_REDIRECT_PAGOS_PAGE_OPTION, 0 );

    if ( $page_id <= 0 || ! is_page( $page_id ) ) {
        return;
    }

    $destino = trim(
        (string) get_post_meta( $page_id, OBRAS_REDIRECT_PAGOS_META_KEY, true )
    );

    if ( ! $destino || ! wp_http_validate_url( $destino ) ) {
        return;
    }

    wp_redirect( esc_url_raw( $destino ), 302, 'Bitácora de Obra' );
    exit;
} );
