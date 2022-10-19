<?php

namespace WCML\AdminTexts;

use WPML\FP\Lst;

class Hooks implements \IWPML_Action {

	public function add_hooks() {
		add_filter( 'wpml_st_blacklisted_options', Lst::append( 'woocommerce_permalinks' ) );
	}

}
