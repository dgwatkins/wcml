<?php
require WC_PATH . '/tests/framework/helpers/class-wc-helper-order.php';
require WC_PATH . '/tests/framework/helpers/class-wc-helper-product.php';
require WC_PATH . '/tests/framework/helpers/class-wc-helper-shipping.php';

/**
 * Class Test_WCML_Multi_Currency_Orders
 *
 * @group wcml-2957
 */
class Test_WCML_Multi_Currency_Orders extends WCML_UnitTestCase {

	private $settings;
	private $multi_currency;
	private $orders;

	function setUp() {
		parent::setUp();

		// Settings.
		$settings                            = $this->woocommerce_wpml->settings;
		$settings['enable_multi_currency']   = 2;
		$settings['default_currencies']      = [ 'en' => 'USD' ];
		$settings['currency_options']['USD'] = [
			'rate'               => 1.55,
			'position'           => 'left',
			'thousand_sep'       => ',',
			'decimal_sep'        => '.',
			'num_decimals'       => 2,
			'rounding'           => 'down',
			'rounding_increment' => 0,
			'auto_subtract'      => 0,
		];

		$this->settings = $settings;

		$this->woocommerce_wpml->update_settings( $settings );

		// Multi currency objects.
		$this->woocommerce_wpml->multi_currency = new WCML_Multi_Currency();
		$this->multi_currency                   = $this->woocommerce_wpml->multi_currency;

		$this->orders[0] = WC_Helper_Order::create_order();

		$this->orders[1] = WC_Helper_Order::create_order();
		$order_id        = method_exists( $this->orders[1], 'get_id' ) ? $this->orders[1]->get_id() : $this->orders[1]->id;
		update_post_meta( $order_id, '_order_currency', 'EUR' );

		$this->orders[2] = WC_Helper_Order::create_order();
		$order_id        = method_exists( $this->orders[2], 'get_id' ) ? $this->orders[2]->get_id() : $this->orders[2]->id;
		update_post_meta( $order_id, '_order_currency', 'EUR' );
	}

	function tearDown() {
		unset( $GLOBALS['wp_query'], $GLOBALS['typenow'] );
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_shows_order_currencies_selector() {
		global $wp_query, $typenow;

		$typenow = 'shop_order';

		ob_start();
		?>
		<select id="dropdown_shop_order_currency" name="_order_currency">
			<option value="">Show all currencies</option>
							<option value="USD" >United States (US) dollar (&#036;)</option>
								<option value="GBP" >Pound sterling (&pound;)</option>
						</select>
		<?php
		$expected_selector = ob_get_clean();

		ob_start();
		$this->multi_currency->orders->show_orders_currencies_selector();
		$selector = ob_get_clean();

		$this->assertSame( $expected_selector, $selector );
	}

	/**
	 * @test
	 */
	public function it_shows_order_currencies_selector_with_selected_currency() {
		global $wp_query, $typenow;

		$wp_query->query[ '_order_currency' ] = 'GBP';
		$typenow = 'shop_order';

		ob_start();
		?>
		<select id="dropdown_shop_order_currency" name="_order_currency">
			<option value="">Show all currencies</option>
							<option value="USD" >United States (US) dollar (&#036;)</option>
								<option value="GBP"  selected='selected'>Pound sterling (&pound;)</option>
						</select>
		<?php
		$expected_selector = ob_get_clean();

		ob_start();
		$this->multi_currency->orders->show_orders_currencies_selector();
		$selector = ob_get_clean();

		$this->assertSame( $expected_selector, $selector );
	}

	/**
	 * @test
	 */
	public function it_does_NOT_show_order_currencies_selector() {
		global $typenow;

		$typenow = '';

		ob_start();
		$this->multi_currency->orders->show_orders_currencies_selector();
		$selector = ob_get_clean();

		$this->assertEquals( '', $selector );
	}
}
