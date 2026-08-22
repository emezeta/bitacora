<?php
/**
 * Enqueue scripts and styles.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'obras_enqueue_styles' ) ) {

	function obras_enqueue_styles() {

		$theme_style_path     = get_theme_file_path( '/style.css' );
		$custom_style_path    = get_theme_file_path( '/css/custom.css' );
		$land_style_path      = get_theme_file_path( '/css/landpage.css' );
		$dashboard_style_path = get_theme_file_path( '/css/dashboardfe.css' );
		$navigation_style_path = get_theme_file_path( '/css/navigation.css' );

		wp_enqueue_style(
			'bitacora-style',
			get_theme_file_uri( '/style.css' ),
			array(),
			file_exists( $theme_style_path ) ? filemtime( $theme_style_path ) : wp_get_theme()->get( 'Version' )
		);

		wp_enqueue_style(
			'obras-custom',
			get_theme_file_uri( '/css/custom.css' ),
			array( 'bitacora-style' ),
			file_exists( $custom_style_path ) ? filemtime( $custom_style_path ) : null
		);

		if ( is_front_page() ) {
			wp_enqueue_style(
				'obras-land',
				get_theme_file_uri( '/css/landpage.css' ),
				array( 'obras-custom' ),
				file_exists( $land_style_path ) ? filemtime( $land_style_path ) : null
			);
		}

		if ( is_user_logged_in() ) {
			wp_enqueue_style(
				'bitacora-navigation',
				get_theme_file_uri( '/css/navigation.css' ),
				array( 'obras-custom' ),
				file_exists( $navigation_style_path ) ? filemtime( $navigation_style_path ) : null
			);

			wp_enqueue_style(
				'obras-dashboardfe',
				get_theme_file_uri( '/css/dashboardfe.css' ),
				array( 'bitacora-navigation' ),
				file_exists( $dashboard_style_path ) ? filemtime( $dashboard_style_path ) : null
			);
		}
	}

	add_action( 'wp_enqueue_scripts', 'obras_enqueue_styles', 10 );
}

/**
 * Carga el componente de navegación Bitácora en las pantallas wp-admin
 * donde se utiliza: editor de contenido y Perfil.
 */
add_action(
    'admin_enqueue_scripts',
    'obras_enqueue_admin_navigation_styles',
    10
);

function obras_enqueue_admin_navigation_styles() {

    $screen = get_current_screen();

    if (
        ! $screen
        || ! in_array(
            $screen->base,
            array( 'post', 'profile' ),
            true
        )
    ) {
        return;
    }

    $navigation_style_path = get_theme_file_path(
        '/css/navigation.css'
    );

    wp_enqueue_style(
        'bitacora-navigation-admin',
        get_theme_file_uri( '/css/navigation.css' ),
        array(),
        file_exists( $navigation_style_path )
            ? filemtime( $navigation_style_path )
            : null
    );
}
