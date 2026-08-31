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

    /*
     * El contenedor principal usa los defaults del sistema:
     * Notas / Nota / Nueva nota.
     *
     * Construcción añade comentarios al core.
     */
    'core' => array(
        'feature_comments' => true,
    ),

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

            'feature_file'      => true,
            'feature_thumbnail' => false,
            'feature_location'  => false,
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

            'feature_file'      => true,
            'feature_thumbnail' => true,
            'feature_location'  => true,
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

            'feature_file'      => true,
            'feature_thumbnail' => false,
            'feature_location'  => false,
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

            'feature_file'      => true,
            'feature_thumbnail' => false,
            'feature_location'  => false,
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

            'feature_file'      => true,
            'feature_thumbnail' => false,
            'feature_location'  => false,
        ),
    ),

    'classes' => array(

        /*
         * Notas.
         */
        'notas-observacion' => array(
            'name'     => 'Observación',
            'slug'     => 'notas-observacion',
            'scope'    => 'section',
            'scope_id' => 'notas',
            'order'    => 10,
            'state'    => 'active',
        ),

        'notas-novedad' => array(
            'name'     => 'Novedad',
            'slug'     => 'notas-novedad',
            'scope'    => 'section',
            'scope_id' => 'notas',
            'order'    => 20,
            'state'    => 'active',
        ),

        'notas-explicacion' => array(
            'name'     => 'Explicación',
            'slug'     => 'notas-explicacion',
            'scope'    => 'section',
            'scope_id' => 'notas',
            'order'    => 30,
            'state'    => 'active',
        ),

        'notas-referencia' => array(
            'name'     => 'Referencia',
            'slug'     => 'notas-referencia',
            'scope'    => 'section',
            'scope_id' => 'notas',
            'order'    => 40,
            'state'    => 'active',
        ),

        'notas-dato-tecnico' => array(
            'name'     => 'Dato técnico',
            'slug'     => 'notas-dato-tecnico',
            'scope'    => 'section',
            'scope_id' => 'notas',
            'order'    => 50,
            'state'    => 'active',
        ),

        'notas-audio' => array(
            'name'     => 'Audio',
            'slug'     => 'notas-audio',
            'scope'    => 'section',
            'scope_id' => 'notas',
            'order'    => 60,
            'state'    => 'active',
        ),

        'notas-video' => array(
            'name'     => 'Video',
            'slug'     => 'notas-video',
            'scope'    => 'section',
            'scope_id' => 'notas',
            'order'    => 70,
            'state'    => 'active',
        ),

        'notas-foto-imagen' => array(
            'name'     => 'Foto Imagen',
            'slug'     => 'notas-foto-imagen',
            'scope'    => 'section',
            'scope_id' => 'notas',
            'order'    => 80,
            'state'    => 'active',
        ),


        /*
         * Documentos.
         */
        'documentos-memo' => array(
            'name'     => 'Memo',
            'slug'     => 'documentos-memo',
            'scope'    => 'section',
            'scope_id' => 'documentos',
            'order'    => 10,
            'state'    => 'active',
        ),

        'documentos-instructivo' => array(
            'name'     => 'Instructivo',
            'slug'     => 'documentos-instructivo',
            'scope'    => 'section',
            'scope_id' => 'documentos',
            'order'    => 20,
            'state'    => 'active',
        ),

        'documentos-informe' => array(
            'name'     => 'Informe',
            'slug'     => 'documentos-informe',
            'scope'    => 'section',
            'scope_id' => 'documentos',
            'order'    => 30,
            'state'    => 'active',
        ),

        'documentos-estado-cuenta' => array(
            'name'     => 'Estado de cuenta',
            'slug'     => 'documentos-estado-cuenta',
            'scope'    => 'section',
            'scope_id' => 'documentos',
            'order'    => 40,
            'state'    => 'active',
        ),


        /*
         * Materiales.
         */
        'materiales-foto' => array(
            'name'     => 'Foto',
            'slug'     => 'materiales-foto',
            'scope'    => 'section',
            'scope_id' => 'materiales',
            'order'    => 10,
            'state'    => 'active',
        ),

        'materiales-video' => array(
            'name'     => 'Video',
            'slug'     => 'materiales-video',
            'scope'    => 'section',
            'scope_id' => 'materiales',
            'order'    => 20,
            'state'    => 'active',
        ),

        'materiales-muestra' => array(
            'name'     => 'Muestra',
            'slug'     => 'materiales-muestra',
            'scope'    => 'section',
            'scope_id' => 'materiales',
            'order'    => 30,
            'state'    => 'active',
        ),

        'materiales-insumo' => array(
            'name'     => 'Insumo',
            'slug'     => 'materiales-insumo',
            'scope'    => 'section',
            'scope_id' => 'materiales',
            'order'    => 40,
            'state'    => 'active',
        ),

        'materiales-observacion' => array(
            'name'     => 'Observación',
            'slug'     => 'materiales-observacion',
            'scope'    => 'section',
            'scope_id' => 'materiales',
            'order'    => 50,
            'state'    => 'active',
        ),

        'materiales-referencia' => array(
            'name'     => 'Referencia',
            'slug'     => 'materiales-referencia',
            'scope'    => 'section',
            'scope_id' => 'materiales',
            'order'    => 60,
            'state'    => 'active',
        ),


        /*
         * Catálogos.
         */
        'catalogos-observacion' => array(
            'name'     => 'Observación',
            'slug'     => 'catalogos-observacion',
            'scope'    => 'section',
            'scope_id' => 'catalogos',
            'order'    => 10,
            'state'    => 'active',
        ),

        'catalogos-novedad' => array(
            'name'     => 'Novedad',
            'slug'     => 'catalogos-novedad',
            'scope'    => 'section',
            'scope_id' => 'catalogos',
            'order'    => 20,
            'state'    => 'active',
        ),

        'catalogos-explicacion' => array(
            'name'     => 'Explicación',
            'slug'     => 'catalogos-explicacion',
            'scope'    => 'section',
            'scope_id' => 'catalogos',
            'order'    => 30,
            'state'    => 'active',
        ),

        'catalogos-referencia' => array(
            'name'     => 'Referencia',
            'slug'     => 'catalogos-referencia',
            'scope'    => 'section',
            'scope_id' => 'catalogos',
            'order'    => 40,
            'state'    => 'active',
        ),

        'catalogos-dato-tecnico' => array(
            'name'     => 'Dato técnico',
            'slug'     => 'catalogos-dato-tecnico',
            'scope'    => 'section',
            'scope_id' => 'catalogos',
            'order'    => 50,
            'state'    => 'active',
        ),


        /*
         * Planos.
         */
        'planos-observacion' => array(
            'name'     => 'Observación',
            'slug'     => 'planos-observacion',
            'scope'    => 'section',
            'scope_id' => 'planos',
            'order'    => 10,
            'state'    => 'active',
        ),

        'planos-novedad' => array(
            'name'     => 'Novedad',
            'slug'     => 'planos-novedad',
            'scope'    => 'section',
            'scope_id' => 'planos',
            'order'    => 20,
            'state'    => 'active',
        ),

        'planos-explicacion' => array(
            'name'     => 'Explicación',
            'slug'     => 'planos-explicacion',
            'scope'    => 'section',
            'scope_id' => 'planos',
            'order'    => 30,
            'state'    => 'active',
        ),

        'planos-referencia' => array(
            'name'     => 'Referencia',
            'slug'     => 'planos-referencia',
            'scope'    => 'section',
            'scope_id' => 'planos',
            'order'    => 40,
            'state'    => 'active',
        ),

        'planos-dato-tecnico' => array(
            'name'     => 'Dato técnico',
            'slug'     => 'planos-dato-tecnico',
            'scope'    => 'section',
            'scope_id' => 'planos',
            'order'    => 50,
            'state'    => 'active',
        ),


        /*
         * Paisajismo.
         */
        'paisajismo-observacion' => array(
            'name'     => 'Observación',
            'slug'     => 'paisajismo-observacion',
            'scope'    => 'section',
            'scope_id' => 'paisajismo',
            'order'    => 10,
            'state'    => 'active',
        ),

        'paisajismo-novedad' => array(
            'name'     => 'Novedad',
            'slug'     => 'paisajismo-novedad',
            'scope'    => 'section',
            'scope_id' => 'paisajismo',
            'order'    => 20,
            'state'    => 'active',
        ),

        'paisajismo-explicacion' => array(
            'name'     => 'Explicación',
            'slug'     => 'paisajismo-explicacion',
            'scope'    => 'section',
            'scope_id' => 'paisajismo',
            'order'    => 30,
            'state'    => 'active',
        ),

        'paisajismo-referencia' => array(
            'name'     => 'Referencia',
            'slug'     => 'paisajismo-referencia',
            'scope'    => 'section',
            'scope_id' => 'paisajismo',
            'order'    => 40,
            'state'    => 'active',
        ),

        'paisajismo-dato-tecnico' => array(
            'name'     => 'Dato técnico',
            'slug'     => 'paisajismo-dato-tecnico',
            'scope'    => 'section',
            'scope_id' => 'paisajismo',
            'order'    => 50,
            'state'    => 'active',
        ),
    ),
);
