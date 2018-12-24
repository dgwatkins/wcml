<?php

class Test_WCML_Comments extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;

	private $scheme      = 'http://';
	private $http_host   = 'domain.tld';
	private $request_uri = '/some/path/';

	public function setUp()
	{
		parent::setUp();
		\WP_Mock::wpPassthruFunction( '__' );
		
	}

	private function get_woocommerce_wpml(){
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	private function get_sitepress() {
		return $this->getMockBuilder( 'SitePress' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	private function get_wpml_post_translations() {
		return $this->getMockBuilder( 'WPML_Post_Translation' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}


	private function get_subject( $woocommerce_wpml = false, $sitepress = false, $wpml_post_translations = false ){
		
		if( !$woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}
		
		if( !$sitepress ){
			$sitepress = $this->get_sitepress();
		}

		if( !$wpml_post_translations ){
			$wpml_post_translations = $this->get_wpml_post_translations();
		}
		
		return new WCML_Comments( $woocommerce_wpml, $sitepress, $wpml_post_translations );
	}

	/**
	 * @test
	 */
	public function add_hooks(){

		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'comment_post', array( $subject, 'add_comment_rating' ) );
		\WP_Mock::expectActionAdded( 'woocommerce_review_before_comment_meta', array( $subject, 'add_comment_flag' ), 9 );

		\WP_Mock::expectFilterAdded( 'get_post_metadata', array( $subject, 'filter_average_rating' ), 10, 4 );
		\WP_Mock::expectFilterAdded( 'comments_clauses', array( $subject, 'comments_clauses' ), 10, 2 );
		\WP_Mock::expectFilterAdded( 'woocommerce_product_review_list_args', array( $subject, 'comments_link' ) );
		\WP_Mock::expectFilterAdded( 'wpml_is_comment_query_filtered', array( $subject, 'is_comment_query_filtered' ), 10, 2 );

		$subject->add_hooks();
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
		
		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $product_id ),
			'return' => 'product'
		));

		$original_ratings_stars = mt_rand( 1, 5 );
		$original_ratings_count = mt_rand( 301, 400 );
		$original_ratings = array( $original_ratings_stars => $original_ratings_count );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_id, '_wc_rating_count', true ),
			'return' => $original_ratings
		));
		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_id, '_wc_review_count', true ),
			'return' => $original_ratings_count
		));
		
		$translated_ratings_stars = mt_rand( 1, 5 );
		$translated_ratings_count = mt_rand( 401, 500 );
		$translated_ratings = array( $translated_ratings_stars => $translated_ratings_count );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $translated_product_id, '_wc_rating_count', true ),
			'return' => $translated_ratings
		));
		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $translated_product_id, '_wc_review_count', true ),
			'return' => $translated_ratings_count
		));

		$expected_reviews_count = $original_ratings_count + $translated_ratings_count;

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'   => array( $translated_product_id, '_wcml_review_count', $expected_reviews_count ),
			'times'   => 1,
			'return' => true
		));
		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'   => array( $product_id, '_wcml_review_count', $expected_reviews_count ),
			'times'   => 1,
			'return' => true
		));
		
		$original_ratings_sum = $original_ratings_stars*$original_ratings_count;
		$translated_ratings_sum = $translated_ratings_stars*$translated_ratings_count;
		$expected_average_rating = number_format( ( $original_ratings_sum + $translated_ratings_sum ) / $expected_reviews_count, 2, '.', '' );
		
		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'   => array( $translated_product_id, '_wcml_average_rating', $expected_average_rating ),
			'times'   => 1,
			'return' => true
		));
		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'   => array( $product_id, '_wcml_average_rating', $expected_average_rating ),
			'times'   => 1,
			'return' => true
		));

		\WP_Mock::wpFunction( 'sanitize_text_field', array(
			'args'   => array( $product_id ),
			'times'   => 1,
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

		\WP_Mock::wpFunction( 'get_post_type', array(
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

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $object_id ),
			'return' => 'product'
		) );

		$meta_key            = '_wc_average_rating';
		$wcml_average_rating = mt_rand( 101, 200 );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $object_id, '_wcml_average_rating', false ),
			'return' => $wcml_average_rating
		) );

		$filtered_value = $subject->filter_average_rating( $value, $object_id, $meta_key, false );

		$this->assertEquals( $wcml_average_rating, $filtered_value );
	}

	/**
	 * @test
	 */
	public function filter_average_rating_return_count_for_all_languages(){

		$subject = $this->get_subject();

		$value     = rand_str();
		$object_id = mt_rand( 1, 100 );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $object_id ),
			'return' => 'product'
		) );


		$meta_key = '_wc_review_count';
		$wcml_review_count = mt_rand( 201, 300 );
		$_GET['clang'] = 'all';
		
		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $object_id, '_wcml_review_count', false ),
			'return' => $wcml_review_count
		));

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

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $product_id ),
			'return' => 'product'
		));
		
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
	 */
	public function comments_link(){

		$current_language = rand_str();
		$language_details = array();
		$language_details['display_name'] = rand_str();

		$_SERVER['HTTP_HOST']   = $this->http_host;
		$_SERVER['REQUEST_URI'] = $this->request_uri;
		$url = $this->scheme . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_current_language', 'get_language_details' ) )
		                  ->getMock();

		$sitepress->method( 'get_current_language' )->willReturn( $current_language );
		$sitepress->expects( $this->once() )->method( 'get_language_details' )->with( $current_language )->willReturn( $language_details );

		$subject = $this->get_subject( false, $sitepress );

		\WP_Mock::wpFunction( 'is_ssl', array(
			'times'   => 2,
			'return' => false
		));

		\WP_Mock::wpFunction( 'remove_filter', array(
			'return' => true
		));

		$product_id = mt_rand( 1, 100 );

		\WP_Mock::wpFunction( 'get_the_ID', array(
			'times'   => 6,
			'return' => $product_id
		));

		$this->comments_link_to_current_language( $subject, $product_id, $url, $current_language, $language_details );
		$this->comments_link_to_all_languages( $subject, $product_id, $url, $current_language );

	}

	private function comments_link_to_current_language( $subject, $product_id, $url, $current_language, $language_details ){

		$_GET['clang'] = 'all';
		$new_query_args = array( 'clang' => $current_language );
		$expected_url   = $url . '?' . http_build_query( $new_query_args );
		$wc_review_count = mt_rand( 201, 300 );

		\WP_Mock::wpFunction( 'add_query_arg', array(
			'args'   => array( $new_query_args, $url ),
			'return' => $expected_url,
		));

		\WP_Mock::wpFunction( 'metadata_exists', array(
			'args'   => array( 'post', $product_id, '_wcml_review_count' ),
			'return' => $expected_url,
		));

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_id, '_wc_review_count', true ),
			'return' => $wc_review_count
		));

		ob_start();
		$subject->comments_link( array() );
		$link = ob_get_clean();

		$expected_link = '<p><a id="lang-comments-link" href="' . $expected_url . '">Show only reviews in '.$language_details['display_name'].' ('.$wc_review_count.')</a></p>';
		$this->assertEquals( $expected_link, $link );
	}

	private function comments_link_to_all_languages( $subject, $product_id, $url, $current_language ){

		$_GET['clang'] = $current_language;
		$new_query_args = array( 'clang' => 'all' );
		$expected_url   = $url . '?' . http_build_query( $new_query_args );
		$current_review_count = mt_rand( 201, 300 );
		$all_wcml_review_count = mt_rand( 301, 400 );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_id, '_wc_review_count', true ),
			'return' => $current_review_count
		));

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_id, '_wcml_review_count', true ),
			'return' => $all_wcml_review_count
		));

		\WP_Mock::wpFunction( 'add_query_arg', array(
			'args'   => array( $new_query_args, $url ),
			'return' => $expected_url,
		));

		ob_start();
		$subject->comments_link( array() );
		$link = ob_get_clean();

		$expected_link = '<p><a id="lang-comments-link" href="' . $expected_url . '">Show reviews in all languages  ('.$all_wcml_review_count.')</a></p>';
		$this->assertEquals( $expected_link, $link );
	}
	
	/**
	 * @test
	 */
	public function is_comment_query_filtered(){

		$subject = $this->get_subject( );

		$product_id = mt_rand( 1, 100 );

		$_GET['clang'] = 'all';

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $product_id ),
			'return' => 'product'
		));
		
		$this->assertFalse( $subject->is_comment_query_filtered( true, $product_id ) );

		$product_id = mt_rand( 101, 200 );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $product_id ),
			'return' => rand_str()
		));

		$this->assertTrue( $subject->is_comment_query_filtered( true, $product_id ) );
	}

	/**
	 * @test
	 */
	public function add_comment_flag(){

		$comment = new stdClass();
		$comment->comment_post_ID = mt_rand( 1, 100 );
		$language = rand_str();
		$flag_url = rand_str();

		$_GET['clang'] = 'all';

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $comment->comment_post_ID ),
			'return' => 'product'
		));

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_language_for_element', 'get_flag_url' ) )
		                  ->getMock();

		$sitepress->method( 'get_flag_url' )->with( $language )->willReturn( $flag_url );

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get_element_lang_code' ) )
		                               ->getMock();

		$wpml_post_translations->method( 'get_element_lang_code' )->with( $comment->comment_post_ID )->willReturn( $language );

		$subject = $this->get_subject( false, $sitepress, $wpml_post_translations );
		
		ob_start();
		$subject->add_comment_flag( $comment );
		$comment_flag = ob_get_clean();

		$expected_comment_flag = '<div style="float: left; padding-right: 5px;"><img src="' . $flag_url . '" width=18" height="12"></div>';
		$this->assertEquals( $expected_comment_flag, $comment_flag );
	}

}
