<?php

abstract class WCML_Menu_Wrap_Base extends WCML_Templates_Factory {

	protected function init_template_base_dir() {
		$this->template_paths = [
			WCML_PLUGIN_PATH . '/templates/',
		];
	}

	public function get_template() {
		return 'menus-wrap.twig';
	}

}
