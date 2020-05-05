<?php

namespace WCML\AdminNotices;

/**
 * @group admin-notices
 */
class TestReview extends \OTGS_TestCase {

	private function getSubject( $wpmlNotices = null, $wpdb = null, $sitepress = null ) {
		$wpmlNotices = $wpmlNotices ?: $this->getWpmlNotices();
		$wpdb        = $wpdb ?: $this->getWpdb();
		$sitepress   = $sitepress ?: $this->getSitepress();


		return new Review( $wpmlNotices, $wpdb, $sitepress );
	}

	private function getWpmlNotices() {
		return $this->getMockBuilder( '\WPML_Notices' )
		            ->setMethods(
			            [
				            'get_new_notice',
				            'is_notice_dismissed',
				            'get_new_notice_action',
				            'add_notice',
			            ]
		            )->disableOriginalConstructor()->getMock();
	}

	private function getSitepress() {
		return $this->getMockBuilder( '\SitePress' )
		            ->setMethods(
			            [
				            'get_default_language',
			            ]
		            )->disableOriginalConstructor()->getMock();
	}

	private function getWpdb() {
		return $this->stubs->wpdb();
	}

	/**
	 * @test
	 */
	public function itShouldLoadOnBackend() {
		$subject = $this->getSubject();
		$this->assertInstanceOf( \IWPML_Backend_Action::class, $subject );
	}

	/**
	 * @test
	 */
	public function itShouldLoadOnFrontend() {
		$subject = $this->getSubject();
		$this->assertInstanceOf( \IWPML_Frontend_Action::class, $subject );
	}

	/**
	 * @test
	 */
	public function itShouldAddHooks() {
		$subject = $this->getSubject();
		\WP_Mock::expectActionAdded( 'admin_notices', [ $subject, 'addNotice' ] );
		\WP_Mock::expectActionAdded( 'woocommerce_after_order_object_save', [ $subject, 'onNewOrder' ] );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldCheckOptionOnNewOrder() {
		$subject = $this->getSubject();

		\WP_Mock::userFunction( 'get_option', [
			'args'   => [ Review::OPTION_NAME, false ],
			'times'  => 1,
			'return' => true,
		] );

		$subject->onNewOrder();
	}

	/**
	 * @test
	 */
	public function itShouldMaybeAddOptionToShowNoticeOnNewOrder() {

		\WP_Mock::userFunction( 'get_option', [
			'args'   => [ Review::OPTION_NAME, false ],
			'times'  => 1,
			'return' => false,
		] );

		$defaultCurrency = 'USD';
		$defaultLanguage = 'en';
		$wpdb            = $this->getWpdb();
		$sitepress       = $this->getSitepress();

		\WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', [
			'times'  => 1,
			'return' => $defaultCurrency,
		] );

		$sitepress->method( 'get_default_language' )->willReturn( $defaultLanguage );

		$query = $wpdb->prepare( "SELECT COUNT(p.ID) FROM {$wpdb->postmeta} as pm 
				INNER JOIN {$wpdb->posts} as p ON pm.post_id = p.ID 
				WHERE p.post_type = 'shop_order' AND ( 
 				( pm.meta_key = '_order_currency' AND pm.meta_value != %s ) 
 				OR 
 				( pm.meta_key = 'wpml_language' AND pm.meta_value != %s  ) )",
			$defaultCurrency, $defaultLanguage );

		$wpdb->method( 'get_var' )->with( $query )->willReturn( 1 );

		$subject = $this->getSubject( null, $wpdb, $sitepress );


		\WP_Mock::userFunction( 'add_option', [
			'args'   => [ Review::OPTION_NAME, true ],
			'times'  => 1,
			'return' => true,
		] );

		$subject->onNewOrder();
	}

	/**
	 * @test
	 */
	public function itShouldNotAddNotice() {
		$subject = $this->getSubject();

		\WP_Mock::userFunction( 'get_option', [
			'args'   => [ Review::OPTION_NAME, false ],
			'times'  => 1,
			'return' => false,
		] );

		$subject->addNotice();
	}

	/**
	 * @test
	 */
	public function itShouldNotAddNoticeAfterItWasDissmised() {

		\WP_Mock::userFunction( 'get_option', [
			'args'   => [ Review::OPTION_NAME, false ],
			'times'  => 1,
			'return' => true,
		] );

		$wpmlNotices = $this->getWpmlNotices();
		$notice      = $this->getMockBuilder( '\WPML_Notice' )->disableOriginalConstructor()->getMock();

		$wpmlNotices->method( 'get_new_notice' )->with( 'wcml-rate', $this->getNoticeText(), 'wcml-admin-notices' )->willReturn( $notice );
		$wpmlNotices->method( 'is_notice_dismissed' )->willReturn( true );
		$wpmlNotices->expects( $this->never() )->method( 'add_notice' )->with( $notice )->willReturn( true );

		$subject = $this->getSubject( $wpmlNotices );

		$subject->addNotice();
	}

	/**
	 * @test
	 */
	public function itShouldAddNotice() {

		\WP_Mock::userFunction( 'get_option', [
			'args'   => [ Review::OPTION_NAME, false ],
			'times'  => 1,
			'return' => true,
		] );

		$wpmlNotices = $this->getWpmlNotices();

		$notice = $this->getMockBuilder( '\WPML_Notice' )
		               ->setMethods(
			               [
				               'set_css_class_types',
				               'set_css_classes',
				               'set_dismissible',
				               'add_action',
				               'set_restrict_to_screen_ids',
				               'add_capability_check',
			               ]
		               )->disableOriginalConstructor()->getMock();

		$reviewButton = $this->getMockBuilder( '\WPML_Notice_Action' )->disableOriginalConstructor()->getMock();

		$notice->method( 'set_css_class_types' )->with( 'info' )->willReturn( true );
		$notice->method( 'set_css_classes' )->with( [ 'otgs-notice-wcml-rating' ] )->willReturn( true );
		$notice->method( 'set_dismissible' )->with( true )->willReturn( true );
		$notice->method( 'add_action' )->with( $reviewButton )->willReturn( true );
		$notice->method( 'set_restrict_to_screen_ids' )->with( $this->getRestrictedScreenIds() )->willReturn( true );
		$notice->method( 'add_capability_check' )->with( [
			'manage_options',
			'wpml_manage_woocommerce_multilingual'
		] )->willReturn( true );

		$reviewLink = 'https://wordpress.org/support/plugin/woocommerce-multilingual/reviews/?filter=5#new-post';
		$wpmlNotices->method( 'get_new_notice_action' )->with( __( 'Review WooCommerce Multilingual', 'woocommerce-multilingual' ), $reviewLink, false, false, true )->willReturn( $reviewButton );
		$wpmlNotices->method( 'get_new_notice' )->with( 'wcml-rate', $this->getNoticeText(), 'wcml-admin-notices' )->willReturn( $notice );
		$wpmlNotices->method( 'is_notice_dismissed' )->willReturn( false );
		$wpmlNotices->expects( $this->once() )->method( 'add_notice' )->with( $notice )->willReturn( true );

		$subject = $this->getSubject( $wpmlNotices );

		$subject->addNotice();
	}

	private function getNoticeText() {
		$text = '<h2>';
		$text .= __( 'Congrats! You\'ve just earned some money using WooCommerce Multilingual.', 'woocommerce-multilingual' );
		$text .= '</h2>';

		$text .= '<p>';
		$text .= __( 'How do you feel getting your very first order in foreign language or currency?', 'woocommerce-multilingual' );
		$text .= '<br />';
		$text .= __( 'We for sure are super thrilled about your success! Will you help WCML improve and grow?', 'woocommerce-multilingual' );
		$text .= '</p>';

		$text .= '<p><strong>';
		$text .= __( 'Give us <span class="rating">5.0 <i class="otgs-ico-star"></i></span> review now.', 'woocommerce-multilingual' );
		$text .= '</strong></p>';

		return $text;
	}

	private function getRestrictedScreenIds() {
		return [
			'dashboard',
			'woocommerce_page_wpml-wcml',
			'woocommerce_page_wc-admin',
			'woocommerce_page_wc-reports',
			'woocommerce_page_wc-settings',
			'woocommerce_page_wc-status',
			'woocommerce_page_wc-addons',
			'edit-shop_order',
			'edit-shop_coupon',
			'edit-product',
		];
	}

}
