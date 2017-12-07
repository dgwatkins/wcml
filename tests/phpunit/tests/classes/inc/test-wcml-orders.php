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

		$object = new stdClass();
		$object->id = rand( 1, 100 );
		$language = 'fr';

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $object->id, 'wpml_language', true ),
			'return' => $language
		));

		$item = array();
		$item[ 'product_id' ] = rand( 1, 100 );
		$item[ 'variation_id' ] = rand( 1, 100 );

		$expected_item = array();
		$expected_item[ 'product_id' ] = rand( 1, 100 );
		$expected_item[ 'variation_id' ] = rand( 1, 100 );

		\WP_Mock::onFilter( 'translate_object_id' )->with( $item[ 'product_id' ], 'product', false, $language )->reply( $expected_item[ 'product_id' ] );
		\WP_Mock::onFilter( 'translate_object_id' )->with( $item[ 'variation_id' ], 'product_variation', false, $language )->reply( $expected_item[ 'variation_id' ] );

		$mock             = \Mockery::mock( 'alias:WooCommerce_Functions_Wrapper' );
		$mock->shouldReceive( 'get_order_id' )->andReturn( $object->id );		
		$mock->shouldReceive( 'get_item_downloads' )->andReturn( $expected_item );
		
		\WP_Mock::wpFunction( 'remove_filter', array( 'times' => 1, 'return' => true ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_get_item_downloads', array( $subject, 'filter_downloadable_product_items' ), 10, 3 );

		$filtered_item = $subject->filter_downloadable_product_items( array(), $item, $object );

		$this->assertEquals( $expected_item, $filtered_item );
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
}
