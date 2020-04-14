<?php

namespace WCML\Tax\Strings;

/**
 * @group tax
 */
class TestHooks extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldAddHooks() {
		$subject = $this->getSubject();

		\WP_Mock::expectActionAdded( 'woocommerce_tax_rate_added', [ $subject, 'registerLabelString' ], 10, 2 );
		\WP_Mock::expectActionAdded( 'woocommerce_tax_rate_updated', [ $subject, 'registerLabelString' ], 10, 2 );

		\WP_Mock::expectFilterAdded( 'woocommerce_rate_label', [ $subject, 'translateLabelString' ], 10, 2  );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldRegisterLabelString() {
		$tax_id   = 1;
		$tax_rate = [ 'tax_rate_name' => 'test name' ];

		$subject = $this->getSubject();

		\WP_Mock::userFunction( 'icl_register_string', [
			'times'  => 1,
			'args'   => [ $subject::STRINGS_CONTEXT, 'tax_label_'.$tax_id, $tax_rate['tax_rate_name'] ],
			'return' => true,
		] );

		$subject->registerLabelString( $tax_id, $tax_rate );
	}

	/**
	 * @test
	 */
	public function itShouldNotRegisterLabelString() {
		$subject = $this->getSubject();

		$subject->registerLabelString( 2, [] );
	}

	/**
	 * @test
	 */
	public function itShouldTranslateLabelString() {
		$label            = rand_str();
		$translated_label = rand_str();
		$tax_id           = 2;
		$string_id        = 5;

		$subject = $this->getSubject();

		\WP_Mock::userFunction( 'icl_get_string_id', [
			'times'  => 1,
			'args'   => [ $label, $subject::STRINGS_CONTEXT, 'tax_label_'.$tax_id ],
			'return' => $string_id,
		] );

		\WP_Mock::userFunction( 'icl_translate', [
			'times'  => 1,
			'args'   => [
				$subject::STRINGS_CONTEXT,
				'tax_label_'.$tax_id,
				$label
			],
			'return' => $translated_label,
		] );

		$this->assertEquals( $translated_label, $subject->translateLabelString( $label, $tax_id ) );
	}

	/**
	 * @test
	 */
	public function itShouldTranslateLabelStringWithMigration() {

		$label                                  = rand_str();
		$translated_label                       = rand_str();
		$tax_id                                 = 2;
		$string_id                              = 5;
		$new_string_id                          = 6;
		$old_string_translations['es']['value'] = rand_str();

		$subject = $this->getSubject();

		\WP_Mock::userFunction( 'icl_get_string_id', [
			'args'   => [ $label, $subject::STRINGS_CONTEXT, 'tax_label_'.$tax_id ],
			'return' => false,
		] );

		\WP_Mock::userFunction( 'icl_register_string', [
			'times'  => 1,
			'args'   => [ $subject::STRINGS_CONTEXT, 'tax_label_'.$tax_id, $label ],
			'return' => $new_string_id,
		] );

		\WP_Mock::userFunction( 'icl_get_string_id', [
			'args'   => [ $label, 'woocommerce taxes', $label ],
			'return' => $string_id,
		] );

		\WP_Mock::userFunction( 'icl_get_string_translations_by_id', [
			'times'  => 1,
			'args'   => [ $string_id ],
			'return' => $old_string_translations,
		] );

		\WP_Mock::userFunction( 'icl_add_string_translation', [
			'times'  => 1,
			'args'   => [
				$new_string_id,
				'es',
				$old_string_translations['es']['value'],
				ICL_STRING_TRANSLATION_COMPLETE
			],
			'return' => $old_string_translations,
		] );

		\WP_Mock::userFunction( 'icl_translate', [
			'times'  => 1,
			'args'   => [
				$subject::STRINGS_CONTEXT,
				'tax_label_'.$tax_id,
				$label
			],
			'return' => $translated_label,
		] );

		$this->assertEquals( $translated_label, $subject->translateLabelString( $label, $tax_id ) );
	}

	private function getSubject() {
		return new Hooks();
	}
}
