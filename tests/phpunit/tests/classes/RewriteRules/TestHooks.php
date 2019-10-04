<?php

namespace WCML\RewriteRules;

/**
 * @group rewrite-rules
 */
class TestHooks extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldLoadOnBackAndFrontWithDic() {
		$subject = $this->getSubject();

		$this->assertInstanceOf( '\IWPML_Backend_Action', $subject );
		$this->assertInstanceOf( '\IWPML_Frontend_Action', $subject );
		$this->assertInstanceOf( '\IWPML_DIC_Action', $subject );
	}

	/**
	 * @test
	 */
	public function itShouldAddHooks() {
		$subject = $this->getSubject();

		\WP_Mock::expectFilterAdded(
			'option_woocommerce_queue_flush_rewrite_rules',
			[ $subject, 'preventFlushInNonDefaultLang' ]
		);

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldNotAlterWoocommerceQueueFlushOptionIfNotYes() {
		$subject = $this->getSubject();

		$this->assertEquals( 'foo', $subject->preventFlushInNonDefaultLang( 'foo' ) );
	}

	/**
	 * @test
	 */
	public function itShouldNotAlterWoocommerceQueueFlushOptionIfCurrentLanguageIsDefault() {
		$lang = 'fr';

		$sitepress = $this->getSitepress();
		$sitepress->method( 'get_current_language' )->willReturn( $lang );
		$sitepress->method( 'get_default_language' )->willReturn( $lang );

		$subject = $this->getSubject( $sitepress );

		$this->assertEquals( 'yes', $subject->preventFlushInNonDefaultLang( 'yes' ) );
	}

	/**
	 * @test
	 */
	public function itShouldAlterWoocommerceQueueFlushOptionIfCurrentLanguageIsNotDefault() {
		$sitepress = $this->getSitepress();
		$sitepress->method( 'get_current_language' )->willReturn( 'en' );
		$sitepress->method( 'get_default_language' )->willReturn( 'fr' );

		$subject = $this->getSubject( $sitepress );

		$this->assertEquals( 'no', $subject->preventFlushInNonDefaultLang( 'yes' ) );
	}

	/**
	 * @param null|\PHPUnit_Framework_MockObject_MockObject|\SitePress $sitepress
	 *
	 * @return Hooks
	 */
	private function getSubject( $sitepress = null ) {
		$sitepress = $sitepress ?: $this->getSitepress();
		return new Hooks( $sitepress );
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|\SitePress
	 */
	private function getSitepress() {
		return $this->getMockBuilder( '\SitePress' )
			->setMethods(
				[
					'get_current_language',
					'get_default_language',
				]
			)->disableOriginalConstructor()->getMock();
	}
}
