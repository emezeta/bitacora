<?php
/**
 * Perfil de instalación: Construcción
 *
 * Este archivo contiene exclusivamente datos iniciales del perfil.
 * No forma parte del núcleo lógico de Bitácora y no se carga
 * automáticamente.
 *
 * Las secciones creadas a partir de este perfil pasan a ser datos
 * normales de WordPress. El perfil no determina su comportamiento
 * posterior.
 */

defined( 'ABSPATH' ) || exit;

return array(

    'id'    => 'construccion',
    'label' => 'Construcción',

    'sections' => array(

        'documentos' => array(
            'name'      => 'Documentos',
            'slug'      => 'documentos',
            'singular'  => 'Documento',
            'plural'    => 'Documentos',
            'subtitle'  => '',
            'new_label' => 'Nuevo documento',
            'order'     => 10,
            'area'      => 'main',
            'state'     => 'active',
        ),

        'materiales' => array(
            'name'      => 'Materiales',
            'slug'      => 'materiales',
            'singular'  => 'Material',
            'plural'    => 'Materiales',
            'subtitle'  => '',
            'new_label' => 'Nuevo material',
            'order'     => 20,
            'area'      => 'main',
            'state'     => 'active',
        ),

        'catalogos' => array(
            'name'      => 'Catálogos',
            'slug'      => 'catalogos',
            'singular'  => 'Catálogo',
            'plural'    => 'Catálogos',
            'subtitle'  => '',
            'new_label' => 'Nuevo catálogo',
            'order'     => 30,
            'area'      => 'main',
            'state'     => 'active',
        ),

        'planos' => array(
            'name'      => 'Planos',
            'slug'      => 'planos',
            'singular'  => 'Plano',
            'plural'    => 'Planos',
            'subtitle'  => '',
            'new_label' => 'Nuevo plano',
            'order'     => 40,
            'area'      => 'main',
            'state'     => 'active',
        ),

        'paisajismo' => array(
            'name'      => 'Paisajismo',
            'slug'      => 'paisajismo',
            'singular'  => 'Idea',
            'plural'    => 'Ideas',
            'subtitle'  => 'Lluvia de ideas',
            'new_label' => 'Nueva idea',
            'order'     => 50,
            'area'      => 'more',
            'state'     => 'active',
        ),
    ),
);
