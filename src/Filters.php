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
        'docraptor_print' => __('Docraptor PDF (for print)', 'pressbooks'),
        'docraptor' => __('Docraptor PDF (for digital distribution)', 'pressbooks')
    ] + $formats['standard'];

    // unset($formats['standard']['pdf']); TODO Intelligently disable Prince export module.
    // unset($formats['standard']['print_pdf']); TODO Intelligently disable Prince export module.

    return $formats;
}
add_filter('pb_export_formats', __NAMESPACE__ . '\\add_to_formats');

/**
 * Add this module to the export batch currently in progress.
 *
 * @param array $modules an array of active export module classnames
 * @return array $modules
 */
function add_to_modules($modules)
{
    if (isset($_POST['export_formats']['docraptor'])) {
        $modules[] = '\PressbooksDocraptor\Export\Docraptor';
    }
    if (isset($_POST['export_formats']['docraptor_print'])) {
        $modules[] = '\PressbooksDocraptor\Export\DocraptorPrint';
    }
    return $modules;
}
add_filter('pb_active_export_modules', __NAMESPACE__ . '\\add_to_modules');
