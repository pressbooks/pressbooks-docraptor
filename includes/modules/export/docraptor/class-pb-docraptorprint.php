<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */
namespace PressbooksDocraptor\Modules\Export\Docraptor;

use \Pressbooks\Modules\Export\Export;
use \Pressbooks\Container;

class DocraptorPrint extends Docraptor
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
        $this->pdfProfile = 'PDF/X-1a:2003';
        $this->pdfOutputIntent = plugins_url('pressbooks-docraptor/assets/icc/USWebCoatedSWOP.icc');

        // Set the access protected "format/xhtml" URL with a valid timestamp and NONCE
        $timestamp = time();
        $md5 = $this->nonce($timestamp);
        $this->url = home_url() . "/format/xhtml?timestamp={$timestamp}&hashkey={$md5}&style=docraptor";

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
        $filename = $this->timestampedFileName('._print.pdf');
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

        try {
            $doc = new \DocRaptor\Doc();
            if (WP_ENV == 'production') {
                $doc->setTest(false);
            } else {
                $doc->setTest(true);
            }
            $doc->setDocumentUrl($this->url);
            $doc->setName(get_bloginfo('name'));
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
     * Override based on Theme Options
     */
    protected function themeOptionsOverrides()
    {

        $sass = \Pressbooks\Container::get('Sass');

        if ($sass->isCurrentThemeCompatible(2)) {
            $extra = "/* Print Overrides */\n\$prince-image-resolution: 300dpi; \n";
        } else {
            $extra = "/* Print Overrides */\nimg { prince-image-resolution: 300dpi; } \n";
        }

        $icc = $this->pdfOutputIntent;

        $extra .= "@prince-pdf { prince-pdf-output-intent: url('$icc'); } \n";

        $scss = '';
        $scss = apply_filters('pb_pdf_css_override', $scss) . "\n";

        $scss = $sass->applyOverrides($scss, $extra);

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
}
