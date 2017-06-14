<?php

/**
 * Class Test_WCML_Setup_Handlers
 * @group wcml-1987
 *
 */
class Test_WCML_Setup_Handlers extends OTGS_TestCase {

	private function get_woocommerce_wpml_mock(){
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->setMethods( array( 'get_multi_currency' ) )
		            ->getMock();
	}

	private function get_subject( $woocommerce_wpml = null ){
		if( null === $woocommerce_wpml ) {
			$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		}
		return new WCML_Setup_Handlers( $woocommerce_wpml );
	}

	private function get_wcml_attributes_mock() {
		return $this->getMockBuilder( 'WCML_Attributes' )
		            ->disableOriginalConstructor()
		            ->setMethods( array( 'set_translatable_attributes' ) )
		            ->getMock();
	}

	private function get_wcml_currency_mock(){
		return $this->getMockBuilder( 'WCML_Multi_Currency' )
		            ->disableOriginalConstructor()
		            ->setMethods( array( 'enable', 'disable' ) )
		            ->getMock();
	}

	private function get_wcml_store_mock() {
		return $this->getMockBuilder( 'WCML_Store' )
		            ->disableOriginalConstructor()
		            ->setMethods( array( 'create_missing_store_pages_with_redirect' ) )
		            ->getMock();
	}

	/**
	 * @test
	 */
	public function save_attributes(){
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject = $this->get_subject( $woocommerce_wpml );

		$data = [ 'attributes' => [] ];

		$woocommerce_wpml->attributes = $this->get_wcml_attributes_mock();

		$woocommerce_wpml->attributes
			->expects( $this->once() )
			->method( 'set_translatable_attributes' )
			->with( $data['attributes'] );

		$subject->save_attributes( $data );
	}

	/**
	 * @test
	 */
	public function save_multi_currency_enable(){
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject = $this->get_subject( $woocommerce_wpml );

		$woocommerce_wpml->expects( $this->once() )->method( 'get_multi_currency' );

		$woocommerce_wpml->multi_currency = $this->get_wcml_currency_mock();

		$data = [ 'enabled' => true ];

		$woocommerce_wpml->multi_currency->expects( $this->once() )->method( 'enable' );

		$subject->save_multi_currency( $data );
	}

	/**
	 * @test
	 */
	public function save_multi_currency_disable(){
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject = $this->get_subject( $woocommerce_wpml );

		$woocommerce_wpml->expects( $this->once() )->method( 'get_multi_currency' );

		$woocommerce_wpml->multi_currency = $this->get_wcml_currency_mock();

		$data = [ 'enabled' => false ];

		$woocommerce_wpml->multi_currency->expects( $this->once() )->method( 'disable' );

		$subject->save_multi_currency( $data );
	}

	/**
	 * @test
	 */
	public function install_store_pages_should_create_wc_pages(){
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject = $this->get_subject( $woocommerce_wpml );

		$data = [ 'install_missing_pages' => true ];

		$woocommerce_wpml->store = $this->get_wcml_store_mock();

		$woocommerce_wpml->store->expects( $this->once() )->method( 'create_missing_store_pages_with_redirect' );

		$wc_install_stub  = Mockery::mock( 'overload:WC_Install' );
		$wc_install_stub->shouldReceive( 'create_pages' );

		$subject->install_store_pages( $data );
	}

	/**
	 * @test
	 */
	public function install_store_pages_should_create_only_page_translations(){
		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$subject = $this->get_subject( $woocommerce_wpml );

		$data = [
			'install_missing_pages' => false,
			'create_pages' => true,
		];

		$woocommerce_wpml->store = $this->get_wcml_store_mock();

		$woocommerce_wpml->store->expects( $this->once() )->method( 'create_missing_store_pages_with_redirect' );

		$subject->install_store_pages( $data );
	}

}