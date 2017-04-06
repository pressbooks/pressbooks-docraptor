<?php
/*
Plugin Name: Docraptor for Pressbooks
Plugin URI: https://pressbooks.org
Description: Docraptor exporter for Pressbooks.
Version: 1.0.0-RC4
Author: Pressbooks (Book Oven Inc.)
Author URI: https://pressbooks.org
Text Domain: pressbooks-docraptor
License: GPLv2
GitHub Plugin URI: https://github.com/pressbooks/pressbooks-docraptor
Release Asset: true
*/

if (! class_exists('\\DocRaptor\\Doc')) {
    if (file_exists($autoloader = dirname(__FILE__) . '/vendor/autoload.php')) {
        require_once($autoloader);
    } else {
        $title = __('Dependencies Missing', 'pressbooks-docraptor');
        $body = __('Please run <code>composer install</code> from the root of the Docraptor for Pressbooks plugin directory.', 'pressbooks-docraptor');
        $message = "<h1>{$title}</h1><p>{$body}</p>";
        wp_die($message, $title);
    }
}

// -------------------------------------------------------------------------------------------------------------------
// Class autoloader
// -------------------------------------------------------------------------------------------------------------------

function _pressbooks_docraptor_autoload($class_name)
{

    $prefix = 'PressbooksDocraptor\\';
    $len = strlen($prefix);
    if (strncasecmp($prefix, $class_name, $len) !== 0) {
        // Ignore classes not in our namespace
        return;
    }

    $parts = explode('\\', strtolower($class_name));
    array_shift($parts);
    $class_file = 'class-pb-' . str_replace('_', '-', array_pop($parts)) . '.php';
    $path = count($parts) ? implode('/', $parts) . '/' : '';
    @include(dirname(__FILE__) . '/includes/' . $path . $class_file);
}

spl_autoload_register('_pressbooks_docraptor_autoload');

add_action('init', function () {
    if (! @include_once(WP_PLUGIN_DIR . '/pressbooks/compatibility.php')) {
        add_action('admin_notices', function () {
            printf(
                '<div id="message" class="error fade"><p>%s</p></div>',
                __('Docraptor for Pressbooks cannot find a Pressbooks install.', 'pressbooks-docraptor')
            );
        });
        return;
    } elseif (! version_compare(PB_PLUGIN_VERSION, '3.9.8', '>=')) {
        add_action('admin_notices', function () {
            printf(
                '<div id="message" class="error fade"><p>%s</p></div>',
                __('Docraptor for Pressbooks requires Pressbooks 3.9.8 or greater.', 'pressbooks-docraptor')
            );
        });
        return;
    } else {
        require_once(dirname(__FILE__) . '/includes/pb-filters.php');
    }
});
