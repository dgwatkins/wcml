<?php

namespace WCML\Reviews\Translations;

/**
 * @group reviews
 * @group wcml-3442
 */
class TestFrontEndHooks extends \OTGS_TestCase {

	const COMMENT_ID          = 123;
	const COMMENT_POST_ID     = 456;
	const COMMENT_STRING_NAME = 'product-' . self::COMMENT_POST_ID . '-review-' . self::COMMENT_ID;

	private function get_subject( $wpdb = null ) {
		$wpdb = $wpdb ?: $this->getMockBuilder( '\wpdb' )->getMock();

		return new FrontEndHooks( $wpdb );
	}
	
	private function get_wp_comment( $commentContent = 'Some content', $commentType = 'review' ) {
		$comment                  = $this->getMockBuilder( 'WP_Comment' )->disableOriginalConstructor()->getMock();
		$comment->comment_ID      = self::COMMENT_ID;
		$comment->comment_post_ID = self::COMMENT_POST_ID;
		$comment->comment_content = $commentContent;
		$comment->comment_type    = $commentType;

		return $comment;
	}

	/**
	 * @test
	 */
	public function it_should_add_hooks() {
		$subject = $this->get_subject();

		\WP_Mock::onFilter( 'wcml_enable_product_review_translation' )
			->with( true )
			->reply( true );

		\WP_Mock::expectActionAdded( 'wp_insert_comment', [ $subject, 'insertCommentAction' ], 10, 2 );
		\WP_Mock::expectActionAdded( 'woocommerce_review_before',      [ $subject, 'translateReview' ] );
		\WP_Mock::expectFilterAdded( 'woocommerce_product_get_rating_counts', [ $subject, 'getRatingCount' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_should_NOT_add_hooks_if_feature_disabled() {
		$subject = $this->get_subject();

		\WP_Mock::onFilter( 'wcml_enable_product_review_translation' )
			->with( true )
			->reply( false );

		\WP_Mock::expectActionNotAdded( 'wp_insert_comment', [ $subject, 'insertCommentAction' ] );
		\WP_Mock::expectActionNotAdded( 'woocommerce_review_before',      [ $subject, 'translateReview' ] );
		\WP_Mock::expectFilterNotAdded( 'woocommerce_product_get_rating_counts', [ $subject, 'getRatingCount' ] );

		$subject->add_hooks();
	}
	
	/**
	 * @test
	 */
	public function it_returns_ratings_count(){
		$post_id = 48;
		
		\WP_Mock::userFunction( 'get_the_ID', [
			'return' => $post_id
		] );
		
		$product_obj = $this->getMockBuilder( 'WC_Product' )
		                    ->disableOriginalConstructor()
		                    ->setMethods( ['get_average_rating'] )
		                    ->getMock();
		
		$product_obj->method( 'get_average_rating' )->willReturn( '5' );
		
		\WP_Mock::userFunction( 'wc_get_product', array(
			'args'   => array( $post_id ),
			'return' => $product_obj
		));
		
		//$this->get_subject()->getRatingCount();

	}
	
	/**
	 * @test
	 * @dataProvider registerCommentString
	 */
	public function it_registers_comment_string( $commentContent, $commentType, $shouldRegister ) {
		$comment = $this->get_wp_comment( $commentContent, $commentType );
		
		\WP_Mock::onFilter( 'wpml_current_language' )->with( null )->reply( 'pl' );

		$this->expectAction(
			'wpml_register_single_string',
			[ 'wcml-reviews', self::COMMENT_STRING_NAME, $comment->comment_content, false, 'pl' ],
			(int) $shouldRegister
		);

		$this->get_subject()->insertCommentAction( $comment->comment_ID, $comment );
	}
	
	public function registerCommentString() {
		return [
			'valid review' => [ 'Some content', 'review', true ],
			'empty review' =>[ '', 'review', false ],
			'not a review' =>[ 'Some content', 'no-a-review', false ],

		];
	}

	public function it_should_NOT_translate_non_review_comments() {
		$comment         = $this->get_wp_comment( 'Some comment', 'not-a-review' );
		$originalComment = clone $comment;

		$this->get_subject()->translateReview( $comment );

		$this->assertSame( $originalComment, $comment );

	}
	
	/**
	 * @test
	 */
	public function it_should_NOT_translate_review_if_string_is_NOT_translated() {
		$comment         = $this->get_wp_comment( 'Some content', 'review' );
		$originalComment = clone $comment;
		
		\WP_Mock::onFilter( 'wpml_translate_single_string' )
		        ->with( $comment->comment_content, 'wcml-reviews', self::COMMENT_STRING_NAME )
		        ->reply( $comment->comment_content );

		$this->get_subject()->translateReview( $comment );

		$this->assertEquals( $originalComment, $comment );
	}

	/**
	 * @test
	 */
	public function it_should_translate_review() {
		$originalString   = 'Some content';
		$translatedString = 'Translated content';

		$comment         = $this->get_wp_comment( $originalString, 'review' );
		$originalComment = clone $comment;

		\WP_Mock::onFilter( 'wpml_translate_single_string' )
		        ->with( $originalString, 'wcml-reviews', self::COMMENT_STRING_NAME )
		        ->reply( $translatedString );

		$this->get_subject()->translateReview( $comment );

		$this->assertNotEquals( $originalComment, $comment );
		$this->assertTrue( $comment->is_translated );
		$this->assertEquals( $translatedString, $comment->comment_content );
	}
}