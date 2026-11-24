<?php
/**
 * IAW Hijo Theme functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package iaw-hijo
 */

add_action( 'wp_enqueue_scripts', 'iaw_hijo_parent_theme_enqueue_styles' );

/**
 * Enqueue scripts and styles.
 */
function iaw_hijo_parent_theme_enqueue_styles() {
	wp_enqueue_style( 'twentytwentyfive-style', get_template_directory_uri() . '/style.css', array(), '0.1.0' );
	wp_enqueue_style(
		'iaw-hijo-style',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'twentytwentyfive-style' ),
		'0.1.0'
	);
}

// Permitir peticiones HTTP a IPs locales/LAN del aula para el bloque RSS de la pareja
add_filter( 'http_request_host_is_external', '__return_true' );
