<?php
/**
 * Bitácora de Obra - Campos ACF y navegación en single post.
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
		'bitacora',
		'documento_obra',
		'material_obra',
		'catalogo_obra',
		'plano_obra',
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

		$can_manage_current = false;
		if ( function_exists( 'obras_user_can_manage_list_item' ) ) {
			$can_manage_current = obras_user_can_manage_list_item( $post_id );
		} elseif ( is_user_logged_in() ) {
			$can_manage_current = ( get_current_user_id() === (int) get_post_field( 'post_author', $post_id ) ) || current_user_can( 'manage_options' );
		}

		if ( $can_manage_current ) {
			$edit_url = get_edit_post_link( $post_id );
			if ( $edit_url ) {
				$nav_html .= '<a href="' . esc_url( $edit_url ) . '" style="display:inline-block; padding:10px 16px; background:#198754; color:#fff; text-decoration:none; border-radius:8px; font-weight:600;">✏️ Editar</a>';
			}
		}

		$nav_html .= '</div>';
	}

	// ------------------------------------------------------------------------
	// Nota
	// ------------------------------------------------------------------------
	if ( 'bitacora' === $post_type ) {
		$archivo_id = get_post_meta( $post_id, 'archivo_adjunto', true );

		$box_html .= '<div class="obras-acf-box bitacora">';
		$box_html .= '<h3>Datos de la Nota</h3>';

		if ( function_exists( 'obras_get_post_creation_date_label' ) ) {
			$fecha_formateada = obras_get_post_creation_date_label( $post_id );
		} else {
			$fecha_formateada = get_the_date( get_option( 'date_format' ), $post_id );
		}

		$box_html .= '<p><strong>📅 Fecha:</strong> ' . esc_html( $fecha_formateada ) . '</p>';

		if ( ! empty( $archivo_id ) ) {
			if ( is_numeric( $archivo_id ) ) {
				$archivo_url      = wp_get_attachment_url( $archivo_id );
				$archivo_filename = get_post_meta( $archivo_id, '_wp_attachment_image_alt', true ) ?: basename( $archivo_url );
				$box_html .= '<p><strong>📎 Archivo adjunto:</strong> <a href="' . esc_url( $archivo_url ) . '" target="_blank">' . esc_html( $archivo_filename ) . '</a></p>';
			} elseif ( is_array( $archivo_id ) && isset( $archivo_id['url'] ) ) {
				$box_html .= '<p><strong>📎 Archivo adjunto:</strong> <a href="' . esc_url( $archivo_id['url'] ) . '" target="_blank">' . esc_html( $archivo_id['filename'] ?? basename( $archivo_id['url'] ) ) . '</a></p>';
			}
		}

		$box_html .= '</div>';
	}

	// ------------------------------------------------------------------------
	// Documento
	// ------------------------------------------------------------------------
	if ( 'documento_obra' === $post_type ) {
		$archivo_id   = get_post_meta( $post_id, 'archivo_documento', true );
		$class_label  = function_exists( 'obras_get_post_class_label' ) ? obras_get_post_class_label( $post_id ) : '';

		$box_html .= '<div class="obras-acf-box documento">';
		$box_html .= '<h3>Datos del Documento</h3>';

		if ( ! empty( $class_label ) ) {
			$box_html .= '<p><strong>Clase del contenido:</strong> ' . esc_html( $class_label ) . '</p>';
		}

		if ( ! empty( $archivo_id ) && is_numeric( $archivo_id ) ) {
			$archivo_url = wp_get_attachment_url( $archivo_id );
			$box_html .= '<p><strong>📎 Archivo:</strong> <a href="' . esc_url( $archivo_url ) . '" target="_blank">' . esc_html( basename( $archivo_url ) ) . '</a></p>';
		} elseif ( is_array( $archivo_id ) && isset( $archivo_id['url'] ) ) {
			$box_html .= '<p><strong>📎 Archivo:</strong> <a href="' . esc_url( $archivo_id['url'] ) . '" target="_blank">' . esc_html( $archivo_id['filename'] ?? basename( $archivo_id['url'] ) ) . '</a></p>';
		}

		$box_html .= '</div>';
	}

	// ------------------------------------------------------------------------
	// Material
	// ------------------------------------------------------------------------
	if ( 'material_obra' === $post_type ) {
		$archivo_id  = get_post_meta( $post_id, 'archivo_recurso', true );
		$ubicacion   = get_post_meta( $post_id, 'ubicacion_fisica', true );
		$class_label = function_exists( 'obras_get_post_class_label' ) ? obras_get_post_class_label( $post_id ) : '';

		$box_html .= '<div class="obras-acf-box material">';
		$box_html .= '<h3>Datos del Material</h3>';

		if ( ! empty( $class_label ) ) {
			$box_html .= '<p><strong>Clase del contenido:</strong> ' . esc_html( $class_label ) . '</p>';
		}

		if ( ! empty( $ubicacion ) ) {
			$box_html .= '<p><strong>📍 Ubicación:</strong> ' . esc_html( $ubicacion ) . '</p>';
		}

		if ( ! empty( $archivo_id ) && is_numeric( $archivo_id ) ) {
			$archivo_url = wp_get_attachment_url( $archivo_id );
			$box_html .= '<p><strong>📎 Archivo:</strong> <a href="' . esc_url( $archivo_url ) . '" target="_blank">' . esc_html( basename( $archivo_url ) ) . '</a></p>';
		} elseif ( is_array( $archivo_id ) && isset( $archivo_id['url'] ) ) {
			$box_html .= '<p><strong>📎 Archivo:</strong> <a href="' . esc_url( $archivo_id['url'] ) . '" target="_blank">' . esc_html( $archivo_id['filename'] ?? basename( $archivo_id['url'] ) ) . '</a></p>';
		}

		$box_html .= '</div>';
	}

	// ------------------------------------------------------------------------
	// Catálogo
	// ------------------------------------------------------------------------
	if ( 'catalogo_obra' === $post_type ) {
		$archivo_id  = get_post_meta( $post_id, 'archivo_catalogo', true );
		$class_label = function_exists( 'obras_get_post_class_label' ) ? obras_get_post_class_label( $post_id ) : '';

		$box_html .= '<div class="obras-acf-box catalogo">';
		$box_html .= '<h3>Datos del Catálogo</h3>';

		if ( ! empty( $class_label ) ) {
			$box_html .= '<p><strong>Clase del contenido:</strong> ' . esc_html( $class_label ) . '</p>';
		}

		if ( ! empty( $archivo_id ) && is_numeric( $archivo_id ) ) {
			$archivo_url = wp_get_attachment_url( $archivo_id );
			$box_html .= '<p><strong>📎 Archivo:</strong> <a href="' . esc_url( $archivo_url ) . '" target="_blank">' . esc_html( basename( $archivo_url ) ) . '</a></p>';
		} elseif ( is_array( $archivo_id ) && isset( $archivo_id['url'] ) ) {
			$box_html .= '<p><strong>📎 Archivo:</strong> <a href="' . esc_url( $archivo_id['url'] ) . '" target="_blank">' . esc_html( $archivo_id['filename'] ?? basename( $archivo_id['url'] ) ) . '</a></p>';
		}

		$box_html .= '</div>';
	}

	// ------------------------------------------------------------------------
	// Plano
	// ------------------------------------------------------------------------
	if ( 'plano_obra' === $post_type ) {
		$archivo_id  = get_post_meta( $post_id, 'archivo_plano', true );
		$class_label = function_exists( 'obras_get_post_class_label' ) ? obras_get_post_class_label( $post_id ) : '';

		$box_html .= '<div class="obras-acf-box plano">';
		$box_html .= '<h3>Datos del Plano</h3>';

		if ( ! empty( $class_label ) ) {
			$box_html .= '<p><strong>Clase del contenido:</strong> ' . esc_html( $class_label ) . '</p>';
		}

		if ( ! empty( $archivo_id ) && is_numeric( $archivo_id ) ) {
			$archivo_url = wp_get_attachment_url( $archivo_id );
			$box_html .= '<p><strong>📎 Archivo:</strong> <a href="' . esc_url( $archivo_url ) . '" target="_blank">' . esc_html( basename( $archivo_url ) ) . '</a></p>';
		} elseif ( is_array( $archivo_id ) && isset( $archivo_id['url'] ) ) {
			$box_html .= '<p><strong>📎 Archivo:</strong> <a href="' . esc_url( $archivo_id['url'] ) . '" target="_blank">' . esc_html( $archivo_id['filename'] ?? basename( $archivo_id['url'] ) ) . '</a></p>';
		}

		$box_html .= '</div>';
	}

	return $content . $box_html . $nav_html;
}
