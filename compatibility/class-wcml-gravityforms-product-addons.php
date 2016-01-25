<?php
class WCML_Gravityforms_Product_Addons {
    public function __construct() {
        add_filter('gform_form_post_get_meta', array($this, 'gform_form_post_get_meta'), 10, 1);
    }
    
    public function gform_form_post_get_meta($value) {

        if (isset($value['fields']) && is_array($value['fields'])) {
            foreach ($value['fields'] as $id => $data) {
                if (isset($data->label) && isset($data->id)) {
                    $value['fields'][$id]->label = apply_filters( 'wpml_translate_single_string', $data->label, "gravity_form-{$data->id}", "field-{$data->id}-label" );
                }
            }
        }

        return $value;
    }
    
}
