<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */
namespace PressbooksDocraptor\Export;

use Pressbooks\Modules\Export\Export;
use Pressbooks\Container;

class DocRaptor extends Export
{

    /**
     * Service URL
     *
     * @var string
     */
    public $url;


    /**
     * Fullpath to log file used by Prince.
     *
     * @var string
     */
    public $logfile;


    /**
     * Fullpath to book CSS file.
     *
     * @var string
     */
    protected $exportStylePath;


    /**
     * Fullpath to book JavaScript file.
     *
     * @var string
     */
    protected $exportScriptPath;


    /**
     * CSS overrides
     *
     * @var string
     */
    protected $cssOverrides;


    /**
     * @param array $args
     */
    public function __construct(array $args)
    {

        // Some defaults

        if (! defined('PB_DOCRAPTOR_API_KEY')) {
            define('PB_DOCRAPTOR_API_KEY', 'YOUR_API_KEY_HERE');
        }

        $this->exportStylePath = $this->getExportStylePath('prince');
        $this->exportScriptPath = $this->getExportScriptPath('prince');

        // Set the access protected "format/xhtml" URL with a valid timestamp and NONCE
        $timestamp = time();
        $md5 = $this->nonce($timestamp);
        $this->url = home_url() . "/format/xhtml?timestamp={$timestamp}&hashkey={$md5}";

        $this->themeOptionsOverrides();
    }


    /**
     * Create $this->outputPath
     *
     * @return bool
     */
    public function convert()
    {

        // Sanity check

        if (empty($this->exportStylePath) || ! is_file($this->exportStylePath)) {
            $this->logError('$this->exportStylePath must be set before calling convert().');

            return false;
        }

        // Configure service
        $configuration = \DocRaptor\Configuration::getDefaultConfiguration();
        if (defined('PB_DOCRAPTOR_API_KEY')) {
            $configuration->setUsername(PB_DOCRAPTOR_API_KEY);
        }

        // Set logfile
        $this->logfile = $this->createTmpFile();

        // Set filename
        $filename = $this->timestampedFileName('.pdf');
        $this->outputPath = $filename;

        // Fonts
        Container::get('GlobalTypography')->getFonts();

        // CSS File
        $css = $this->kneadCss();
        $css_file = $this->createTmpFile();
        file_put_contents($css_file, $css);

        // Save PDF as file in exports folder
        $docraptor = new \DocRaptor\DocApi();

        $doc = new \DocRaptor\Doc();
        $doc->setTest(true);
        $doc->setDocumentUrl($this->url);
        // TODO Handle stylesheet $css_file
        // TODO Handle scripts $this->exportScriptPath );
        $create_response = $docraptor->createDoc($doc);
        $retval = fopen($this->outputPath, 'wb');
        fwrite($retval, $create_response);
        fclose($retval);

        return $retval;
    }

    /**
     * Check the sanity of $this->outputPath
     *
     * @return bool
     */
    public function validate()
    {

        // Is this a PDF?
        if (! $this->isPdf($this->outputPath)) {
            $this->logError(file_get_contents($this->logfile));

            return false;
        }

        return true;
    }


    /**
     * Add $this->url as additional log info, fallback to parent.
     *
     * @param $message
     * @param array $more_info (unused, overridden)
     */
    public function logError($message, array $more_info = array())
    {

        $more_info = array(
            'url' => $this->url,
        );

        parent::logError($message, $more_info);
    }


    /**
     * Verify if body is actual PDF
     *
     * @param string $file
     *
     * @return bool
     */
    protected function isPdf($file)
    {

        $mime = static::mimeType($file);

        return ( strpos($mime, 'application/pdf') !== false );
    }

    protected function getPdfProfile()
    {
        if (defined('PB_PDF_PROFILE')) {
            return PB_PDF_PROFILE;
        }
        return null;
    }

    protected function getPdfOutputIntent()
    {
        if (defined('PB_PDF_OUTPUT_INTENT')) {
            return PB_PDF_OUTPUT_INTENT;
        }
        return null;
    }

    /**
     * Return kneaded CSS string
     *
     * @return string
     */
    protected function kneadCss()
    {

        $sass = Container::get('Sass');
        $scss_dir = pathinfo($this->exportStylePath, PATHINFO_DIRNAME);

        $scss = $sass->applyOverrides(file_get_contents($this->exportStylePath), $this->cssOverrides);

        if ($sass->isCurrentThemeCompatible(1)) {
            $css = $sass->compile($scss, [
                $sass->pathToUserGeneratedSass(),
                $sass->pathToPartials(),
                $sass->pathToFonts(),
                get_stylesheet_directory(),
            ]);
        } elseif ($sass->isCurrentThemeCompatible(2)) {
            $css = $sass->compile($scss, $sass->defaultIncludePaths('prince'));
        } else {
            $css = static::injectHouseStyles($scss);
        }

        // Search for url("*"), url('*'), and url(*)
        $url_regex = '/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i';
        $css = preg_replace_callback($url_regex, function ($matches) use ($scss_dir) {

            $url = $matches[3];

            if (preg_match('#^themes-book/pressbooks-book/fonts/[a-zA-Z0-9_-]+(\.woff|\.otf|\.ttf)$#i', $url)) {
                $my_asset = realpath(PB_PLUGIN_DIR . $url);
                if ($my_asset) {
                    return 'url(' . PB_PLUGIN_DIR . $url . ')';
                }
            } elseif (preg_match('#^uploads/assets/fonts/[a-zA-Z0-9_-]+(\.woff|\.otf|\.ttf)$#i', $url)) {
                $my_asset = realpath(WP_CONTENT_DIR . '/' . $url);
                if ($my_asset) {
                    return 'url(' . WP_CONTENT_DIR . '/' . $url . ')';
                }
            } elseif (! preg_match('#^https?://#i', $url)) {
                $my_asset = realpath("$scss_dir/$url");
                if ($my_asset) {
                    return "url($scss_dir/$url)";
                }
            }

            return $matches[0]; // No change
        }, $css);

        if (WP_DEBUG) {
            Container::get('Sass')->debug($css, $scss, 'prince');
        }

        return $css;
    }


    /**
     * Override based on Theme Options
     */
    protected function themeOptionsOverrides()
    {

        // --------------------------------------------------------------------
        // CSS

        $scss = '';
        $scss = apply_filters('pb_pdf_css_override', $scss) . "\n";

        // Copyright
        // Please be kind, help Pressbooks grow by leaving this on!
        if (empty($GLOBALS['PB_SECRET_SAUCE']['TURN_OFF_FREEBIE_NOTICES_PDF'])) {
            $freebie_notice = __('This book was produced using Pressbooks.com, and PDF rendering was done by PrinceXML.', 'pressbooks');
            $scss .= '#copyright-page .ugc > p:last-of-type::after { display:block; margin-top: 1em; content: "' . $freebie_notice . '" }' . "\n";
        }

        $this->cssOverrides = $scss;

        // --------------------------------------------------------------------
        // Hacks

        $hacks = array();
        $hacks = apply_filters('pb_pdf_hacks', $hacks);

        // Append endnotes to URL?
        if ('endnotes' == $hacks['pdf_footnotes_style']) {
            $this->url .= '&endnotes=true';
        }
    }


    /**
     * Dependency check.
     *
     * @return bool
     */
    public static function hasDependencies()
    {
        return true;
    }
}
