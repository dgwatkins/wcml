<?php

namespace WCML\Utilities;

/**
 * @group translation-jobs
 * @group jobs
 */
class TestJobs extends \WCML_UnitTestCase {

	/**
	 * @test
	 */
	public function itGetProductJob() {
		$targetLang = 'fr';
		$postType   = 'product';
		$productId  = wpml_test_insert_post( 'en', $postType );
		$jobId      = wpml_tm_load_job_factory()->create_local_post_job( $productId, $targetLang );

		$job = Jobs::getProductJob( $productId, $targetLang );

		$this->assertEquals( $jobId, $job->job_id );
		$this->assertEquals( $productId, $job->original_doc_id );
		$this->assertEquals( 'post_' . $postType, $job->original_post_type );

		// Job does not exist
		$this->assertFalse( Jobs::getProductJob( $productId, 'pt-br' ) );
	}

	/**
	 * @test
	 */
	public function testIsAte() {
		$this->assertTrue( Jobs::isAte( (object) [ 'editor' => 'ate' ] ) );
		$this->assertFalse( Jobs::isAte( (object) [ 'editor' => 'cte' ] ) );
		$this->assertFalse( Jobs::isAte( false ) );
	}
}
