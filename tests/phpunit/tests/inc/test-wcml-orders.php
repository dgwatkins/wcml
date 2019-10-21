<?php

class Test_WCML_Orders extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;


	public function setUp()
	{
		parent::setUp();

		$this->sitepress = $this->getMockBuilder('SitePress')
			->disableOriginalConstructor()
			->setMethods(array( 'get_current_language', 'get_user_admin_language' ))
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
	public function filter_downloadable_product_items(){

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
	 */
	public function get_filtered_comments(){

		$user_id = 1;
		$user_language = rand_str( 2 );

		$comment = new stdClass();
		$comment->comment_content = rand_str();
		$comments[] = $comment;

		$comment_string_id = 10;
		$filtered_comment = new stdClass();
		$filtered_comment->comment_content = rand_str();
		$comment_strings[ $user_language ][ 'value' ] = $filtered_comment->comment_content;

		$expected_comments[] = $filtered_comment;

		\WP_Mock::wpFunction( 'get_current_user_id', array(
			'return' => $user_id
		));

		\WP_Mock::wpFunction( 'get_user_meta', array(
			'args'   => array( $user_id, 'icl_admin_language', true ),
			'return' => $user_language
		));

		\WP_Mock::wpFunction( 'icl_get_string_id', array(
			'args'   => array( $comment->comment_content, 'woocommerce' ),
			'return' => $comment_string_id
		));

		\WP_Mock::wpFunction( 'icl_get_string_translations_by_id', array(
			'args'   => array( $comment_string_id ),
			'return' => $comment_strings
		));

		$subject = $this->get_subject( );
		$filtered_comments = $subject->get_filtered_comments( $comments );

		$this->assertEquals( $expected_comments, $filtered_comments );

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
		$order          = $this->getMockBuilder( 'WC_Order' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( array( 'get_id' ) )
		                       ->getMock();
		$order->method( 'get_id' )->willReturn( $order_id );

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $order_id, 'wpml_language', true ),
			'return' => $language
		) );

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

		$language = 'en';

		$this->sitepress->method( 'get_current_language' )->willReturn( $language );

		$this->get_woocommerce_order_items_mock( $language, new stdClass() );
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

}
