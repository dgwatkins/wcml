<?php
class WC_Order_Query extends WC_Object_Query {

	protected function get_default_query_vars() {}

	/**
	 * Get orders matching the current query vars.
	 *
	 * @return array|object of WC_Order objects
	 *
	 * @throws Exception When WC_Data_Store validation fails.
	 */
	public function get_orders() {}
}
