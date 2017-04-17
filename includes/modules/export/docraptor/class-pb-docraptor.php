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

class Docraptor extends Export
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
     * Full path to book's JavaScript file.
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
     * Create $this->outputPath.
     *
     * @return bool|string
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
        $configuration->setUsername(DOCRAPTOR_API_KEY);

        // Set logfile
        $this->logfile = $this->createTmpFile();

        // Set filename
        $filename = $this->timestampedFileName($this->fileExtension);
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
            if (WP_ENV == 'development') {
                $response = wp_remote_get($this->url);
                $document_content = str_replace('</head>', "<style>$css</style></head>", $response['body']);
                $doc->setTest(true);
                $doc->setDocumentContent($document_content);
            } else {
                $doc->setTest(false);
                $doc->setDocumentUrl($this->url);
            }
            $doc->setName(get_bloginfo('name'));
            $doc->setPrinceOptions($prince_options);
            $create_response = $docraptor->createAsyncDoc($doc);
            $done = false;

            while (!$done) {
                $status_response = $docraptor->getAsyncDocStatus($create_response->getStatusId());
                switch ($status_response->getStatus()) {
                    case 'completed':
                        if (!function_exists('download_url')) {
                            require_once(ABSPATH . 'wp-admin/includes/file.php');
                        }
                        $result = \download_url($status_response->getDownloadUrl());
                        if (is_wp_error($result)) {
                            $_SESSION['pb_errors'][] = __('Your PDF could not be retrieved.', 'pressbooks-docraptor');
                            $retval = false;
                        } else {
                            copy($result, $this->outputPath);
                            unlink($result);
                            $retval = $this->outputPath;
                        }
                        $done = true;
                        $msg = $this->getDetailedLog($create_response->getStatusId());
                        file_put_contents($this->logfile, $msg);
                        break;
                    case 'failed':
                        file_put_contents($this->logfile, $status_response);
                        $done = true;
                        $retval = false;
                        break;
                    default:
                        sleep(1);
                }
            }
        } catch (\DocRaptor\ApiException $exception) {
            $msg = $exception->getResponseBody();
            file_put_contents($this->logfile, $msg);
        }

        if ($msg) {
            $this->logError(file_get_contents($this->logfile));
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
     * Verify if body is actual PDF.
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

    /**
     * When given a DocRaptor async status ID, return the document generation log for the relevant job.
     *
     * @param string $id
     *
     * @return string
     */
    protected function getDetailedLog($id)
    {
        $response = wp_remote_get(esc_url('https://docraptor.com/doc_logs.json?user_credentials=' . DOCRAPTOR_API_KEY));
        $logs = json_decode($response['body']);
        foreach ($logs as $log) {
            if ($log->status_id == $id) {
                return $log->generation_log;
            }
        }

        return false;
    }

    /**
     * Return the output file extension.
     *
     * @return string
     */
    protected function getFileExtension()
    {
        return '.pdf';
    }

    /**
     * Return the desired PDF profile.
     *
     * @return string
     */
    protected function getPdfProfile()
    {
        if (defined('PB_PDF_PROFILE')) {
            return PB_PDF_PROFILE;
        }
        return null;
    }

    /**
     * Return the desired PDF output intent.
     *
     * @return string
     */
    protected function getPdfOutputIntent()
    {
        if (defined('PB_PDF_OUTPUT_INTENT')) {
            return PB_PDF_OUTPUT_INTENT;
        }
        return null;
    }

    /**
     * Return kneaded CSS string.
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
     * Override based on Theme Options.
     */
    protected function themeOptionsOverrides()
    {

        // --------------------------------------------------------------------
        // CSS

        $scss = '';
        $scss = apply_filters('pb_pdf_css_override', $scss) . "\n";

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


    /**
     * Dependency check.
     *
     * @return bool
     */
    public static function hasDependencies()
    {
        if (false !== \Pressbooks\Utility\check_xmllint_install()) {
            return true;
        }

        return false;
    }
}
