<?php
/**
 * Class DocraptorTest
 *
 * @package Pressbooks_Docraptor
 */

/**
 * Docraptor test case.
 */
class DocraptorTest extends WP_UnitTestCase {

	public function testHasDependencies() {
		$return = \PressbooksDocraptor\Modules\Export\Docraptor\Docraptor::hasDependencies();
		$this->assertInternalType( 'boolean', $return );
	}
}
