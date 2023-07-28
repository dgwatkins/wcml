<?php

use tad\FunctionMocker\FunctionMocker;
use WPML\FP\Fns;
use WPML\Convert\Ids;

class Test_WCML_Comments extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var SitePress */
	private $sitepress;

	private $scheme      = 'http://';
	private $http_host   = 'domain.tld';
	private $request_uri = '/some/path/';

	public function setUp()
	{
		parent::setUp();
        $_GET = [];
		\WP_Mock::passthruFunction( '__' );
	}

	private function get_woocommerce_wpml(){
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	private function get_sitepress() {
		return $this->getMockBuilder( SitePress::class )
					->setMethods( [
						'get_current_language'
					] )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	private function get_wpml_post_translations() {
		return $this->getMockBuilder( 'WPML_Post_Translation' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}


	private function get_subject( $woocommerce_wpml = false, $sitepress = false, $wpml_post_translations = false, $wpdb = false ){
		
		if( !$woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}
		
		if( !$sitepress ){
			$sitepress = $this->get_sitepress();
		}

		if( !$wpml_post_translations ){
			$wpml_post_translations = $this->get_wpml_post_translations();
		}

		if( !$wpdb ){
			$wpdb = $this->stubs->wpdb();
		}
		
		return new WCML_Comments( $woocommerce_wpml, $sitepress, $wpml_post_translations, $wpdb );
	}

	/**
	 * @test
	 */
	public function add_hooks(){
		global $_GET;

		$_GET['clang'] = 'all';

		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'wp_head', array( $subject, 'no_index_all_reviews_page' ) );

		\WP_Mock::expectActionAdded( 'wp_insert_comment', array( $subject, 'add_comment_rating' ) );
		\WP_Mock::expectActionAdded( 'added_comment_meta', array( $subject, 'maybe_duplicate_comment_rating' ), 10, 4 );
		\WP_Mock::expectActionAdded( 'woocommerce_review_before_comment_meta', array( $subject, 'add_comment_flag' ), 9 );
		\WP_Mock::expectActionAdded( 'woocommerce_review_before_comment_text', [ $subject, 'open_lang_div' ] );
		\WP_Mock::expectActionAdded( 'woocommerce_review_after_comment_text', [ $subject, 'close_lang_div' ] );
		\WP_Mock::expectActionAdded( 'trashed_comment', array( $subject, 'recalculate_average_rating_on_comment_hook' ), 10, 2 );

		\WP_Mock::expectFilterAdded( 'get_post_metadata', array( $subject, 'filter_average_rating' ), 10, 4 );
		\WP_Mock::expectFilterAdded( 'comments_clauses', array( $subject, 'comments_clauses' ), 10, 2 );
		\WP_Mock::expectActionAdded( 'comment_form_before', array( $subject, 'comments_link' ) );
		\WP_Mock::expectFilterAdded( 'wpml_is_comment_query_filtered', array( $subject, 'is_comment_query_filtered' ), 10, 3 );
		\WP_Mock::expectActionAdded( 'woocommerce_product_set_visibility', Fns::withoutRecursion( Fns::noop(), [ $subject, 'recalculate_comment_rating' ] ), 9 );

		\WP_Mock::expectFilterAdded( 'woocommerce_top_rated_products_widget_args', array( $subject, 'top_rated_products_widget_args' ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_rating_filter_count', array( $subject, 'woocommerce_rating_filter_count' ), 10, 3 );

		$subject->add_hooks();
	}
	
	/**
	 * @test
	 */
	public function it_does_not_register_no_index_all_reviews_page_hook_when_languages_by_default_selected() {
		global $_GET;
		$_GET['clang'] = 'all';
		
		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( array( 'get_setting' ) )
		                         ->getMock();
		$woocommerce_wpml->method( 'get_setting' )->willReturn( 1 );
		
		
		$subject = $this->get_subject( $woocommerce_wpml );
		
		\WP_Mock::expectActionNotAdded( 'wp_head', [ $subject, 'no_index_all_reviews_page' ] );
		
		$subject->add_hooks();
		
		unset( $_GET );
	}
	
	/**
	 * @test
	 */
	public function add_comment_rating(){

		$product_id = mt_rand( 1, 100 );
		$translated_product_id = mt_rand( 101, 200 );
		$trid = mt_rand( 201, 300 );
		$_POST['comment_post_ID'] = $product_id;
		
		$translations = array();
		$translations[] = $product_id;
		$translations[] = $translated_product_id;

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		     ->disableOriginalConstructor()
		     ->setMethods( array( 'get_element_translations' ) )
		     ->getMock();

		$wpml_post_translations->method( 'get_element_translations' )->with( $product_id )->willReturn( $translations );
		
		$subject = $this->get_subject( false, false, $wpml_post_translations );
		
		\WP_Mock::userFunction( 'get_post_type', array(
			'args'   => array( $product_id ),
			'return' => 'product'
		));

		$original_ratings_stars = mt_rand( 1, 5 );
		$original_ratings_count = mt_rand( 301, 400 );
		$original_ratings = array( $original_ratings_stars => $original_ratings_count );

		$product_obj = $this->getMockBuilder( 'WC_Product' )
		                    ->disableOriginalConstructor()
		                    ->setMethods()
		                    ->getMock();

		\WP_Mock::userFunction( 'wc_get_product', array(
			'args'   => array( $product_id ),
			'return' => $product_obj
		));

		$wc_comment = \Mockery::mock( 'overload:WC_Comments' );
		$wc_comment->shouldReceive( 'get_rating_counts_for_product' )->with( $product_obj )->andReturn( $original_ratings );
		$wc_comment->shouldReceive( 'get_review_count_for_product' )->with( $product_obj )->andReturn( $original_ratings_count );
		$wc_comment->shouldReceive( 'clear_transients' );

		$translated_ratings_stars = mt_rand( 1, 5 );
		$translated_ratings_count = mt_rand( 401, 500 );
		$translated_ratings = array( $translated_ratings_stars => $translated_ratings_count );

		$translated_product_obj = $this->getMockBuilder( 'WC_Product' )
		                    ->disableOriginalConstructor()
		                    ->setMethods()
		                    ->getMock();

		\WP_Mock::userFunction( 'wc_get_product', array(
			'args'   => array( $translated_product_id ),
			'return' => $translated_product_obj
		));

		$wc_comment->shouldReceive( 'get_rating_counts_for_product' )->with( $translated_product_obj )->andReturn( $translated_ratings );
		$wc_comment->shouldReceive( 'get_review_count_for_product' )->with( $translated_product_obj )->andReturn( $translated_ratings_count );

		$expected_reviews_count = $original_ratings_count + $translated_ratings_count;

		\WP_Mock::userFunction( 'update_post_meta', array(
			'args'   => array( $translated_product_id, '_wcml_review_count', $expected_reviews_count ),
			'times'  => 1,
			'return' => true
		));
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args'   => array( $product_id, '_wcml_review_count', $expected_reviews_count ),
			'times'  => 1,
			'return' => true
		));

		$expected_ratings_count = $original_ratings;
		foreach ( $translated_ratings as $rating => $count ) {
			if ( ! isset( $expected_ratings_count[ $rating ] ) ) {
				$expected_ratings_count[ $rating ] = 0;
			}
			$expected_ratings_count[ $rating ] += $count;
		}

		\WP_Mock::userFunction( 'update_post_meta', array(
			'args'   => array( $translated_product_id, '_wcml_rating_count', $expected_ratings_count ),
			'times'  => 1,
			'return' => true
		));
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args'   => array( $product_id, '_wcml_rating_count', $expected_ratings_count ),
			'times'  => 1,
			'return' => true
		));

		$original_ratings_sum = $original_ratings_stars*$original_ratings_count;
		$translated_ratings_sum = $translated_ratings_stars*$translated_ratings_count;
		$expected_average_rating = number_format( ( $original_ratings_sum + $translated_ratings_sum ) / $expected_reviews_count, 2, '.', '' );
		
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args'   => array( $translated_product_id, '_wcml_average_rating', $expected_average_rating ),
			'times'  => 1,
			'return' => true
		));
		\WP_Mock::userFunction( 'update_post_meta', array(
			'args'   => array( $product_id, '_wcml_average_rating', $expected_average_rating ),
			'times'  => 1,
			'return' => true
		));

		\WP_Mock::userFunction( 'sanitize_text_field', array(
			'args'   => array( $product_id ),
			'times'  => 1,
			'return' => $product_id
		));

		$subject->add_comment_rating( mt_rand( 501, 600 ) );
		
	}

	/**
	 * @test
	 */
	public function filter_average_rating_return_original() {

		$subject = $this->get_subject();

		$value     = rand_str();
		$object_id = mt_rand( 1, 100 );

		\WP_Mock::userFunction( 'get_post_type', array(
			'args'   => array( $object_id ),
			'return' => 'product'
		) );

		$meta_key       = rand_str();
		$filtered_value = $subject->filter_average_rating( $value, $object_id, $meta_key, false );

		$this->assertEquals( $value, $filtered_value );

	}

	/**
	 * @test
	 */
	public function filter_average_rating_return_filtered() {

		$subject = $this->get_subject();

		$value     = rand_str();
		$object_id = mt_rand( 1, 100 );

		\WP_Mock::userFunction( 'get_post_type', array(
			'args'   => array( $object_id ),
			'return' => 'product'
		) );

		$meta_key            = '_wc_average_rating';
		$wcml_average_rating = mt_rand( 101, 200 );

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $object_id, '_wcml_average_rating', false ),
			'return' => $wcml_average_rating
		) );

		\WP_Mock::userFunction( 'get_the_ID', array(
			'return' => $object_id,
		) );

		\WP_Mock::userFunction( 'metadata_exists', array(
			'args'   => array( 'post', $object_id, '_wcml_rating_count' ),
			'return' => true,
		) );

		$filtered_value = $subject->filter_average_rating( $value, $object_id, $meta_key, false );

		$this->assertEquals( $wcml_average_rating, $filtered_value );
	}

	/**
	 * @test
	 */
	public function filter_average_rating_return_rating_count_filtered() {

		$subject = $this->get_subject();

		$value     = rand_str();
		$object_id = mt_rand( 1, 100 );

		\WP_Mock::userFunction( 'get_post_type', array(
			'args'   => array( $object_id ),
			'return' => 'product'
		) );

		\WP_Mock::userFunction( 'get_the_ID', array(
			'return' => $object_id,
		) );

		\WP_Mock::userFunction( 'metadata_exists', array(
			'args'   => array( 'post', $object_id, '_wcml_rating_count' ),
			'return' => true,
		) );

		$meta_key          = '_wc_rating_count';
		$wcml_rating_count = mt_rand( 101, 200 );

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $object_id, '_wcml_rating_count', false ),
			'return' => $wcml_rating_count
		) );

		$filtered_value = $subject->filter_average_rating( $value, $object_id, $meta_key, false );

		$this->assertEquals( $wcml_rating_count, $filtered_value );
	}

	/**
	 * @test
	 */
	public function filter_average_rating_return_count_for_all_languages(){

		$subject = $this->get_subject();

		$value     = rand_str();
		$object_id = mt_rand( 1, 100 );

		\WP_Mock::userFunction( 'get_post_type', array(
			'args'   => array( $object_id ),
			'return' => 'product'
		) );


		$meta_key = '_wc_review_count';
		$wcml_review_count = mt_rand( 201, 300 );
		$_GET['clang'] = 'all';
		
		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $object_id, '_wcml_review_count', false ),
			'return' => $wcml_review_count
		));

		\WP_Mock::userFunction( 'get_the_ID', array(
			'return' => $object_id,
		) );

		\WP_Mock::userFunction( 'metadata_exists', array(
			'args'   => array( 'post', $object_id, '_wcml_rating_count' ),
			'return' => true,
		) );

		$filtered_value = $subject->filter_average_rating( $value, $object_id, $meta_key, false );

		$this->assertEquals( $wcml_review_count, $filtered_value );
	}

	/**
	 * @test
	 */
	public function comments_clauses(){
		$product_id = mt_rand( 1, 100 );
		$translated_product_id = mt_rand( 100, 200 );
		$trid = mt_rand( 200, 300 );
		$_GET['clang'] = 'all';

		\WP_Mock::userFunction( 'get_post_type', array(
			'args'   => array( $product_id ),
			'return' => 'product'
		));

		FunctionMocker::replace( '\WCML\Utilities\DB::prepareIn', function( $args ) {
			return implode( ',', $args );
		} );

		$translations = array();
		$translations[] = $product_id;
		$translations[] = $translated_product_id;

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get_element_translations' ) )
		                               ->getMock();

		$wpml_post_translations->expects( $this->once() )->method( 'get_element_translations' )->with( $product_id )->willReturn( $translations );

		$subject = $this->get_subject( false, false, $wpml_post_translations );
		
		$clauses = array();
		$clauses['where'] = 'comment_post_ID = ' . $product_id;
		$obj = new StdClass();
		$obj->query_vars['post_id'] = $product_id;

		$filtered_comments_clauses = $subject->comments_clauses( $clauses, $obj );
		
		$ids              = implode( ',', array( $product_id, $translated_product_id ) );
		$expected_comments_clauses_where = 'comment_post_ID IN (' . $ids . ')';
		
		$this->assertEquals( $expected_comments_clauses_where, $filtered_comments_clauses['where']);
		
	}

	/**
	 * @test
	 * @dataProvider comments_link_data
	 */
	public function comments_link( $is_product, $clang_in_current_url, $default_all, $expected ) {
		if ( $clang_in_current_url ) {
			$_GET[ 'clang' ] = $clang_in_current_url;
		}
		$_SERVER['HTTP_HOST'] = 'example.com';
		$_SERVER['REQUEST_URI'] = '/foo/';

		$post_id = 10;
		$all_reviews = 100;
		$reviews_in_current_language = 11;

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_setting' ) )
			->getMock();
		$woocommerce_wpml->method( 'get_setting' )->willReturn( $default_all );

		$sitepress = $this->getMockBuilder( SitePress::class )
			->disableOriginalConstructor()
			->setMethods( array( 'get_current_language', 'get_language_details' ) )
			->getMock();
		$sitepress->method( 'get_language_details' )->with( 'en' )->willReturn( [ 'display_name' => 'English' ] );
		$sitepress->method( 'get_current_language' )->willReturn( 'en' );


		$subject = $this->get_subject( $woocommerce_wpml, $sitepress );

		WP_Mock::userFunction( 'is_ssl', [
			'return' => false
		] );

		WP_Mock::userFunction( 'is_product', [
			'return' => $is_product
		] );

		WP_Mock::userFunction( 'get_the_ID', [
			'return' => $post_id
		] );

		WP_Mock::userFunction( 'add_query_arg', [
			'args' => [ [ 'clang' => 'all' ] ],
			'return' => 'http://example.com/foo/?clang=all'
		] );

		WP_Mock::userFunction( 'add_query_arg', [
			'args' => [ [ 'clang' => 'en' ] ],
			'return' => 'http://example.com/foo/?clang=en'
		] );

		WP_Mock::passthruFunction( 'remove_filter' );

		WP_Mock::userFunction( 'metadata_exists', [
			'return' => true
		] );

		WP_Mock::userFunction( 'get_post_meta', [
			'return_in_order' => [ $all_reviews, $reviews_in_current_language ]
		] );

		WP_Mock::userFunction( 'get_post_type', [
			'return' => $is_product ? 'product' : 'not_product'
		] );

		$this->expectOutputRegex( $expected );

		$subject->comments_link();

		unset( $_GET['clang'] );

	}

	public function comments_link_data() {
		// $is_product, $clang_in_current_url, $default_all, $expected
		return [
			[ true, null, 0, '/all-languages-reviews/' ],
			[ true, null, 1, '/current-language-reviews/' ],
			[ true, 'en', 0, '/all-languages-reviews/' ],
			[ true, 'all', 0, '/current-language-reviews/' ],
			[ true, 'en', 1, '/all-languages-reviews/' ],
			[ true, 'all', 1, '/current-language-reviews/' ],
			[ 'false', null, 0, '//' ],
		];
	}

	/**
	 * @test
	 */
	public function missing_counts_calculated_on_the_fly() {
		$product_id = 123;
		$translations = [ $product_id ];

		$product = $this->getMockBuilder( 'WC_Product' )
			->disableOriginalConstructor()
			->getMock();

		\WP_Mock::userFunction( 'wc_get_product', [
			'args'   => [ $product_id ],
			'return' => $product,
		] );

		$wc_comment = \Mockery::mock( 'overload:WC_Comments' );
		$wc_comment->shouldReceive( 'get_rating_counts_for_product' )->with( $product )->andReturn( [] );
		$wc_comment->shouldReceive( 'get_review_count_for_product' )->with( $product )->andReturn( 0 );

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		     ->disableOriginalConstructor()
		     ->setMethods( array( 'get_element_translations' ) )
		     ->getMock();

		$wpml_post_translations->method( 'get_element_translations' )->with( $product_id )->willReturn( $translations );

		$subject = $this->get_subject( false, false, $wpml_post_translations );

		WP_Mock::userFunction( 'get_the_ID', [
			'return' => $product_id
		] );

		WP_Mock::passthruFunction( 'remove_filter' );

		WP_Mock::userFunction( 'metadata_exists', [
			'return' => false
		] );

		$subject->get_reviews_count( 'en' );
	}

	/**
	 * @test
	 */
	public function it_recalculates_on_comment() {
		$product_id   = 123;
		$translations = [ $product_id ];
		$comment_id   = 456;

		$comment = $this->getMockBuilder( 'WC_Comment' )
			->disableOriginalConstructor()
			->getMock();
		$comment->comment_post_ID = $product_id;

		\WP_Mock::userFunction( 'get_comment', [
			'args'   => [ $comment_id ],
			'return' => $comment,
		] );

		\WP_Mock::userFunction( 'get_post_type', [
			'args'   => $product_id,
			'return' => 'product',
		] );

		$product = $this->getMockBuilder( 'WC_Product' )
			->disableOriginalConstructor()
			->getMock();

		\WP_Mock::userFunction( 'wc_get_product', [
			'args'   => [ $product_id ],
			'return' => $product,
		] );

		$wc_comment = \Mockery::mock( 'overload:WC_Comments' );
		$wc_comment->shouldReceive( 'get_rating_counts_for_product' )->with( $product )->andReturn( [] );
		$wc_comment->shouldReceive( 'get_review_count_for_product' )->with( $product )->andReturn( 0 );

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_element_translations' ) )
			->getMock();

		$wpml_post_translations->method( 'get_element_translations' )->with( $product_id )->willReturn( $translations );

		$subject = $this->get_subject( false, false, $wpml_post_translations );

		$subject->recalculate_average_rating_on_comment_hook( $comment_id, $comment );
	}

	/**
	 * @test
	 */
	public function is_comment_query_filtered(){

		$subject = $this->get_subject( );

		$product_id = mt_rand( 1, 100 );

		$_GET['clang'] = 'all';

		\WP_Mock::userFunction( 'get_post_type', array(
			'args'   => array( $product_id ),
			'return' => 'product'
		));
		
		$this->assertFalse( $subject->is_comment_query_filtered( true, $product_id ) );

		$product_id = mt_rand( 101, 200 );

		\WP_Mock::userFunction( 'get_post_type', array(
			'args'   => array( $product_id ),
			'return' => rand_str()
		));

		$this->assertTrue( $subject->is_comment_query_filtered( true, $product_id ) );

		$product_id                = 0;
		$comment_query             = $this->getMockBuilder( 'WP_Comment_Query' )->disableOriginalConstructor()->getMock();
		$comment_query->query_vars = [ 'post_type' => 'product' ];

		\WP_Mock::userFunction( 'get_post_type', array(
			'args'   => array( $product_id ),
			'return' => false,
		));

		$this->assertFalse( $subject->is_comment_query_filtered( true, $product_id, $comment_query ) );

		$comment_query->query_vars = [ 'post_type' => 'post' ];

		$this->assertTrue( $subject->is_comment_query_filtered( true, $product_id, $comment_query ) );
	}

	/**
	 * @test
	 */
	public function add_comment_flag(){

		$comment = new stdClass();
		$comment->comment_post_ID = mt_rand( 1, 100 );
		$language = rand_str();
		$flag_url = rand_str();
		$flag_name = rand_str();

		$_GET['clang'] = 'all';

		\WP_Mock::userFunction( 'get_post_type', array(
			'args'   => array( $comment->comment_post_ID ),
			'return' => 'product'
		));

		$sitepress = $this->getMockBuilder( SitePress::class )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_language_for_element', 'get_flag_url', 'get_display_language_name' ) )
		                  ->getMock();

		$sitepress->method( 'get_flag_url' )->with( $language )->willReturn( $flag_url );
		$sitepress->method( 'get_display_language_name' )->with( $language )->willReturn( $flag_name );

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get_element_lang_code' ) )
		                               ->getMock();

		$wpml_post_translations->method( 'get_element_lang_code' )->with( $comment->comment_post_ID )->willReturn( $language );

		$subject = $this->get_subject( false, $sitepress, $wpml_post_translations );
		
		ob_start();
		$subject->add_comment_flag( $comment );
		$comment_flag = ob_get_clean();

		$expected_comment_flag = '<div style="float: left; padding: 6px 5px 0 0;"><img src="' . $flag_url . '" width="18" height="12" alt="' . $flag_name . '"></div>';
		$this->assertEquals( $expected_comment_flag, $comment_flag );
	}

	/**
	 * @test
	 * @dataProvider lang_div_data_provider
	 */
	public function open_and_close_lang_div( $clang, $preg ) {
		$_GET['clang'] = $clang;

		\WP_Mock::userFunction( 'get_post_type', array(
			'args'   => array( 10 ),
			'return' => 'product'
		));

		$comment = new stdClass();
		$comment->comment_post_ID = 10;

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_element_lang_code' ) )
			->getMock();

		$wpml_post_translations->method( 'get_element_lang_code' )->with( 10 )->willReturn( 'en' );

		$subject = $this->get_subject( false, false, $wpml_post_translations );

		$this->expectOutputRegex( $preg );

		$subject->open_lang_div( $comment );

		$subject->close_lang_div( $comment );

		unset( $_GET['clang'] );
	}

	public function lang_div_data_provider() {
		return [
			[ 'all', '/<div lang=\"en\"><\/div>/' ],
			[ 'en', '//' ],
		];
	}

	/**
	 * @test
	 * @group wcml-3442
	 */
	public function open_and_close_lang_div_with_translated_comment() {
		$product_id   = 123;
		$current_lang = 'fr';

		$_GET['clang'] = 'all';

		\WP_Mock::userFunction( 'get_post_type', [
			'args'   => [ $product_id ],
			'return' => 'product'
		] );

		$comment = new stdClass();
		$comment->comment_post_ID = $product_id;
		$comment->is_translated   = true;

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )
			->willReturn( $current_lang );

		$subject = $this->get_subject( null, $sitepress );

		$this->expectOutputRegex( '/<div lang=\"' . $current_lang . '\"><span class=\"wcml-review-translated\">\(translated\)<\/span><\/div>/' );

		$subject->open_lang_div( $comment );

		$subject->close_lang_div( $comment );

		unset( $_GET['clang'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_recalculate_comment_rating_for_corrupted_data(){

		$product_id = 1;
		$translated_product_id = 2;

		$translations = array();
		$translations[] = $translated_product_id;

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get_element_translations' ) )
		                               ->getMock();

		$wpml_post_translations->method( 'get_element_translations' )->with( $product_id )->willReturn( $translations );

		$translated_product_obj = $this->getMockBuilder( 'WC_Product' )
		                               ->disableOriginalConstructor()
		                               ->setMethods()
		                               ->getMock();

		\WP_Mock::userFunction( 'wc_get_product', array(
			'args'   => array( $translated_product_id ),
			'return' => $translated_product_obj
		));

		$wc_comment = \Mockery::mock( 'overload:WC_Comments' );
		$wc_comment->shouldReceive( 'get_rating_counts_for_product' )->with( $translated_product_obj )->andReturn( 'corrupted_data' );
		$wc_comment->shouldReceive( 'get_review_count_for_product' )->with( $translated_product_obj )->andReturn( 10 );

		$subject = $this->get_subject( false, false, $wpml_post_translations );

		\WP_Mock::userFunction( 'update_post_meta', array(
			'args'   => array( $translated_product_id, '_wcml_average_rating', 0 ),
			'times'  => 0,
			'return' => false
		));

		$subject->recalculate_comment_rating( $product_id );

	}

	/**
	 * @test
	 */
	public function it_should_not_recalculate_reviews_on_trash_for_non_products(){

		$comment_id = 101;
		$comment = $this->getMockBuilder( 'WP_Comment' )
		                ->disableOriginalConstructor()
		                ->setMethods()
		                ->getMock();
		$comment->comment_post_ID = 11;

		\WP_Mock::userFunction( 'get_post_type', array(
			'args'   => array( $comment->comment_post_ID ),
			'return' => 'non_product'
		));

		$subject = $this->get_subject();
		$subject->recalculate_average_rating_on_comment_hook( $comment_id, $comment );
	}

	/**
	 * @test
	 */
	public function it_should_not_recalculate_rating_on_non_products(){

		$product_id = 5;
		$translated_product_id_1 = 13;
		$translated_product_id_2 = 57;

		$translations = array();
		$translations[] = $translated_product_id_1;
		$translations[] = $translated_product_id_2;

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_element_translations' ) )
			->getMock();

		$wpml_post_translations->method( 'get_element_translations' )->with( $product_id )->willReturn( $translations );

		\WP_Mock::userFunction( 'wc_get_product', array(
			'args'   => $translated_product_id_1,
			'return' => false,
		));

		\WP_Mock::userFunction( 'wc_get_product', array(
			'args'   => $translated_product_id_2,
			'return' => null,
		));

		$subject = $this->get_subject( false, false, $wpml_post_translations );

		\WP_Mock::userFunction( 'update_post_meta', array(
			'args'   => array( $translated_product_id_1, '_wcml_average_rating', 0 ),
			'times'  => 0,
			'return' => false
		));

		\WP_Mock::userFunction( 'update_post_meta', array(
			'args'   => array( $translated_product_id_2, '_wcml_average_rating', 0 ),
			'times'  => 0,
			'return' => false
		));

		$subject->recalculate_comment_rating( $product_id );
	}

	/**
	 * @test
	 */
	public function it_should_get_comment_object_for_WP_lower_4_9(){

		$comment_id = 101;
		$comment = $this->getMockBuilder( 'WP_Comment' )
		                ->disableOriginalConstructor()
		                ->setMethods()
		                ->getMock();
		$comment->comment_post_ID = 11;

		\WP_Mock::userFunction( 'get_comment', array(
			'args'   => array( $comment_id ),
			'return' => $comment
		));

		\WP_Mock::userFunction( 'get_post_type', array(
			'args'   => array( $comment->comment_post_ID ),
			'return' => 'non_product'
		));

		$subject = $this->get_subject();
		$subject->recalculate_average_rating_on_comment_hook( $comment_id, null );
	}


	/**
	 * @test
	 */
	public function it_should_filter_top_rated_products_widget_args() {

		$args             = [];
		$args['meta_key'] = rand_str();

		$subject = $this->get_subject();

		$filtered_args = $subject->top_rated_products_widget_args( $args );

		$this->assertEquals( '_wcml_average_rating', $filtered_args['meta_key'] );
	}

	/**
	 * @test
	 */
	public function it_should_filter_woocommerce_rating_count() {

		$label         = rand_str();
		$count         = 2;
		$rating        = 4;
		$expectedCount = 1;

		$current_language = 'es';

		$ratingTerm                   = new stdClass();
		$ratingTerm->term_taxonomy_id = 10;


		$sitepress = $this->getMockBuilder( SitePress::class )
		                  ->disableOriginalConstructor()
		                  ->setMethods( [ 'get_current_language' ] )
		                  ->getMock();

		$sitepress->method( 'get_current_language' )->willReturn( $current_language );

		$wpdb = $this->stubs->wpdb();
		$wpdb->method( 'get_var' )->willReturn( $expectedCount );

		$subject = $this->get_subject( false, $sitepress, false, $wpdb );

		\WP_Mock::userFunction( 'get_term_by', [
			'args'   => [ 'name', 'rated-' . $rating, 'product_visibility' ],
			'return' => $ratingTerm
		] );

		$filtered_count = $subject->woocommerce_rating_filter_count( $label, $count, $rating );

		$this->assertEquals( "({$expectedCount})", $filtered_count );
	}

	/**
	 * @test
	 */
	public function it_should_duplicate_comment_rating() {

		\WP_Mock::passthruFunction( 'remove_action' );

		$rating                = 4;
		$comment_id            = 6;
		$duplicated_comment_id = 7;

		$duplicated_comments = [ $duplicated_comment_id ];
		$wpdb                = $this->stubs->wpdb();
		$wpdb->method( 'get_col' )->willReturn( $duplicated_comments );

		\WP_Mock::userFunction( 'wpml_get_setting_filter', [
			'args'   => [ null, 'sync_comments_on_duplicates' ],
			'return' => true
		] );

		\WP_Mock::userFunction( 'add_comment_meta', [
			'args'   => [ $duplicated_comment_id, 'rating', $rating ],
			'return' => true
		] );

		$comment                  = new stdClass();
		$comment->comment_post_ID = 12;

		\WP_Mock::userFunction( 'get_comment', [
			'args'   => [ $comment_id ],
			'return' => $comment
		] );

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get_element_translations' ) )
		                               ->getMock();

		$wpml_post_translations->method( 'get_element_translations' )->with( $comment->comment_post_ID )->willReturn( [] );

		$subject = $this->get_subject( false, false, $wpml_post_translations, $wpdb );

		$subject->maybe_duplicate_comment_rating( 2, $comment_id, 'rating', $rating );
	}

	/**
	 * @test
	 */
	public function it_should_not_duplicate_comment_rating_when_sync_comments_on_duplicates_is_off() {

		\WP_Mock::userFunction( 'wpml_get_setting_filter', [
			'args'   => [ null, 'sync_comments_on_duplicates' ],
			'return' => false
		] );

		$subject = $this->get_subject();

		$subject->maybe_duplicate_comment_rating( 2, 12, 'rating', 4 );
	}

	/**
	 * @test
	 */
	public function it_should_not_duplicate_for_not_rating_meta_key() {

		$subject = $this->get_subject();

		$subject->maybe_duplicate_comment_rating( 2, 23, 'verified', 0 );
	}

	/**
	 * @test
	 * @group wcml-3313
	 */
	public function it_should_return_zero_when_comment_rating_is_blank() {

		$subject = $this->get_subject();

		$value     = "4";
		$object_id = "13";

		\WP_Mock::userFunction( 'get_post_type', array(
			'args'   => array( $object_id ),
			'return' => 'product'
		) );

		$meta_key            = '_wc_average_rating';
		$wcml_average_rating = "";

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $object_id, '_wcml_average_rating', false ),
			'return' => $wcml_average_rating
		) );

		\WP_Mock::userFunction( 'get_the_ID', array(
			'return' => $object_id,
		) );

		\WP_Mock::userFunction( 'metadata_exists', array(
			'args'   => array( 'post', $object_id, '_wcml_rating_count' ),
			'return' => true,
		) );

		$filtered_value = $subject->filter_average_rating( $value, $object_id, $meta_key, false );

		$this->assertEquals( 0, $filtered_value );
	}

	/**
	 * @test
	 */
	public function it_adds_noindex_to_all_reviews_page() {
		global $_GET;
		$_GET['clang'] = 'all';

		$subject = $this->get_subject();

		ob_start();
		$subject->no_index_all_reviews_page();
		$meta = ob_get_clean();

		$expected = '<meta name="robots" content="noindex">';

		$this->assertEquals( $meta, $expected );

		unset( $_GET );
	}

	/**
	 * @test
	 */
	public function it_resets_meta_when_no_ratings() {
		$product_id    = 123;
		$tr_product_id = 456;
		$translations  = [ $product_id, $tr_product_id ];

		$product = $this->getMockBuilder( 'WC_Product' )
			->disableOriginalConstructor()
			->getMock();

		\WP_Mock::userFunction( 'wc_get_product', [
			'args'   => [ $product_id ],
			'return' => $product,
		] );

		$tr_product = $this->getMockBuilder( 'WC_Product' )
			->disableOriginalConstructor()
			->getMock();

		\WP_Mock::userFunction( 'wc_get_product', [
			'args'   => [ $tr_product_id ],
			'return' => $tr_product,
		] );

		$wc_comment = \Mockery::mock( 'overload:WC_Comments' );
		$wc_comment->shouldReceive( 'get_rating_counts_for_product' )->with( $product )->andReturn( [] );
		$wc_comment->shouldReceive( 'get_review_count_for_product' )->with( $product )->andReturn( 0 );
		$wc_comment->shouldReceive( 'get_rating_counts_for_product' )->with( $tr_product )->andReturn( [] );
		$wc_comment->shouldReceive( 'get_review_count_for_product' )->with( $tr_product )->andReturn( 0 );

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		     ->disableOriginalConstructor()
		     ->setMethods( array( 'get_element_translations' ) )
		     ->getMock();

		$wpml_post_translations->method( 'get_element_translations' )->with( $product_id )->willReturn( $translations );

		$subject = $this->get_subject( false, false, $wpml_post_translations );

		\WP_Mock::userFunction( 'update_post_meta', [
			'args'  => [ $product_id, WCML_Comments::WCML_AVERAGE_RATING_KEY, null ],
			'times' => 1,
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
			'args'  => [ $product_id, WCML_Comments::WCML_REVIEW_COUNT_KEY, null ],
			'times' => 1,
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
			'args'  => [ $product_id, WCML_Comments::WCML_RATING_COUNT_KEY, null ],
			'times' => 1,
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
			'args'  => [ $tr_product_id, WCML_Comments::WCML_AVERAGE_RATING_KEY, null ],
			'times' => 1,
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
			'args'  => [ $tr_product_id, WCML_Comments::WCML_REVIEW_COUNT_KEY, null ],
			'times' => 1,
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
			'args'  => [ $tr_product_id, WCML_Comments::WCML_RATING_COUNT_KEY, null ],
			'times' => 1,
		] );

		$subject->recalculate_comment_rating( $product_id );
	}

	/**
	 * @test
	 */
	public function test_translating_post_ids_in_reviews() {
		$original_product_id   = 123;
		$translated_product_id = 456;

		$comment = $this->getMockBuilder( WP_Comment::class )
			->disableOriginalConstructor()
			->getMock();
		$comment->comment_post_ID = $original_product_id;
		$comment->comment_type    = 'review';

		$comments = [ $comment ];

		FunctionMocker::replace( Ids::class . '::convert', $translated_product_id );

		$subject = $this->get_subject();
		$results = $subject->translate_product_ids( $comments );

		$this->assertEquals( $translated_product_id, $results[0]->comment_post_ID );
	}

	/**
	 * @test
	 */
	public function test_does_not_translate_post_ids_in_comments() {
		$original_product_id   = 123;
		$translated_product_id = 456;

		$comment = $this->getMockBuilder( WP_Comment::class )
			->disableOriginalConstructor()
			->getMock();
		$comment->comment_post_ID = $original_product_id;
		$comment->comment_type    = 'comment';

		$comments = [ $comment ];

		FunctionMocker::replace( Ids::class . '::convert', $translated_product_id );

		$subject = $this->get_subject();
		$results = $subject->translate_product_ids( $comments );

		$this->assertEquals( $original_product_id, $results[0]->comment_post_ID );
	}

}
