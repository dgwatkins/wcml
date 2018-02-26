<?php

class WCML_Update_Product_Gallery_Translation_Factory implements IWPML_Backend_Action_Loader {

	public function create() {
		global $sitepress;

		return new WCML_Update_Product_Gallery_Translation( new WPML_Translation_Element_Factory( $sitepress ), $sitepress );
	}

}