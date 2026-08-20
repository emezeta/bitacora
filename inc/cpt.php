<?php
/**
 * Bitácora de Obra - CPT
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

function obras_register_documento_cpt() {
    register_post_type(
        'documento_obra',
        array(
            'labels' => array(
                'name'                  => 'Documentos',
                'singular_name'         => 'Documento',
                'menu_name'             => 'Documentos',
                'name_admin_bar'        => 'Documento',
                'add_new'               => 'Nuevo documento',
                'add_new_item'          => 'Agregar documento nuevo',
                'new_item'              => 'Nuevo documento',
                'edit_item'             => 'Editar documento',
                'view_item'             => 'Ver documento',
                'all_items'             => 'Documentos',
                'search_items'          => 'Buscar documentos',
                'not_found'             => 'No se encontraron documentos',
                'not_found_in_trash'    => 'No se encontraron documentos en la papelera',
                'archives'              => 'Archivo de documentos',
                'attributes'            => 'Atributos del documento',
                'insert_into_item'      => 'Insertar en el documento',
                'uploaded_to_this_item' => 'Subido a este documento',
            ),
            'public'        => true,
            'has_archive'   => false,
            'rewrite'       => array( 'slug' => 'documentos-cpt' ),
              'supports'      => array( 'title', 'editor', 'author' ),
              'menu_icon'     => 'dashicons-media-document',
              'menu_position' => 3,
              'show_in_rest'  => false,
              'show_in_menu'  => 'edit.php?post_type=bitacora',
        )
    );
}

// ============================================================================
// === MATERIALES =============================================================
// ============================================================================

function obras_register_material_cpt() {
    register_post_type(
        'material_obra',
        array(
            'labels' => array(
                'name'                  => 'Materiales',
                'singular_name'         => 'Material',
                'menu_name'             => 'Materiales',
                'name_admin_bar'        => 'Material',
                'add_new'               => 'Nuevo material',
                'add_new_item'          => 'Agregar material nuevo',
                'new_item'              => 'Nuevo material',
                'edit_item'             => 'Editar material',
                'view_item'             => 'Ver material',
                'all_items'             => 'Materiales',
                'search_items'          => 'Buscar materiales',
                'not_found'             => 'No se encontraron materiales',
                'not_found_in_trash'    => 'No se encontraron materiales en la papelera',
                'archives'              => 'Archivo de materiales',
                'attributes'            => 'Atributos del material',
                'insert_into_item'      => 'Insertar en el material',
                'uploaded_to_this_item' => 'Subido a este material',
            ),
            'public'        => true,
            'has_archive'   => false,
            'rewrite'       => array( 'slug' => 'materiales-cpt' ),
              'supports'      => array( 'title', 'editor', 'author', 'thumbnail' ),
              'menu_icon'     => 'dashicons-archive',
              'menu_position' => 4,
              'show_in_rest'  => false,
              'show_in_menu'  => 'edit.php?post_type=bitacora',
        )
    );
}

// ============================================================================
// === CATÁLOGOS ==============================================================
// ============================================================================

function obras_register_catalogo_cpt() {
    register_post_type(
        'catalogo_obra',
        array(
            'labels' => array(
                'name'                  => 'Catálogos',
                'singular_name'         => 'Catálogo',
                'menu_name'             => 'Catálogos',
                'name_admin_bar'        => 'Catálogo',
                'add_new'               => 'Nuevo catálogo',
                'add_new_item'          => 'Agregar catálogo nuevo',
                'new_item'              => 'Nuevo catálogo',
                'edit_item'             => 'Editar catálogo',
                'view_item'             => 'Ver catálogo',
                'all_items'             => 'Catálogos',
                'search_items'          => 'Buscar catálogos',
                'not_found'             => 'No se encontraron catálogos',
                'not_found_in_trash'    => 'No se encontraron catálogos en la papelera',
                'archives'              => 'Archivo de catálogos',
                'attributes'            => 'Atributos del catálogo',
                'insert_into_item'      => 'Insertar en el catálogo',
                'uploaded_to_this_item' => 'Subido a este catálogo',
            ),
            'public'        => true,
            'has_archive'   => false,
            'rewrite'       => array( 'slug' => 'catalogos-cpt' ),
              'supports'      => array( 'title', 'editor', 'author' ),
              'menu_icon'     => 'dashicons-book-alt',
              'menu_position' => 5,
              'show_in_rest'  => false,
              'show_in_menu'  => 'edit.php?post_type=bitacora',
        )
    );
}

// ============================================================================
// === PLANOS =================================================================
// ============================================================================

function obras_register_plano_cpt() {
    register_post_type(
        'plano_obra',
        array(
            'labels' => array(
                'name'                  => 'Planos',
                'singular_name'         => 'Plano',
                'menu_name'             => 'Planos',
                'name_admin_bar'        => 'Plano',
                'add_new'               => 'Nuevo plano',
                'add_new_item'          => 'Agregar plano nuevo',
                'new_item'              => 'Nuevo plano',
                'edit_item'             => 'Editar plano',
                'view_item'             => 'Ver plano',
                'all_items'             => 'Planos',
                'search_items'          => 'Buscar planos',
                'not_found'             => 'No se encontraron planos',
                'not_found_in_trash'    => 'No se encontraron planos en la papelera',
                'archives'              => 'Archivo de planos',
                'attributes'            => 'Atributos del plano',
                'insert_into_item'      => 'Insertar en el plano',
                'uploaded_to_this_item' => 'Subido a este plano',
            ),
            'public'        => true,
            'has_archive'   => false,
            'rewrite'       => array( 'slug' => 'planos-cpt' ),
              'supports'      => array( 'title', 'editor', 'author' ),
              'menu_icon'     => 'dashicons-media-spreadsheet',
              'menu_position' => 6,
              'show_in_rest'  => false,
              'show_in_menu'  => 'edit.php?post_type=bitacora',
        )
    );
}

// ============================================================================
// === HOOKS ==================================================================
// ============================================================================

add_action( 'init', 'obras_register_bitacora_cpt' );
add_action( 'init', 'obras_register_documento_cpt' );
add_action( 'init', 'obras_register_material_cpt' );
add_action( 'init', 'obras_register_catalogo_cpt' );
add_action( 'init', 'obras_register_plano_cpt' );
