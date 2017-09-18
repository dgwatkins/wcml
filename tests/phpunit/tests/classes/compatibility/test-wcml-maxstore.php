<?php

class Test_WCML_MaxStore extends OTGS_TestCase {

    private function get_subject() {
        return new WCML_MaxStore();
    }

    /**
     * @test
     */
    public function add_hooks() {
        $subject = $this->get_subject();
        \WP_Mock::expectFilterAdded( 'wcml_force_reset_cart_fragments', array(
            $subject,
            'wcml_force_reset_cart_fragments'
        ) );
        $subject->add_hooks();
    }

    /**
     * @test
     */
    public function wcml_force_reset_cart_fragments() {
        $subject = $this->get_subject();
        $this->assertEquals( 1, $subject->wcml_force_reset_cart_fragments() );
    }

}
