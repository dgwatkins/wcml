<?php

class Test_WCML_Checkout_Field_Editor extends OTGS_TestCase {

	private $package;

	public function setUp()
	{
		parent::setUp();

		$this->package = (object) array(
			'kind'      => 'WooCommerce Add-On',
			'kind_slug' => 'woocommerce-add-on',
			'name'      => 'checkout-field-editor',
			'title'     => 'WooCommerce Checkout Field Editor'
		);

	}

	private function get_subject(){

		return new WCML_Checkout_Field_Editor( $this->package );
	}

    /**
     * @test
     */
    public function add_hooks_is_admin(){

        $subject = $this->get_subject();

        \WP_Mock::wpFunction( 'is_admin', array(
            'return' => true,
            'times'  => 1
        ) );

        \WP_Mock::expectFilterAdded( 'pre_update_option_wc_fields_billing', array( $subject, 'register_fields' ) );
        \WP_Mock::expectFilterAdded( 'pre_update_option_wc_fields_shipping', array( $subject, 'register_fields' ) );
        \WP_Mock::expectFilterAdded( 'pre_update_option_wc_fields_additional', array( $subject, 'register_fields' ) );

        $subject->add_hooks();

    }

    /**
     * @test
     */
    public function add_hooks_front(){

        $subject = $this->get_subject();

        \WP_Mock::wpFunction( 'is_admin', array(
            'return' => false,
            'times'  => 1
        ) );

        \WP_Mock::expectFilterAdded( 'pre_option_wc_fields_billing', array( $subject, 'get_billing' ) );
        \WP_Mock::expectFilterAdded( 'pre_option_wc_fields_shipping', array( $subject, 'get_shipping' ) );
        \WP_Mock::expectFilterAdded( 'pre_option_wc_fields_additional', array( $subject, 'get_additional' ) );

        $subject->add_hooks();

    }

    /**
	 * @test
	 */
	public function register_fields(){

		$subject = $this->get_subject();

		$field_key = rand_str();
		$field = array(
			'label' => rand_str()
		);

		WP_Mock::expectAction( 'wpml_register_string', $field['label'], $field_key . '_label', $this->package, $field_key . ' Label', $this->package->kind );

		$fields = array(
			$field_key => $field
		);

		$subject = $this->get_subject();
		$subject->register_fields( $fields );

	}


	/**
	 * @test
	 */
	public function translate_fields(){

		$subject = $this->get_subject();

		$field_key = rand_str();
		$field = array(
			'label' => rand_str()
		);

		$exclude_from_translation_field = array(
			'label' => rand_str()
		);

		$translated_label = rand_str();
		\WP_Mock::onFilter( 'wpml_translate_string' )
		        ->with( $field['label'], $field_key.'_label', $this->package )
		        ->reply( $translated_label );


		$fields = array(
			$field_key => $field,
			'shipping_city' => $exclude_from_translation_field
		);

		$subject        = $this->get_subject();
		$filtered_fields = $subject->translate_fields( $fields );

		$this->assertEquals( $translated_label, $filtered_fields[$field_key]['label'] );
		$this->assertEquals( $exclude_from_translation_field['label'], $filtered_fields['shipping_city']['label'] );

	}
}
