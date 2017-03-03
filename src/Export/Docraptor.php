<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */
namespace PressbooksDocraptor\Export;

use \Pressbooks\Modules\Export\Export;
use \Pressbooks\Container;
use DocRaptor\Doc;
use DocRaptor\ApiException;

class Docraptor extends Export
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
        $this->exportStylePath = $this->getExportStylePath('prince');
        $this->exportScriptPath = $this->getExportScriptPath('prince');
        $this->pdfProfile = $this->getPdfProfile();
        $this->pdfOutputIntent = $this->getPdfOutputIntent();

        // Set the access protected "format/xhtml" URL with a valid timestamp and NONCE
        $timestamp = time();
        $md5 = $this->nonce($timestamp);
        $this->url = home_url() . "/format/xhtml?timestamp={$timestamp}&hashkey={$md5}&style=docraptor&script=prince";

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

        // CSS
        $css = $this->kneadCss();
        $css_file = \Pressbooks\Container::get('Sass')->pathToUserGeneratedCss() . '/docraptor.css';
        file_put_contents($css_file, $css);

        // Save PDF as file in exports folder
        $docraptor = new \DocRaptor\DocApi();
        $prince_options = new \DocRaptor\PrinceOptions();
        $prince_options->setProfile($this->pdfProfile);
        $prince_options->setBaseurl(home_url());

        try {
            $doc = new \DocRaptor\Doc();
            if (WP_ENV == 'development' || WP_ENV == 'staging') {
                $doc->setTest(true);
            } else {
                $doc->setTest(false);
            }
            $doc->setDocumentUrl($this->url);
            $doc->setPrinceOptions($prince_options);
            $create_response = $docraptor->createAsyncDoc($doc);

            $done = false;

            while (!$done) {
                $status_response = $docraptor->getAsyncDocStatus($create_response->getStatusId());
                switch ($status_response->getStatus()) {
                    case 'completed':
                        $doc_response = $docraptor->getAsyncDoc($status_response->getDownloadId());
                        $retval = fopen($this->outputPath, 'wb');
                        fwrite($retval, $doc_response);
                        fclose($retval);
                        $done = true;
                        break;
                    case 'failed':
                        wp_die($status_response);
                        $done = true;
                        break;
                    default:
                        sleep(1);
                }
            }
        } catch (\DocRaptor\ApiException $exception) {
            $message = "<h1>{$exception->getMessage()}</h1><p>{$exception->getCode()}</p><p>{$exception->getResponseBody()}</p>";
            wp_die($message);
        }

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
                    return 'url(' . PB_PLUGIN_URL . $url . ')';
                }
            } elseif (preg_match('#^uploads/assets/fonts/[a-zA-Z0-9_-]+(\.woff|\.otf|\.ttf)$#i', $url)) {
                $my_asset = realpath(WP_CONTENT_DIR . '/' . $url);
                if ($my_asset) {
                    return 'url(' . WP_CONTENT_URL . '/' . $url . ')';
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
