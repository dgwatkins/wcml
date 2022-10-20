<?php

namespace WCML\AdminTexts;

use WPML\FP\Lst;
use WPML\LIB\WP\Hooks as WPHooks;

use function WPML\FP\spreadArgs;

class Hooks {

	public function add_hooks() {
		WPHooks::onFilter( 'wpml_st_blacklisted_options' )
			->then( spreadArgs( Lst::append( 'woocommerce_permalinks' ) ) );
	}

}
