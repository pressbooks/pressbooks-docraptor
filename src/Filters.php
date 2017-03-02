<?php namespace PressbooksDocraptor\Filters;

function add_to_formats($formats)
{
    // unset($formats['standard']['pdf']); TODO
    // unset($formats['standard']['print_pdf']); TODO
    $formats['standard'] = ['docraptor_print' => __('Docraptor PDF (for print)', 'pressbooks'), 'docraptor' => __('Docraptor PDF (for digital distribution)', 'pressbooks'),] + $formats['standard'];
    return $formats;
}
add_filter('pb_export_formats', __NAMESPACE__ . '\\add_to_formats');

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
