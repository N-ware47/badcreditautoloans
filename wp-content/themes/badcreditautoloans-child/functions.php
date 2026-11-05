<?php
/**
 * Assets and editor support for the Bad Credit Auto Loans child theme.
 *
 * @package BadCreditAutoLoans
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		$stylesheet = get_stylesheet_directory() . '/style.css';
		wp_enqueue_style(
			'bc-auto-child',
			get_stylesheet_uri(),
			array( 'twentytwentyfive-style' ),
			(string) filemtime( $stylesheet )
		);

		wp_enqueue_script(
			'bc-auto-homepage',
			get_stylesheet_directory_uri() . '/assets/js/homepage.js',
			array(),
			(string) filemtime( get_stylesheet_directory() . '/assets/js/homepage.js' ),
			array( 'in_footer' => true, 'strategy' => 'defer' )
		);
	},
	20
);

add_action(
	'after_setup_theme',
	function () {
		add_editor_style( 'style.css' );
	}
);

add_action(
	'wp_head',
	function () {
		if ( is_front_page() ) {
			echo '<meta name="description" content="A clearer starting point for your next car. Explore a payment estimate, understand the basics, and plan your next step with Bad Credit Auto Loans.">' . "\n";
		}
	}
);

add_filter(
	'pre_get_document_title',
	function ( $title ) {
		return is_front_page() ? 'Bad Credit Auto Loans | A fresh start. A better drive.' : $title;
	}
);
