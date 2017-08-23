<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */

namespace PressbooksDocraptor\Modules\Export\Docraptor;

class DocraptorPrint extends Docraptor {
	/**
	 * Constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args ) {
		parent::__construct( $args );
		$this->url .= '&fullsize-images=1';
	}

	/**
	 * @return string
	 */
	protected function generateFileName() {
		return $this->timestampedFileName( '._print.pdf' );
	}

	/**
	 * Return the desired PDF profile.
	 *
	 * @return string
	 */
	protected function getPdfProfile() {
		return 'PDF/X-1a:2003';
	}

	/**
	 * Return the desired PDF output intent.
	 *
	 * @return string
	 */
	protected function getPdfOutputIntent() {
		return plugins_url( 'pressbooks-docraptor/assets/icc/USWebCoatedSWOP.icc' );
	}
}
