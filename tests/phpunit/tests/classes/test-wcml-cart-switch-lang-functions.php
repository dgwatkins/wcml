<?php

/**
 * Class Test_WCML_Cart_Switch_Lang_Functions
 */
class Test_WCML_Cart_Switch_Lang_Functions extends OTGS_TestCase {

	public function tearDown(){
		global $woocommerce, $woocommerce_wpml;

		unset( $woocommerce, $woocommerce_wpml, $_GET['force_switch']);

	}

	private function get_subject(){
		return new WCML_Cart_Switch_Lang_Functions();
	}

	private function get_woocommerce_wpml(){

		return $this->getMockBuilder('woocommerce_wpml')
			->disableOriginalConstructor()
			->getMock();

	}

	private function get_woocommerce() {

		return $this->getMockBuilder( 'WooCommerce' )
			->disableOriginalConstructor()
			->getMock();

	}

	private function get_wcml_cart() {

		return $this->getMockBuilder( 'WCML_Cart' )
			->disableOriginalConstructor()
			->setMethods(['empty_cart_if_needed'])
			->getMock();

	}

	private function get_wc_session() {

		return $this->getMockBuilder( 'WC_Session' )
			->disableOriginalConstructor()
			->setMethods(['set'])
			->getMock();

	}

	/**
	 * @test
	 */
	public function add_actions(){

		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'wp_footer', array( $subject, 'wcml_language_switch_dialog' ) );
		\WP_Mock::expectActionAdded( 'wp_loaded', array( $subject, 'wcml_language_force_switch' ) );
		\WP_Mock::expectActionAdded( 'wcml_user_switch_language', array( $subject, 'language_has_switched' ), 10, 2 );

        \WP_Mock::expectFilterAdded( 'woocommerce_product_add_to_cart_url', array( $subject, 'remove_force_switch_from_add_to_cart_url' ) );

		$subject->add_actions();

	}

	/**
	 * @test
	 * @todo replace globals
	 */
	public function wcml_language_force_switch_should_switch(){
		global $woocommerce, $woocommerce_wpml;
		$subject = $this->get_subject();
		$woocommerce = $this->get_woocommerce();
		$woocommerce_wpml = $this->get_woocommerce_wpml();

		$_GET['force_switch'] = '1';

		$woocommerce_wpml->cart = $this->get_wcml_cart();
		$woocommerce->session = $this->get_wc_session();

		\WP_Mock::userFunction( 'wpml_is_ajax', [ 'times' => 1, 'return' => false ] );

		$woocommerce_wpml->cart->expects( $this->once() )->method('empty_cart_if_needed')->with('lang_switch');
		$woocommerce->session->expects( $this->once() )->method('set')->with( 'wcml_switched_type', 'lang_switch' );

		$subject->wcml_language_force_switch();

	}

	/**
	 * @test
	 * @todo replace globals
	 */
	public function wcml_language_force_switch_should_not_switch_when_ajax(){
		global $woocommerce, $woocommerce_wpml;
		$subject = $this->get_subject();

		$woocommerce = $this->get_woocommerce();
		$woocommerce_wpml = $this->get_woocommerce_wpml();
		$woocommerce_wpml->cart = $this->get_wcml_cart();
		$woocommerce->session = $this->get_wc_session();

		\WP_Mock::userFunction( 'wpml_is_ajax', [ 'times' => 1, 'return' => 1 ] );

		$woocommerce_wpml->cart->expects( $this->exactly( 0 ) )->method('empty_cart_if_needed');
		$woocommerce->session->expects( $this->exactly( 0 ) )->method('set');

		$subject->wcml_language_force_switch();

	}

	/**
	 * @test
	 * @todo replace globals
	 */
	public function wcml_language_force_switch_should_not_switch_when_not_force_switch(){
		global $woocommerce, $woocommerce_wpml;
		$subject = $this->get_subject();

		$woocommerce = $this->get_woocommerce();
		$woocommerce_wpml = $this->get_woocommerce_wpml();
		$woocommerce_wpml->cart = $this->get_wcml_cart();
		$woocommerce->session = $this->get_wc_session();

		\WP_Mock::userFunction( 'wpml_is_ajax', [ 'times' => 1, 'return' => 1 ] );

		$_GET['force_switch'] = null;

		$woocommerce_wpml->cart->expects( $this->exactly( 0 ) )->method('empty_cart_if_needed');
		$woocommerce->session->expects( $this->exactly( 0 ) )->method('set');

		$subject->wcml_language_force_switch();

	}

	/**
     * @test
     */
	public function remove_force_switch_from_url(){
        $subject = $this->get_subject();
        $url = 'http://example.com/?force_switch=1&add-to-cart=20';
        $this->assertSame( 'http://example.com/?add-to-cart=20', $subject->remove_force_switch_from_add_to_cart_url( $url ) );
    }

    /**
     * @test
     */
    public function remove_force_switch_from_url_keep_url_unchanged(){
        $subject = $this->get_subject();
        $url = rand_str(32);
        $this->assertSame( $url, $subject->remove_force_switch_from_add_to_cart_url( $url ) );
    }

}