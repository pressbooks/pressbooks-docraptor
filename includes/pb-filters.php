<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */
namespace PressbooksDocraptor\Filters;

/**
 * Add this format to the export page formats list.
 *
 * @param array $formats a multidimensional array of standard and exotic formats
 * @return array $formats
 */
function add_to_formats($formats)
{
    $formats['standard'] = [
        'docraptor_print' => __('PDF (for print)', 'pressbooks'),
        'docraptor' => __('PDF (for digital distribution)', 'pressbooks')
    ] + $formats['standard'];

    unset($formats['standard']['pdf']);
    unset($formats['standard']['print_pdf']);

    return $formats;
}
add_filter('pb_export_formats', 'PressbooksDocraptor\Filters\add_to_formats');

/**
 * Hide Prince dependency errors if DocRaptor is enabled.
 *
 * @param array $dependency_errors an array of formats
 * @return array $dependency_errors
 */
function hide_prince_errors($dependency_errors)
{
    unset($dependency_errors['pdf']);
    unset($dependency_errors['print_pdf']);

    return $dependency_errors;
}
add_filter('pb_dependency_errors', 'PressbooksDocraptor\Filters\hide_prince_errors');

/**
 * Make sure the PDF options tab is shown even if Prince is not installed.
 *
 * @param array $tabs And array of tabs, e.g. 'format' => '\Pressbooks\Modules\ThemeOptions\FormatOptions'
 * @return array $tabs
 */
function register_pdf_options_tab($tabs)
{
    $tmp = [
        'global' => '\Pressbooks\Modules\ThemeOptions\GlobalOptions',
        'web' => '\Pressbooks\Modules\ThemeOptions\WebOptions',
        'pdf' => '\Pressbooks\Modules\ThemeOptions\PDFOptions',
    ];

    return array_merge($tmp, $tabs);
}
add_filter('pb_theme_options_tabs', 'PressbooksDocraptor\Filters\register_pdf_options_tab');

/**
 * Add this module to the export batch currently in progress.
 *
 * @param array $modules an array of active export module classnames
 * @return array $modules
 */
function add_to_modules($modules)
{
    if (isset($_POST['export_formats']['docraptor'])) {
        $modules[] = '\PressbooksDocraptor\Modules\Export\Docraptor\Docraptor';
    }
    if (isset($_POST['export_formats']['docraptor_print'])) {
        $modules[] = '\PressbooksDocraptor\Modules\Export\Docraptor\DocraptorPrint';
    }
    return $modules;
}
add_filter('pb_active_export_modules', 'PressbooksDocraptor\Filters\add_to_modules');
