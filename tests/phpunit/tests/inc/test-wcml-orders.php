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
			->setMethods(array( 'get_current_language' ))
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

	/**
	 * @test
	 */
	public function do_to_filter_woocommerce_order_get_items_on_save_action(){

		$_POST['post_type'] = 'shop_order';
		$_POST['wc_order_action'] = '';

		$subject = $this->get_subject();

		$this->assertEmpty( $subject->woocommerce_order_get_items( array(), new stdClass() ) );

		unset( $_POST['post_type'] );
		unset( $_POST['wc_order_action'] );

	}
}
