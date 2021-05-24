<?php

namespace WCML\Setup;

/**
 * @group setup
 */
class TestBeforeHooks extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldNotAddHooksIfSetupIsComplete() {
		$woocommerce_wpml = $this->getWoocommerceWpml( [ 'set_up_wizard_run' => 1 ] );

		$subject = $this->getSubject( $woocommerce_wpml );

		\WP_Mock::expectFilterNotAdded( 'get_translatable_documents_all', [ BeforeHooks::class, 'blockProductTranslation' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldAddHooksIfSetupIsNotComplete() {
		$subject = $this->getSubject();

		\WP_Mock::expectFilterAdded( 'get_translatable_documents_all', [ BeforeHooks::class, 'blockProductTranslation' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldBlockProductTranslation() {
		$translatablePostTypes = [
			'post'              => [],
			'page'              => [],
			'product'           => [],
			'product_variation' => [],
		];

		$expectedTranslatablePostTypes = [
			'post'              => [],
			'page'              => [],
		];

		$this->assertEquals(
			$expectedTranslatablePostTypes,
			BeforeHooks::blockProductTranslation( $translatablePostTypes )
		);
	}

	private function getSubject( $woocommerce_wpml = null ) {
		$woocommerce_wpml = $woocommerce_wpml ?: $this->getWoocommerceWpml();

		return new BeforeHooks( $woocommerce_wpml );
	}

	private function getWoocommerceWpml( $settings = [ 'set_up_wizard_run' => 0 ] ) {
		$woocommerce_wpml = $this->getMockBuilder( \woocommerce_wpml::class )
			->setMethods( [
				'get_setting',
			] )
			->disableOriginalConstructor()->getMock();
		$woocommerce_wpml->method( 'get_setting' )
			->willReturnCallback( function( $key, $default = null ) use ( $settings ) {
				return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
			} );

		return $woocommerce_wpml;
	}
}
