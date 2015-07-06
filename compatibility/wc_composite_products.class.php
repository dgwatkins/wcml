<?php


class WCML_Composite_Products {
	function __construct() {
		add_filter('woocommerce_composite_component_default_option', array($this, 'woocommerce_composite_component_default_option'), 10, 3);
	}
	
	function woocommerce_composite_component_default_option($selected_value, $component_id, $object) {
		$selected_value = apply_filters('wpml_object_id', $selected_value, 'product');
		
		return $selected_value;
	}
}
