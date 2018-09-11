<?php

class WCML_LiteSpeed_Cache {

	function add_hooks() {
		add_filter( 'wcml_is_cache_enabled_for_switching_currency', '__return_true' );
	}

}

