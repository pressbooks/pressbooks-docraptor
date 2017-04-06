<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */
namespace PressbooksDocraptor\Modules\Export\Docraptor;

use \Pressbooks\Modules\Export\Export;
use \Pressbooks\Container;
use DocRaptor\Doc;
use DocRaptor\ApiException;

class DocraptorPrint extends Docraptor
{

    /**
     * Service URL.
     *
     * @var string
     */
    public $url;


    /**
     * File extension.
     *
     * @var string
     */
    public $fileExtension;


    /**
     * Full path to the DocRaptor generation log file.
     *
     * @var string
     */
    public $logfile;


    /**
     * Full path to the book's CSS file.
     *
     * @var string
     */
    protected $exportStylePath;


    /**
     * Fullpath to the book's JavaScript file.
     *
     * @var string
     */
    protected $exportScriptPath;


    /**
     * CSS overrides.
     *
     * @var string
     */
    protected $cssOverrides;


    /**
     * Constructor.
     *
     * @param array $args
     */
    public function __construct(array $args)
    {

        // Some defaults

        if (! defined('DOCRAPTOR_API_KEY')) {
            define('DOCRAPTOR_API_KEY', 'YOUR_API_KEY_HERE');
        }

        $this->exportStylePath = $this->getExportStylePath('prince');
        $this->exportScriptPath = $this->getExportScriptPath('prince');
        $this->fileExtension = $this->getFileExtension();
        $this->pdfProfile = $this->getPdfProfile();
        $this->pdfOutputIntent = $this->getPdfOutputIntent();

        // Set the access protected "format/xhtml" URL with a valid timestamp and NONCE
        $timestamp = time();
        $md5 = $this->nonce($timestamp);
        $this->url = home_url() . "/format/xhtml?timestamp={$timestamp}&hashkey={$md5}&style=docraptor&script=prince";

        $this->themeOptionsOverrides();
    }

    /**
     * Return the output file extension.
     *
     * @return string
     */
    protected function getFileExtension()
    {
        return '._print.pdf';
    }

    /**
     * Return the desired PDF profile.
     *
     * @return string
     */
    protected function getPdfProfile()
    {
        return 'PDF/X-1a:2003';
    }

    /**
     * Return the desired PDF output intent.
     *
     * @return string
     */
    protected function getPdfOutputIntent()
    {
        return plugins_url('pressbooks-docraptor/assets/icc/USWebCoatedSWOP.icc');
    }

    /**
    * Override based on Theme Options.
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

        // Please be kind, help Pressbooks grow by leaving this on!
        if (empty($GLOBALS['PB_SECRET_SAUCE']['TURN_OFF_FREEBIE_NOTICES_PDF'])) {
            $freebie_notice = __(
                'This book was produced using Pressbooks, with PDF rendering by DocRaptor.',
                'pressbooks-docraptor'
            );
            $scss .= sprintf(
                "#copyright-page .ugc > p:last-of-type::after { display:block; margin-top: 1em; content: '%s'; }\n",
                $freebie_notice
            );
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
