<?php
/*
Plugin Name: Docraptor for Pressbooks
Plugin URI: https://pressbooks.org
Description: Docraptor exporter for Pressbooks.
Version: 1.0.0-dev
Author: Pressbooks (Book Oven Inc.)
Author URI: https://pressbooks.org
Text Domain: pressbooks-docraptor
License: GPLv2
*/

if (! class_exists('\\PressbooksDocraptor\\Export\\Docraptor')) {
    if (file_exists($autoloader = dirname(__FILE__) . '/vendor/autoload.php')) {
        require_once($autoloader);
    } else {
        wp_die('Dependencies missing.'); // TODO
    }
}

require_once(dirname(__FILE__) . '/src/Actions.php');
require_once(dirname(__FILE__) . '/src/Filters.php');
