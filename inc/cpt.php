<?php
/**
 * Bitácora - CPT
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================================================
// === BITÁCORA ===============================================================
// ============================================================================

function obras_register_bitacora_cpt() {
    register_post_type(
        'bitacora',
        array(
            'labels' => array(
                'name'                  => 'Notas',
                'singular_name'         => 'Nota',
                'menu_name'             => 'Bitácora',
                'name_admin_bar'        => 'Nota',
                'add_new'               => 'Nueva nota',
                'add_new_item'          => 'Agregar nota nueva',
                'new_item'              => 'Nueva nota',
                'edit_item'             => 'Editar nota',
                'view_item'             => 'Ver nota',
                'all_items'             => 'Notas',
                'search_items'          => 'Buscar notas',
                'not_found'             => 'No se encontraron notas',
                'not_found_in_trash'    => 'No se encontraron notas en la papelera',
                'archives'              => 'Archivo de notas',
                'attributes'            => 'Atributos de la nota',
                'insert_into_item'      => 'Insertar en la nota',
                'uploaded_to_this_item' => 'Subido a esta nota',
            ),
            'public'        => true,
            'has_archive'   => true,
            'rewrite'       => array( 'slug' => 'bitacora-cpt' ),
              'supports'      => array( 'title', 'editor', 'author' ),
              'menu_icon'     => 'dashicons-book',
              'menu_position' => 2,
              'capability_type' => array(
                  'bitacora_content',
                  'bitacora_contents',
              ),
              'map_meta_cap'    => true,
              'show_in_rest'    => false,
        )
    );
}

// ============================================================================
// === DOCUMENTOS =============================================================
// ============================================================================

// ============================================================================
// === MATERIALES =============================================================
// ============================================================================

// ============================================================================
// === CATÁLOGOS ==============================================================
// ============================================================================

// ============================================================================
// === PLANOS =================================================================
// ============================================================================

// ============================================================================
// === HOOKS ==================================================================
// ============================================================================

add_action( 'init', 'obras_register_bitacora_cpt' );
