<?php
/**
 * Activate the child theme and create the local Home page.
 * Run: php -d extension=mysqli scripts/setup-local-homepage.php
 * Copy the child theme into the target WordPress installation before running.
 */

if ( PHP_SAPI !== 'cli' ) {
	http_response_code( 403 );
	exit( 'This setup command is available only from the command line.' );
}

$repository_root = dirname( __DIR__ );
$wordpress_root  = realpath( $argv[1] ?? 'C:/wamp64/www/badcreditautoloans/wordpress' );

if ( ! $wordpress_root || ! is_file( $wordpress_root . '/wp-config.php' ) ) {
	fwrite( STDERR, "A configured local WordPress installation is required.\n" );
	exit( 1 );
}

$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/badcreditautoloans/wordpress/';
define( 'DISABLE_WP_CRON', true );
require $wordpress_root . '/wp-load.php';

if ( ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1', '::1' ), true ) ) {
	fwrite( STDERR, "This command only changes a localhost WordPress site.\n" );
	exit( 1 );
}

$theme_slug = 'badcreditautoloans-child';
wp_clean_themes_cache();
$theme = wp_get_theme( $theme_slug );
if ( ! $theme->exists() || $theme->errors() ) {
	fwrite( STDERR, "Install the complete child theme and Twenty Twenty-Five parent first.\n" );
	exit( 1 );
}

$page = get_page_by_path( 'home', OBJECT, 'page' );
if ( $page && ! get_post_meta( $page->ID, '_bc_auto_homepage', true ) ) {
	fwrite( STDERR, "An existing Home page was found. It has been left untouched.\n" );
	exit( 1 );
}

$administrator_ids = get_users( array( 'role' => 'administrator', 'fields' => 'ID', 'number' => 1 ) );
if ( empty( $administrator_ids ) ) {
	fwrite( STDERR, "No local administrator is available to create the page.\n" );
	exit( 1 );
}
wp_set_current_user( (int) $administrator_ids[0] );

$state_directory = $repository_root . '/.local';
if ( ! is_dir( $state_directory ) && ! mkdir( $state_directory, 0777, true ) ) {
	fwrite( STDERR, "Could not create the local setup-state directory.\n" );
	exit( 1 );
}
$state_file = $state_directory . '/homepage-before.json';
if ( ! file_exists( $state_file ) ) {
	$previous_state = array();
	foreach ( array( 'template', 'stylesheet', 'show_on_front', 'page_on_front', 'page_for_posts' ) as $option_name ) {
		$previous_state[ $option_name ] = get_option( $option_name );
	}
	if ( false === file_put_contents( $state_file, wp_json_encode( $previous_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" ) ) {
		fwrite( STDERR, "Could not save the previous local homepage settings.\n" );
		exit( 1 );
	}
}

if ( get_stylesheet() !== $theme_slug ) {
	switch_theme( $theme_slug );
}
if ( ! $page ) {
	ob_start();
	include get_stylesheet_directory() . '/patterns/homepage.php';
	$homepage_content = ob_get_clean();
	$page_id = wp_insert_post(
		array(
			'post_title'   => 'Home',
			'post_name'    => 'home',
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_content' => wp_slash( $homepage_content ),
			'meta_input'   => array( '_bc_auto_homepage' => '1' ),
		),
		true
	);
	if ( is_wp_error( $page_id ) ) {
		fwrite( STDERR, $page_id->get_error_message() . "\n" );
		exit( 1 );
	}
	$page = get_post( $page_id );
}

// Re-running the command preserves subsequent edits to this page's content.
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', (int) $page->ID );
flush_rewrite_rules( false );

$export_directory = $repository_root . '/content';
if ( ! is_dir( $export_directory ) && ! mkdir( $export_directory, 0777, true ) ) {
	fwrite( STDERR, "The homepage is active, but the content export directory could not be created.\n" );
	exit( 1 );
}
$export = array(
	'format_version' => 1,
	'post_type'      => 'page',
	'title'          => $page->post_title,
	'slug'           => $page->post_name,
	'status'         => $page->post_status,
	'content'        => str_replace( get_stylesheet_directory_uri(), '{{theme_url}}', $page->post_content ),
);
if ( false === file_put_contents( $export_directory . '/home.json', wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n" ) ) {
	fwrite( STDERR, "The homepage is active, but its structured export could not be written.\n" );
	exit( 1 );
}

echo wp_json_encode(
	array(
		'page_id'        => $page->ID,
		'homepage'       => home_url( '/' ),
		'active_theme'   => get_stylesheet(),
		'content_export' => 'content/home.json',
		'previous_state' => '.local/homepage-before.json',
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
) . "\n";
