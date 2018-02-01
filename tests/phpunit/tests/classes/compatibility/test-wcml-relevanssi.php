<?php

class Test_WCML_WCML_Relevanssi extends OTGS_TestCase {

    private function get_subject() {
        return new WCML_Relevanssi();
    }

    /**
     * @test
     */
    public function add_hooks() {
        $subject = $this->get_subject();
        \WP_Mock::expectActionAdded( 'wcml_update_extra_fields', array(
            $subject,
            'index_product'
        ), 10 ,4 );
        $subject->add_hooks();
    }

    /**
     * @test
     */
    public function it_index_product() {

	    $product_id = mt_rand( 1, 10 );
	    $tr_product_id = mt_rand( 10, 20 );
	    $translations = array();
	    $target_language = rand_str( 2 );

	    \WP_Mock::userFunction( 'relevanssi_insert_edit', array(
		    'args'   => array( $tr_product_id ),
		    'times'  => 1
	    ) );

        $subject = $this->get_subject();
        $subject->index_product( $product_id, $tr_product_id, $translations, $target_language );
    }

}
