<?php

class Test_WCML_Order_Status_Manager extends OTGS_TestCase {

	private function get_subject( $posts = array() ) {
		$wp_query = $this->getMockBuilder( 'WP_Query' )
		                 ->setMethods( array( 'query' ) )
		                 ->getMock();

		$wp_query->posts = $posts;

		$subject = new WCML_Order_Status_Manager( $wp_query );

		return $subject;
	}

	/**
	 * @test
	 */
	public function it_adds_the_pre_get_posts_action() {
		$subject = $this->get_subject();
		\WP_Mock::expectActionAdded( 'pre_get_posts', array( $subject, 'pre_get_posts' ), 10, 1 );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function pre_get_post_called_without_parameter() {
		$subject = $this->get_subject();

		$this->assertNull( $subject->pre_get_posts() );
	}

	/**
	 * @test
	 */
	public function pre_get_post_called_with_empty_object() {
		$subject = $this->get_subject();

		$this->assertNull( $subject->pre_get_posts( new stdClass() ) );
	}

	/**
	 * @test
	 */
	public function it_does_not_add_to_query_when_there_are_no_statuses() {

		$existing_not_in = array( 1, 2, 3 );

		$subject = $this->get_subject();

		$this->expect_action_is_removed_and_then_added( $subject );

		$q = $this->prepare_query_var( $existing_not_in );
		$q->expects( $this->once() )->method( 'set' )->with( 'post__not_in', $existing_not_in );

		$subject->pre_get_posts( $q );
	}

	/**
	 * @test
	 */
	public function it_does_not_add_to_query_when_there_are_statuses_in_the_same_language() {

		$current_language = 'fr';

		$existing_not_in = array( 1, 2, 3 );
		$status_posts    = array(
			(object) array( 'ID' => 10, ),
		);
		$subject         = $this->get_subject( $status_posts );

		$this->expect_action_is_removed_and_then_added( $subject );

		$this->given_current_language( $current_language );

		$this->given_post_language( $status_posts[0]->ID, $current_language );

		$q = $this->prepare_query_var( $existing_not_in );
		$q->expects( $this->once() )->method( 'set' )->with( 'post__not_in', $existing_not_in );

		$subject->pre_get_posts( $q );
	}

	/**
	 * @test
	 */
	public function it_adds_to_query_when_there_are_statuses_in_a_different_language() {

		$current_language = 'fr';
		$other_language   = 'de';

		$existing_not_in = array( 1, 2, 3 );
		$status_posts    = array(
			(object) array( 'ID' => 10, ),
		);
		$subject         = $this->get_subject( $status_posts );

		$this->expect_action_is_removed_and_then_added( $subject );

		$this->given_current_language( $current_language );

		$this->given_post_language( $status_posts[0]->ID, $other_language );

		$q = $this->prepare_query_var( $existing_not_in );
		$q->expects( $this->once() )->method( 'set' )->with( 'post__not_in', array_merge( $existing_not_in, array( $status_posts[0]->ID ) ) );

		$subject->pre_get_posts( $q );
	}

	/**
	 * @param array $existing_not_in
	 *
	 * @return WP_Query|PHPUnit_Framework_MockObject_MockObject
	 */
	private function prepare_query_var( array $existing_not_in ) {
		$q                             = $this->getMockBuilder( 'WP_Query' )
		                                      ->setMethods( array( 'set' ) )
		                                      ->getMock();
		$q->query_vars['post__not_in'] = $existing_not_in;
		$q->query['post_type']         = 'wc_order_status';

		return $q;
	}

	private function expect_action_is_removed_and_then_added( WCML_Order_Status_Manager $subject ) {
		\WP_Mock::userFunction( 'doing_filter', array(
			'return' => true,
			'times'  => 1
		) );
		\WP_Mock::userFunction( 'remove_action', array(
			'times' => 1,
			'args'  => array(
				'pre_get_posts',
				array( $subject, 'pre_get_posts' ),
				10
			)
		) );
		\WP_Mock::expectActionAdded( 'pre_get_posts', array( $subject, 'pre_get_posts' ), 10, 1 );
	}

	/**
	 * @param int $post_id
	 * @param $current_language
	 */
	private function given_post_language( $post_id, $current_language ) {
		\WP_Mock::onFilter( 'wpml_post_language_details' )
		        ->with( '', $post_id )
		        ->reply(
			        array( 'language_code' => $current_language )
		        );
	}

	/**
	 * @param $current_language
	 */
	private function given_current_language( $current_language ) {
		\WP_Mock::onFilter( 'wpml_current_language' )
		        ->with( null )
		        ->reply( $current_language );
	}


}