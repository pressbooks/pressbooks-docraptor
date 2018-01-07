<?php
/*
Plugin Name: Docraptor for Pressbooks
Plugin URI: https://pressbooks.org
Description: Docraptor exporter for Pressbooks.
Version: 2.1.0
Author: Pressbooks (Book Oven Inc.)
Author URI: https://pressbooks.org
Text Domain: pressbooks-docraptor
License: GPLv2
Requires PHP: 7.0
Network: true
*/

// -------------------------------------------------------------------------------------------------------------------
// Check requirements
// -------------------------------------------------------------------------------------------------------------------

if ( ! function_exists( 'pb_meets_minimum_requirements' ) && ! @include_once( WP_PLUGIN_DIR . '/pressbooks/compatibility.php' ) ) { // @codingStandardsIgnoreLine
	add_action('admin_notices', function () {
		echo '<div id="message" class="error fade"><p>' . __( 'Cannot find Pressbooks install.', 'pressbooks-cg' ) . '</p></div>';
	});
	return;
} elseif ( ! pb_meets_minimum_requirements() ) {
	return;
}

// -------------------------------------------------------------------------------------------------------------------
// Class autoloader
// -------------------------------------------------------------------------------------------------------------------

\HM\Autoloader\register_class_path( 'PressbooksDocraptor', __DIR__ . '/inc' );

// -------------------------------------------------------------------------------------------------------------------
// Composer autoloader
// -------------------------------------------------------------------------------------------------------------------

if ( ! class_exists( '\DocRaptor\Doc' ) ) {
	if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		require_once __DIR__ . '/vendor/autoload.php';
	} else {
		$title = __( 'Dependencies Missing', 'pressbooks-docraptor' );
		$body = __( 'Please run <code>composer install</code> from the root of the Pressbooks Cover Generator plugin directory.', 'pressbooks-docraptor' );
		$message = "<h1>{$title}</h1><p>{$body}</p>";
		wp_die( $message, $title );
	}
}

// -------------------------------------------------------------------------------------------------------------------
// Requires
// -------------------------------------------------------------------------------------------------------------------

require( __DIR__ . '/inc/filters/namespace.php' );

// -------------------------------------------------------------------------------------------------------------------
// Check for updates
// -------------------------------------------------------------------------------------------------------------------

if ( ! \Pressbooks\Book::isBook() ) {
	$updater = Puc_v4_Factory::buildUpdateChecker(
		'https://github.com/pressbooks/pressbooks-docraptor/',
		__FILE__, // Fully qualified path to the main plugin file
		'pressbooks-docraptor',
		24
	);
	$updater->setBranch( 'master' );
	$updater->getVcsApi()->enableReleaseAssets();
}

// -------------------------------------------------------------------------------------------------------------------
// Hooks
// -------------------------------------------------------------------------------------------------------------------

add_action( 'init', function () {
	add_filter( 'pb_export_formats', '\PressbooksDocraptor\Filters\add_to_formats' );
	add_filter( 'pb_dependency_errors', '\PressbooksDocraptor\Filters\hide_prince_errors' );
	add_filter( 'pb_theme_options_tabs', '\PressbooksDocraptor\Filters\register_pdf_options_tab' );
	add_filter( 'pb_active_export_modules', '\PressbooksDocraptor\Filters\add_to_modules' );
} );
