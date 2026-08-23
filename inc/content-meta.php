<?php
/**
 * Bitácora - Campos ACF y navegación en single post.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================================
// === MOSTRAR CAMPOS ACF + NAVEGACIÓN EN SINGLE POST =========================
// ============================================================================

add_filter( 'the_content', 'obras_display_acf_fields_on_single' );
function obras_display_acf_fields_on_single( $content ) {
	if ( is_admin() || ! is_single() ) {
		return $content;
	}

	$post_type = get_post_type();
	$post_id   = get_the_ID();

	$allowed_post_types = array(
		'bitacora_item',
	);

	if ( ! in_array( $post_type, $allowed_post_types, true ) ) {
		return $content;
	}

	$nav_html = '';
	$box_html = '';

	// ------------------------------------------------------------------------
	// Navegación contextual
	// ------------------------------------------------------------------------
	if ( function_exists( 'obras_get_list_url' ) && function_exists( 'obras_get_list_label' ) && function_exists( 'obras_get_dashboard_url' ) ) {
		$list_url   = obras_get_list_url( $post_id );
		$list_label = obras_get_list_label( $post_id );
		$home_url   = obras_get_dashboard_url();

		$nav_html .= '<div class="obras-single-nav" style="margin:0 0 22px; display:flex; gap:10px; flex-wrap:wrap;">';
		$nav_html .= '<a href="' . esc_url( $list_url ) . '" style="display:inline-block; padding:10px 16px; background:#2271b1; color:#fff; text-decoration:none; border-radius:8px; font-weight:600;">← ' . esc_html( $list_label ) . '</a>';
		$nav_html .= '<a href="' . esc_url( $home_url ) . '" style="display:inline-block; padding:10px 16px; background:#6c757d; color:#fff; text-decoration:none; border-radius:8px; font-weight:600;">🏠 Inicio</a>';

		$can_manage_current = current_user_can( 'edit_post', $post_id );

		if ( $can_manage_current ) {
			$edit_url = get_edit_post_link( $post_id );
			if ( $edit_url ) {
				$nav_html .= '<a href="' . esc_url( $edit_url ) . '" style="display:inline-block; padding:10px 16px; background:#198754; color:#fff; text-decoration:none; border-radius:8px; font-weight:600;">✏️ Editar</a>';
			}
		}

		$nav_html .= '</div>';
	}


	// ------------------------------------------------------------------------
	// Ítem genérico 0.2.0
	// ------------------------------------------------------------------------
	if ( 'bitacora_item' === $post_type ) {

		$section = bitacora_get_item_section( $post_id );
		$class   = bitacora_get_item_class( $post_id );

		$file_id  = 0;
		$location = '';

		if (
			$section
			&& bitacora_section_has_feature( $section, 'file' )
		) {
			$file_id = absint(
				get_post_meta(
					$post_id,
					'bitacora_item_file',
					true
				)
			);
		}

		if (
			$section
			&& bitacora_section_has_feature( $section, 'location' )
		) {
			$location = trim(
				(string) get_post_meta(
					$post_id,
					'bitacora_item_location',
					true
				)
			);
		}

		$rows = array();

		if ( $class ) {
			$rows[] =
				'<p><strong>Clase del contenido:</strong> '
				. esc_html( $class->name )
				. '</p>';
		}

		if ( '' !== $location ) {
			$rows[] =
				'<p><strong>📍 Ubicación:</strong> '
				. esc_html( $location )
				. '</p>';
		}

		if ( $file_id ) {

			$file_url = wp_get_attachment_url( $file_id );

			if ( $file_url ) {

				$file_label = trim(
					(string) get_the_title( $file_id )
				);

				if ( '' === $file_label ) {
					$file_label = wp_basename( $file_url );
				}

				$rows[] =
					'<p><strong>📎 Archivo de referencia:</strong> '
					. '<a href="'
					. esc_url( $file_url )
					. '" target="_blank" rel="noopener">'
					. esc_html( $file_label )
					. '</a></p>';
			}
		}

		if ( ! empty( $rows ) ) {
			$box_html .=
				'<div class="obras-acf-box bitacora-item">';

			$box_html .= '<h3>Datos del contenido</h3>';
			$box_html .= implode( '', $rows );
			$box_html .= '</div>';
		}
	}


	// ------------------------------------------------------------------------
	// Nota
	// ------------------------------------------------------------------------

return $content . $box_html . $nav_html;
}
