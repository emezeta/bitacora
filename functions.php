<?php
/**
 * Bitácora - Theme Functions
 * Theme autónomo
 * Version: 0.1.0-dev
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================================================
// === MÓDULOS DEL THEME  =====================================================
// ============================================================================

require_once get_stylesheet_directory() . '/inc/enqueue.php';
require_once get_stylesheet_directory() . '/inc/content-model.php';
require_once get_stylesheet_directory() . '/inc/profiles.php';
require_once get_stylesheet_directory() . '/inc/item-editor.php';
require_once get_stylesheet_directory() . '/inc/kiosk.php';
require_once get_stylesheet_directory() . '/inc/admin-access.php';
require_once get_stylesheet_directory() . '/inc/author-control.php';
require_once get_stylesheet_directory() . '/inc/admin-dashboard.php';
require_once get_stylesheet_directory() . '/inc/auth.php';
require_once get_stylesheet_directory() . '/inc/restrict.php';
require_once get_stylesheet_directory() . '/inc/branding.php';
require_once get_stylesheet_directory() . '/inc/content-meta.php';
require_once get_stylesheet_directory() . '/inc/shortcodes.php';
require_once get_stylesheet_directory() . '/inc/share-links.php';
require_once get_stylesheet_directory() . '/inc/item-list.php';
require_once get_stylesheet_directory() . '/inc/landing.php';
require_once get_stylesheet_directory() . '/inc/comments.php';
require_once get_stylesheet_directory() . '/inc/admin-columns.php';
require_once get_stylesheet_directory() . '/inc/pad.php';
require_once get_stylesheet_directory() . '/inc/sala-jitsi.php';
require_once get_stylesheet_directory() . '/inc/redirects.php';

// ============================================================================
// === DESACTIVAR GUTENBERG / FORZAR CLASSIC EDITOR ===========================
// ============================================================================

add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
add_filter( 'use_block_editor_for_post', '__return_false', 100 );
add_filter( 'gutenberg_use_widgets_block_editor', '__return_false' );
add_filter( 'use_widgets_block_editor', '__return_false' );
remove_theme_support( 'core-block-patterns' );

add_filter( 'classic_editor_enabled_editors', function( $editors ) {
    return array( 'classic' => true );
} );

require_once get_stylesheet_directory() . '/inc/install.php';
