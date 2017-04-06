<?php
/**
 * Class DocraptorTest
 *
 * @package Pressbooks_Docraptor
 */

/**
 * Docraptor test case.
 */
class DocraptorTest extends WP_UnitTestCase // @codingStandardsIgnoreLine
{

    /**
     * @covers \PressbooksDocraptor\Modules\Export\DocRaptor\Docraptor::hasDependencies
     */
    public function testHasDependencies()
    {
        $this->assertTrue(\PressbooksDocraptor\Modules\Export\DocRaptor\Docraptor::hasDependencies());
    }
}
