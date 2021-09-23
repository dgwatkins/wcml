<?php

use tad\FunctionMocker\FunctionMocker;

class Test_WCML_Orders extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;


	public function setUp()
	{
		parent::setUp();

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( [
			                        'get_current_language',
			                        'get_user_admin_language',
			                        'get_default_language',
		                        ] )
		                        ->getMock();

		$this->woocommerce_wpml = $this->getMockBuilder('woocommerce_wpml')
			->disableOriginalConstructor()
			->getMock();
	}


	private function get_subject(){
		return new WCML_Orders( $this->woocommerce_wpml, $this->sitepress );
	}

	/**
	 * @test
	 */
	public function it_should_set_dashboard_order_language_for_new_order_page() {

		\WP_Mock::userFunction( 'is_admin', [
				'return' => true
			]
		);

		\WP_Mock::userFunction( 'get_current_user_id', [
				'return' => 1
			]
		);

		\WP_Mock::userFunction( 'get_option', [
				'args'   => [ 'wpml-st-all-strings-are-in-english' ],
				'return' => false
			]
		);

		global $pagenow;
		$pagenow           = 'post-new.php';
		$_GET['post_type'] = 'shop_order';

		$language = 'en';
		$this->sitepress->method( 'get_default_language' )->willReturn( $language );

		$setCookie = FunctionMocker::replace( 'setcookie', true );

		$subject = $this->get_subject();
		$subject->init();

		$setCookie->wasCalledWithOnce( [
			$subject::DASHBOARD_COOKIE_NAME,
			$language,
			time() + $subject::COOKIE_TTL,
			COOKIEPATH,
			COOKIE_DOMAIN
		] );

		unset( $_GET['post_type'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_set_dashboard_order_language_for_not_new_order_admin_page() {

		\WP_Mock::userFunction( 'is_admin', [
				'return' => true
			]
		);

		\WP_Mock::userFunction( 'get_current_user_id', [
				'return' => 1
			]
		);

		\WP_Mock::userFunction( 'get_option', [
				'args'   => [ 'wpml-st-all-strings-are-in-english' ],
				'return' => false
			]
		);

		global $pagenow;
		$pagenow           = 'post-new.php';
		$_GET['post_type'] = 'product';

		$subject = $this->get_subject();
		$subject->init();

		unset( $_GET['post_type'] );
	}

	/**
	 * @test
	 */
	public function it_should_filter_downloadable_product_items(){

		$subject = $this->get_subject( );
		$language = 'fr';
		$order_id =  mt_rand( 1, 100 );

		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_id' ) )
		                ->getMock();
		$product->method( 'get_id' )->willReturn( $order_id );


		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $order_id, 'wpml_language', true ),
			'return' => $language
		));

		$variation_id = mt_rand( 101, 200 );
		$translated_variation_id = mt_rand( 201, 300 );
		$expected_downloads = array( 'test' );

		\WP_Mock::onFilter( 'translate_object_id' )->with( $variation_id, 'product_variation', false, $language )->reply( $translated_variation_id );
		$item = $this->getMockBuilder( 'WC_Order_Item_Product' )
		             ->disableOriginalConstructor()
		             ->setMethods( array( 'get_item_downloads', 'get_variation_id', 'set_variation_id', 'set_product_id' ) )
		             ->getMock();
		$item->method( 'get_variation_id' )->willReturn( $variation_id );
		$item->method( 'set_variation_id' )->with( $translated_variation_id )->willReturn( true );
		$item->method( 'get_item_downloads' )->willReturn( $expected_downloads );


		\WP_Mock::wpFunction( 'remove_filter', array( 'times' => 1, 'return' => true ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_get_item_downloads', array( $subject, 'filter_downloadable_product_items' ), 10, 3 );

		$filtered_files = $subject->filter_downloadable_product_items( array(), $item, $product );

		$this->assertEquals( $expected_downloads, $filtered_files );
	}

	/**
	 * @test
	 */
	public function it_should_not_set_product_id_when_filtering_downloadable_product_items(){

		$subject = $this->get_subject( );
		$language = 'fr';
		$order_id =  mt_rand( 1, 100 );

		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_id' ) )
		                ->getMock();
		$product->method( 'get_id' )->willReturn( $order_id );


		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $order_id, 'wpml_language', true ),
			'return' => $language
		));

		$product_id = mt_rand( 101, 200 );
		$translated_product_id = null;
		$expected_downloads = array( 'test' );

		\WP_Mock::onFilter( 'translate_object_id' )->with( $product_id, 'product', false, $language )->reply( $translated_product_id );
		$item = $this->getMockBuilder( 'WC_Order_Item_Product' )
		             ->disableOriginalConstructor()
		             ->setMethods( array( 'get_item_downloads', 'get_product_id', 'get_variation_id', 'set_product_id' ) )
		             ->getMock();
		$item->method( 'get_variation_id' )->willReturn( 0 );
		$item->method( 'get_product_id' )->willReturn( $product_id );
		$item->expects( $this->never() )->method( 'set_product_id' );
		$item->method( 'get_item_downloads' )->willReturn( $expected_downloads );


		\WP_Mock::wpFunction( 'remove_filter', array( 'times' => 1, 'return' => true ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_get_item_downloads', array( $subject, 'filter_downloadable_product_items' ), 10, 3 );

		$filtered_files = $subject->filter_downloadable_product_items( array(), $item, $product );

		$this->assertEquals( $expected_downloads, $filtered_files );
	}

	/**
	 * @test
	 */
	public function filter_customer_get_downloadable_products(){

		$product_id = rand( 1, 100 );
		$tr_product_id = rand( 1, 100 );
		$tr_title = rand_str();

		$downloads = array();
		$downloads[ ] = array(
			'product_id' => $product_id
		);

		$language = 'fr';
		$this->sitepress->method( 'get_current_language' )->willReturn( $language );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $product_id ),
			'return' => 'product'
		));

		\WP_Mock::onFilter( 'translate_object_id' )->with( $product_id, 'product', false, $language )->reply( $tr_product_id );

		\WP_Mock::wpFunction( 'get_the_title', array(
			'args'   => array( $tr_product_id ),
			'return' => $tr_title
		));

		$exp_downloads = array();
		$exp_downloads[ ] = array(
			'product_id' => $product_id,
			'product_name' => $tr_title
		);

		$subject = $this->get_subject( );
		$filtered_downloads = $subject->filter_customer_get_downloadable_products( $downloads );

		$this->assertEquals( $exp_downloads, $filtered_downloads );

	}

	/**
	 * @test
	 * @group wcml-3245
	 */
	public function get_filtered_comments_without_user_id(){
		$unknownUserId = 0;

		$comments = [
			(object) [
				'comment_content' => 'Some order comment.',
			],
		];

		\WP_Mock::userFunction( 'get_current_user_id' )->andReturn( $unknownUserId );

		\WP_Mock::userFunction( 'translate' )->times( 0 );

		$subject = $this->get_subject();

		$this->assertEquals( $comments, $subject->get_filtered_comments( $comments ) );
	}

	/**
	 * @test
	 * @group wcml-3245
	 */
	public function get_filtered_comments_with_user_id(){
		$userId             = 123;
		$originalContent    = 'Some order comment.';
		$translationContent = 'A translated comment';

		$getComments = function( $content ) {
			return [
				(object) [
					'comment_content' => $content,
				],
			];
		};

		\WP_Mock::userFunction( 'get_current_user_id' )->andReturn( $userId );

		\WP_Mock::userFunction( 'translate' )
			->withArgs( [ $originalContent, 'woocommerce' ] )
			->andReturn( $translationContent );

		$subject = $this->get_subject();

		$this->assertEquals(
			$getComments( $translationContent ),
			$subject->get_filtered_comments( $getComments( $originalContent ) )
		);
	}

	public function it_should_get_woocommerce_order_items_in_user_admin_language(){

		$language = 'es';
		$current_user_id = 1;

		\WP_Mock::userFunction( 'get_current_user_id', array(
			'return' => $current_user_id
		));

		$_GET[ 'post' ] = 5;
		\WP_Mock::userFunction( 'get_post_type', array(
			'args'   => array( $_GET[ 'post' ] ),
			'return' => 'shop_order'
		));

		$this->sitepress->method( 'get_user_admin_language' )->with( $current_user_id, true )->willReturn( $language );

		\WP_Mock::userFunction( 'is_admin', array(
				'return' => true
			)
		);

		$this->get_woocommerce_order_items_mock( $language, new stdClass() );

		unset( $_GET[ 'post' ] );
	}

	/**
	 * @test
	 * @dataProvider woocommerce_order_items_actions
	 */
	public function it_should_get_woocommerce_order_items_in_order_language( $action ) {

		$language       = 'fr';
		$_GET['action'] = $action;
		$order_id       = 100;
		$order          = $this->getOrder();
		$order->method( 'get_id' )->willReturn( $order_id );

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $order_id, 'wpml_language', true ),
			'return' => $language
		) );

		\WP_Mock::userFunction( 'is_admin', array(
				'return' => true
			)
		);

		$this->get_woocommerce_order_items_mock( $language, $order );

		unset( $_GET['action'] );
	}

	public function woocommerce_order_items_actions(){
		return array(
			array( 'woocommerce_mark_order_complete' ),
			array( 'woocommerce_mark_order_status' ),
			array( 'mark_processing' )
		);
	}

	/**
	 * @test
	 */
	public function it_should_get_woocommerce_order_items_in_current_language(){

		\WP_Mock::userFunction( 'is_view_order_page', array(
				'return' => true
			)
		);

		$language = 'en';

		$this->sitepress->method( 'get_current_language' )->willReturn( $language );

		$this->get_woocommerce_order_items_mock( $language, new stdClass() );
	}

	/**
	 * @test
	 */
	public function it_should_get_woocommerce_order_items_in_current_language_for_order_received_page(){

		\WP_Mock::userFunction( 'is_order_received_page', array(
				'return' => true
			)
		);

		$language = 'en';

		$this->sitepress->method( 'get_current_language' )->willReturn( $language );

		$this->get_woocommerce_order_items_mock( $language, new stdClass() );
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function it_should_get_woocommerce_order_items_in_current_language_for_rest_api_call(){

		\WP_Mock::userFunction( 'is_admin', array(
				'return' => false
			)
		);

		\WP_Mock::userFunction( 'is_view_order_page', array(
				'return' => false
			)
		);

		\WP_Mock::userFunction( 'is_order_received_page', array(
				'return' => false
			)
		);

		\Mockery::mock( 'overload:\WCML\Rest\Functions' )->shouldReceive( 'isRestApiRequest' )->andReturn( true );

		$language = 'es';

		$this->sitepress->method( 'get_current_language' )->willReturn( $language );

		$this->get_woocommerce_order_items_mock( $language, new stdClass() );
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function it_should_not_get_woocommerce_order_items_for_non_view_order_front_pages(){

		\WP_Mock::userFunction( 'is_admin', array(
				'return' => false
			)
		);

		\WP_Mock::userFunction( 'is_view_order_page', array(
				'return' => false
			)
		);

		\WP_Mock::userFunction( 'is_order_received_page', array(
				'return' => false
			)
		);

		\Mockery::mock( 'overload:\WCML\Rest\Functions' )->shouldReceive( 'isRestApiRequest' )->andReturn( false );

		$subject = $this->get_subject();
		$subject->woocommerce_order_get_items( [ 'test' ], new stdClass() );
	}

	public function get_woocommerce_order_items_mock( $language, $order ){

		$product_id = 8;
		$translated_product_id = 9;
		$this->translated_post_object = new stdClass();
		$this->translated_post_object->post_title = 'ES PRODUCT';
		$variation_id = 10;
		$translated_variation_id = 11;
		$this->translated_variation_title = 'ES PRODUCT - Black('.$language.')';
		$translated_variation_object = $this->getMockBuilder( 'WC_Product' )
		                                    ->disableOriginalConstructor()
		                                    ->setMethods( array( 'get_name' ) )
		                                    ->getMock();
		$translated_variation_object->method( 'get_name' )->willReturn( $this->translated_variation_title );



		\WP_Mock::userFunction( 'get_post_type', array(
			'args'   => array( $product_id ),
			'return' => 'product'
		));

		$color_data =	array(
			'id' => 12,
			'key' => 'color',
			'value' => 'Black'
		);
		$color_meta_data = $this->getMockBuilder( 'WC_Meta_Data' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_data' ) )
		                  ->getMock();
		$color_meta_data->method( 'get_data' )->willReturn( $color_data );
		$updated_color_meta_data_value = 'Black(ES)';

		$size_data_with_missing_id = array(
			'key'   => 'size',
			'value' => 'Small'
		);
		$size_meta_data = $this->getMockBuilder( 'WC_Meta_Data' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_data' ) )
		                  ->getMock();
		$size_meta_data->method( 'get_data' )->willReturn( $size_data_with_missing_id );
		$updated_size_meta_data_value = 'Small(ES)';



		$items = array();
		$product_item = $this->getMockBuilder( 'WC_Order_Item_Product' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'get_type', 'get_product_id', 'get_variation_id', 'set_variation_id', 'set_product_id', 'get_meta_data', 'update_meta_data', 'set_name', 'save' ) )
		                     ->getMock();
		$product_item->method( 'get_product_id' )->willReturn( $product_id );
		$product_item->method( 'get_variation_id' )->willReturn( $variation_id );
		$product_item->expects( $this->once() )->method( 'set_product_id' )->with( $translated_product_id )->willReturn( true );
		$product_item->expects( $this->once() )->method( 'set_variation_id' )->with( $translated_variation_id )->willReturn( true );
		$product_item->method( 'get_type' )->willReturn( 'line_item' );
		$product_item->method( 'get_meta_data' )->willReturn( array( $color_meta_data, $size_meta_data ) );
		$product_item->expects( $this->exactly( 2 ) )->method( 'update_meta_data' )->willReturn( true );
		$product_item->method( 'save' )->willReturn( true );

		$that = $this;
		$product_item->method( 'set_name' )->willReturnCallback( function ( $name ) use ( $that ) {
			if ( $that->translated_post_object->post_title === $name || $that->translated_variation_title === $name ) {
				return true;
			}
		} );

		$items[] = $product_item;

		$shipping_id  = 'flat_rate';
		$shipping_instance_id  = 1;
		$shipping_method_title  = 'Shipping title';
		$translated_shipping_method_title = 'Shipping title ES';
		$shipping_item = $this->getMockBuilder( 'WC_Order_Item_Shipping' )
		                      ->disableOriginalConstructor()
		                      ->setMethods( array( 'get_method_id', 'get_instance_id', 'get_method_title', 'set_method_title', 'save' ) )
		                      ->getMock();
		$shipping_item->method( 'get_method_id' )->willReturn( $shipping_id );
		$shipping_item->method( 'get_instance_id' )->willReturn( $shipping_instance_id );
		$shipping_item->method( 'get_method_title' )->willReturn( $shipping_method_title );
		$shipping_item->expects( $this->once() )->method( 'set_method_title' )->with( $translated_shipping_method_title )->willReturn( true );
		$shipping_item->method( 'save' )->willReturn( true );

		$this->woocommerce_wpml->shipping = $this->getMockBuilder( 'WCML_Shipping' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'translate_shipping_method_title' ) )
		                                         ->getMock();
		$this->woocommerce_wpml->shipping->expects( $this->once() )->method( 'translate_shipping_method_title' )->with( $shipping_method_title, $shipping_id.$shipping_instance_id, $language )->willReturn( $translated_shipping_method_title );

		$items[] = $shipping_item;

		\WP_Mock::userFunction( 'get_post', array(
				'args'   => array( $translated_product_id ),
				'return' => $this->translated_post_object
			)
		);

		\WP_Mock::userFunction( 'wc_get_product', array(
				'args'   => array( $translated_variation_id ),
				'return' => $translated_variation_object
			)
		);

		\WP_Mock::userFunction( 'get_post_meta', array(
				'args'   => array( $translated_variation_id, 'attribute_' . $color_data['key'], true ),
				'return' => $updated_color_meta_data_value
			)
		);

		\WP_Mock::userFunction( 'get_post_meta', array(
				'args'   => array( $translated_variation_id, 'attribute_' . $size_data_with_missing_id['key'], true ),
				'return' => $updated_size_meta_data_value
			)
		);

		\WP_Mock::onFilter( 'translate_object_id' )->with( $product_id, 'product', false, $language )->reply( $translated_product_id );
		\WP_Mock::onFilter( 'translate_object_id' )->with( $variation_id, 'product_variation', false, $language )->reply( $translated_variation_id );

		$subject = $this->get_subject();
		$subject->woocommerce_order_get_items( $items, $order );

	}

	/**
	 * @test
	 * @dataProvider  dpShouldNOTSetOrderLanguageBeforeSave
	 * @group wcml-3621
	 *
	 * @param string $status
	 * @param mixed  $orderLanguage
	 */
	public function itShouldNOTSetOrderLanguageBeforeSave( $status, $orderLanguage ) {
		$order = $this->getOrder();
		$order->method( 'get_status' )->willReturn( $status );
		$order->method( 'get_meta' )->willReturn( $orderLanguage );
		$order->expects( $this->never() )->method( 'add_meta_data' );

		$subject = $this->get_subject();
		$subject->setOrderLanguageBeforeSave( $order );
	}

	public function dpShouldNOTSetOrderLanguageBeforeSave() {
		return [
			'draft order'          => [ 'checkout-draft', '' ],
			'language already set' => [ 'pending', 'fr' ],
		];
	}

	/**
	 * @test
	 * @group wcml-3621
	 */
	public function itShouldSetOrderLanguageBeforeSave() {
		$globalLanguage = 'fr';

		FunctionMocker::replace( 'constant', function( $constantName ) use ( $globalLanguage ) {
			return 'ICL_LANGUAGE_CODE' === $constantName ? $globalLanguage : null;
		} );

		$order = $this->getOrder();
		$order->method( 'get_status' )->willReturn( 'pending' );
		$order->method( 'get_meta' )->willReturn( '' );
		$order->expects( $this->once() )
		      ->method( 'add_meta_data' )
		      ->with( 'wpml_language', $globalLanguage, true );

		$subject = $this->get_subject();
		$subject->setOrderLanguageBeforeSave( $order );
	}

	private function getOrder() {
		return $this->getMockBuilder( \WC_Order::class )
		     ->disableOriginalConstructor()
		     ->setMethods( [
				'get_id',
				'get_status',
				'get_meta',
				'add_meta_data',
		     ] )
		     ->getMock();
	}
}
