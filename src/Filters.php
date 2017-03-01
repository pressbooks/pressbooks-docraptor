<?php namespace PressbooksDocraptor\Filters;

function add_to_formats($formats)
{
        array_splice($formats['standard'], 2, 0, array( 'docraptor' => __('PDF (Docraptor)', 'pressbooks') ));
        return $formats;
}
add_filter('pb_export_formats', __NAMESPACE__ . '\\add_to_formats');

function add_to_modules($modules)
{
    if (isset($_POST['export_formats']['docraptor'])) {
        $modules[] = '\PressbooksDocraptor\Export\Docraptor';
    }
    return $modules;
}
add_filter('pb_active_export_modules', __NAMESPACE__ . '\\add_to_modules');
