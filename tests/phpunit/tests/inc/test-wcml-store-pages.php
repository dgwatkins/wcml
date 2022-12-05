<?php

use WPML\FP\Fns;

/**
 * Class Test_WCML_Store_Pages
 */
class Test_WCML_Store_Pages extends OTGS_TestCase {

	public function setUp(){
		parent::setUp();

	}

	private function get_sitepress(){
		return $this->getMockBuilder( SitePress::class )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	private function get_woocommerce_wpml(){
		return $this->getMockBuilder('woocommerce_wpml')
		            ->disableOriginalConstructor()
		            ->getMock();
	}


	private function get_subject( $woocommerce_wpml = null, $sitepress = null ){

		if( null === $woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}

		if( null === $sitepress ){
			$sitepress = $this->get_sitepress();
		}

		return new WCML_Store_Pages( $woocommerce_wpml, $sitepress );
	}

	/**
	 * @test
	 * @dataProvider woocommerce_page_option_name
	 */
	public function it_should_add_front_end_hooks_to_filter_woocommerce_page_id( $woo_page ) {

		$options = array_column( $this->woocommerce_page_option_name(), 1, 0 );

		$post_translations = $this->getMockBuilder( WPML_Post_Translation::class )
			->disableOriginalConstructor()
			->setMethods( [ 'prefetch_ids' ] )
			->getMock();

		$post_translations->expects( $this->once() )
			->method( 'prefetch_ids' )
			->with( array_filter( array_values( $options ) ) );

		$sitepress = $this->getMockBuilder( SitePress::class )
			->disableOriginalConstructor()
			->setMethods( [ 'post_translations' ] )
			->getMock();

		$sitepress->method( 'post_translations' )
			->willReturn( $post_translations );

		$subject = $this->get_subject( null, $sitepress );

		WP_Mock::userFunction( 'is_admin', array( 'times' => 1, 'return' => false ) );

		WP_Mock::userFunction( 'get_option', array(
			'return' => function( $slug ) use ( $options ) {
				$slug = str_replace( 'woocommerce_', '', $slug );
				return isset( $options[ $slug ] ) ? $options[ $slug ] : false;
			}
		) );

		WP_Mock::expectFilterAdded( 'woocommerce_get_' . $woo_page, Fns::always( true ) );

		WP_Mock::expectFilterAdded( 'woocommerce_get_checkout_url', array( $subject, 'get_checkout_page_url' ) );

		$subject->add_hooks();
	}

	/**
	 * @test
	 * @dataProvider woocommerce_page_option_name
	 */
	public function it_should_filter_woocommerce_page_id_on_wc_settings_page( $woo_page ) {

		global $pagenow;
		$pagenow_buff = $pagenow;
		$pagenow      = 'admin.php';
		$_GET['page'] = 'wc-settings';

		$options = array_column( $this->woocommerce_page_option_name(), 1, 0 );

		$post_translations = $this->getMockBuilder( WPML_Post_Translation::class )
			->disableOriginalConstructor()
			->setMethods( [ 'prefetch_ids' ] )
			->getMock();

		$post_translations->expects( $this->once() )
			->method( 'prefetch_ids' )
			->with( array_filter( array_values( $options ) ) );

		$sitepress = $this->getMockBuilder( SitePress::class )
			->disableOriginalConstructor()
			->setMethods( [ 'post_translations' ] )
			->getMock();

		$sitepress->method( 'post_translations' )
			->willReturn( $post_translations );

		$subject = $this->get_subject( null, $sitepress );

		WP_Mock::userFunction( 'is_admin', array( 'times' => 1, 'return' => true ) );

		WP_Mock::userFunction( 'get_option', array(
			'return' => function( $slug ) use ( $options ) {
				$slug = str_replace( 'woocommerce_', '', $slug );
				return isset( $options[ $slug ] ) ? $options[ $slug ] : false;
			}
		) );

		WP_Mock::expectFilterAdded( 'woocommerce_get_' . $woo_page, Fns::always( true ) );

		$subject->add_hooks();

		$pagenow = $pagenow_buff;
		unset( $_GET['page'] );
	}

	/**
	 * @test
	 * @dataProvider woocommerce_page_option_name
	 */
	public function it_should_filter_woocommerce_page_id_on_pages_listings( $woo_page ) {

		global $pagenow;
		$pagenow_buff = $pagenow;
		$pagenow      = 'edit.php';
		$_GET['post_type'] = 'page';

		$options = array_column( $this->woocommerce_page_option_name(), 1, 0 );

		$post_translations = $this->getMockBuilder( WPML_Post_Translation::class )
			->disableOriginalConstructor()
			->setMethods( [ 'prefetch_ids' ] )
			->getMock();

		$post_translations->expects( $this->once() )
			->method( 'prefetch_ids' )
			->with( array_filter( array_values( $options ) ) );

		$sitepress = $this->getMockBuilder( SitePress::class )
			->disableOriginalConstructor()
			->setMethods( [ 'post_translations' ] )
			->getMock();

		$sitepress->method( 'post_translations' )
			->willReturn( $post_translations );

		$subject = $this->get_subject( null, $sitepress );

		WP_Mock::userFunction( 'is_admin', array( 'times' => 1, 'return' => true ) );

		WP_Mock::userFunction( 'get_option', array(
			'return' => function( $slug ) use ( $options ) {
				$slug = str_replace( 'woocommerce_', '', $slug );
				return isset( $options[ $slug ] ) ? $options[ $slug ] : false;
			}
		) );

		WP_Mock::expectFilterAdded( 'woocommerce_get_' . $woo_page, Fns::always( true ) );

		$subject->add_hooks();

		$pagenow = $pagenow_buff;
		unset( $_GET['post_type'] );
	}

	/**
	 * @test
	 * @group wcml-3648
	 */
	public function it_should_NOT_filter_woocommerce_page_id_on_translation_preview() {
		$_GET['preview'] = '1';
		$_GET['jobId']   = '123';

		$subject = $this->get_subject();

		\WP_Mock::userFunction( 'is_admin' )->andReturn( false );

		foreach ( $this->woocommerce_page_option_name() as $case ) {
			$page = reset( $case );
			WP_Mock::expectFilterNotAdded( 'woocommerce_get_' . $page, Fns::always( true ) );
		}

		$subject->add_hooks();

		unset( $_GET['preview'], $_GET['jobId'] );
	}

	public function woocommerce_page_option_name(){

		return array(
			array( 'shop_page_id', 6 ),
			array( 'cart_page_id', 7 ),
			array( 'checkout_page_id', 8 ),
			array( 'myaccount_page_id', 9 ),
			array( 'terms_page_id', false ),
			array( 'refund_returns_page_id', '' )
		);
	}

	/**
	 * @test
	 * @group pierre
	 */
	public function filter_shop_archive_link(){
		$link          = rand_str();
		$expected_link = rand_str();
		$post_type     = 'product';
		$shop_page_id  = 456;

		$sitepress = $this->getMockBuilder( SitePress::class )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_current_language', 'get_default_language' ) )
		                  ->getMock();
		$sitepress->method( 'get_current_language' )->willReturn( rand_str( 5 ) );
		$sitepress->method( 'get_default_language' )->willReturn( rand_str( 6 ) );

		\WP_Mock::userFunction( 'home_url', array( 'return' => $expected_link ) );
		\WP_Mock::userFunction( 'is_admin' )->andReturn( false );
		\WP_Mock::userFunction( 'get_option' )->with( 'page_on_front' )->andReturn( $shop_page_id );
		\WP_Mock::userFunction( 'wc_get_page_id' )->with( 'shop' )->andReturn( $shop_page_id );
		\WP_Mock::userFunction( 'get_post' )->with( $shop_page_id )
		                                    ->andReturn( \Mockery::mock( '\WP_Post' ) );

		$subject = $this->get_subject( null, $sitepress );
		$subject->init(); // Initialize the state with "page on front" and "shop"

		$this->assertSame( $expected_link, $subject->filter_shop_archive_link( $link, $post_type ) );
	}
}
