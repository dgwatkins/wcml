<?php

namespace WCML\AdminNotices;

use OTGS_TestCase;
use woocommerce_wpml;
use WP_Mock;
use WPML_Notice;
use WPML_Notices;

/**
 * @group admin-notices
 */
class TestMultiCurrencyMissing extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldIncludeAddNoticeHook() {
		 WP_Mock::userFunction( 'wcml_is_multi_currency_on', [ 'return' => true ] );

		$subject = $this->getSubject( null, 1 );

		WP_Mock::expectActionAdded( 'admin_init', [ $subject, 'addNotice' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldNotIncludeAddNoticeHookIfNotUsingMultiCurrency() {
		WP_Mock::userFunction( 'wcml_is_multi_currency_on', [ 'return' => false ] );

		$subject = $this->getSubject( null, 1 );

		WP_Mock::expectActionNotAdded( 'admin_init', [ $subject, 'addNotice' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldNotIncludeAddNoticeHookIfAlreadyHasNotice() {
		WP_Mock::userFunction( 'wcml_is_multi_currency_on', [ 'return' => true ] );

		$notice  = $this->getNotice();
		$subject = $this->getSubject( $notice, 1 );

		WP_Mock::expectActionNotAdded( 'admin_init', [ $subject, 'addNotice' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldNotIncludeAddNoticeHookIfHasMultipleCurrencies() {
		WP_Mock::userFunction( 'wcml_is_multi_currency_on', [ 'return' => true ] );

		$subject = $this->getSubject( null, 2 );

		WP_Mock::expectActionNotAdded( 'admin_init', [ $subject, 'addNotice' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldIncludeRemoveNoticeHook() {
		 WP_Mock::userFunction( 'wcml_is_multi_currency_on', [ 'return' => true ] );

		$notice  = $this->getNotice();
		$subject = $this->getSubject( $notice, 2 );

		WP_Mock::expectActionAdded( 'admin_init', [ $subject, 'removeNotice' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldNotIncludeRemoveNoticeHookIfNotUsingMultiCurrency() {
		WP_Mock::userFunction( 'wcml_is_multi_currency_on', [ 'return' => false ] );

		$subject = $this->getSubject( null, 1 );

		WP_Mock::expectActionNotAdded( 'admin_init', [ $subject, 'removeNotice' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldNotIncludeRemoveNoticeHookIfDoesNotHaveNotice() {
		WP_Mock::userFunction( 'wcml_is_multi_currency_on', [ 'return' => true ] );

		$subject = $this->getSubject( null, 2 );

		WP_Mock::expectActionNotAdded( 'admin_init', [ $subject, 'removeNotice' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldNotIncludeRemoveNoticeHookIfHasOneCurrency() {
		WP_Mock::userFunction( 'wcml_is_multi_currency_on', [ 'return' => true ] );

		$notice  = $this->getNotice();
		$subject = $this->getSubject( $notice, 1 );

		WP_Mock::expectActionNotAdded( 'admin_init', [ $subject, 'removeNotice' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldRemoveNotice() {
		$notice  = $this->getNotice();
		$notices = $this->getNotices();

		/** @var \PHPUnit_Framework_MockObject_MockObject $notice */
		$notice->expects( $this->exactly( 1 ) )->method( 'get_group' )->willReturn( 'default' );
		$notice->expects( $this->exactly( 1 ) )->method( 'get_id' )->willReturn( MultiCurrencyMissing::NOTICE_ID );

		/** @var \PHPUnit_Framework_MockObject_MockObject $notices */
		$notices->expects( $this->exactly( 1 ) )->method( 'remove_notice' )->with( 'default', MultiCurrencyMissing::NOTICE_ID );

		/** @var WPML_Notice $notice */
		/** @var WPML_Notices $notices */

		$subject = $this->getSubject( $notice, 2, $notices );
		$subject->removeNotice();
	}

	/**
	 * @test
	 */
	public function itShouldAddNotice() {
		WP_Mock::userFunction( 'admin_url', [ 'return_arg' => 0 ] );

		$notice  = $this->getNotice();
		$notices = $this->getNotices();

		/** @var \PHPUnit_Framework_MockObject_MockObject $notice */
		$notice->expects( $this->exactly( 1 ) )->method( 'set_css_class_types' )->with( 'notice-warning' );
		$notice->expects( $this->exactly( 1 ) )->method( 'set_restrict_to_screen_ids' )->with( RestrictedScreens::get() );
		$notice->expects( $this->exactly( 1 ) )->method( 'set_dismissible' )->with( true );

		/** @var \PHPUnit_Framework_MockObject_MockObject $notices */
		$notices->expects( $this->exactly( 1 ) )->method( 'create_notice' )->with()->willReturn( $notice );
		$notices->expects( $this->exactly( 1 ) )->method( 'add_notice' )->with( $notice );

		/** @var WPML_Notices $notices */

		$subject = $this->getSubject( null, 2, $notices );
		$subject->addNotice();
	}

	/**
	 * @param WPML_Notice|null  $notice
	 * @param int|null          $numberOfCurrencies
	 * @param WPML_Notices|null $notices
	 * @return MultiCurrencyMissing
	 */
	private function getSubject( $notice = null, $numberOfCurrencies = 0, $notices = null ) {
		return new MultiCurrencyMissing( $this->getWoocommerceWpml( $numberOfCurrencies ), $this->getNotices( $notice, $notices ) );
	}

	/**
	 * @param int $numberOfCurrencies
	 * @return woocommerce_wpml
	 */
	private function getWoocommerceWpml( $numberOfCurrencies ) {
		$wcml                               = $this->getMockBuilder( woocommerce_wpml::class )
			->disableOriginalConstructor()
			->getMock();
		$wcml->settings['currency_options'] = array_fill( 0, $numberOfCurrencies, 'currency' );
		return $wcml;
	}

	/**
	 * @param WPML_Notice|null  $notice
	 * @param WPML_Notices|null $notices
	 * @return WPML_Notices
	 */
	private function getNotices( $notice = null, $notices = null ) {
		if ( ! $notices ) {
			$notices = $this->getMockBuilder( WPML_Notices::class )
			->setMethods(
				[
					'get_notice',
					'create_notice',
					'add_notice',
					'remove_notice',
				]
			)->disableOriginalConstructor()->getMock();}
		if ( $notice ) {
			$notices->expects( $this->exactly( 1 ) )->method( 'get_notice' )->willReturn( $notice );
		}
		return $notices;
	}

	/**
	 * @return WPML_Notice
	 */
	private function getNotice() {
		return $this->getMockBuilder( WPML_Notice::class )
			->setMethods(
				[
					'set_css_class_types',
					'set_restrict_to_screen_ids',
					'set_dismissible',
					'get_id',
					'get_group',

				]
			)->disableOriginalConstructor()->getMock();
	}
}
