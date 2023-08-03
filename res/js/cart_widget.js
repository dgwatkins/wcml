jQuery(function ($) {

	jQuery(document).on( 'click', '.wcml_removed_cart_items_clear', function(e){
        e.preventDefault();

        jQuery.ajax({
            type : 'post',
            url : woocommerce_params.ajax_url,
            data : {
                action: 'wcml_cart_clear_removed_items',
                wcml_nonce: jQuery('#wcml_clear_removed_items_nonce').val()
            },
            success: function(response) {
                window.location = window.location.href;
            }
        });
    });

    var empty_cart_hash = sessionStorage.getItem('woocommerce_cart_hash') == '';
    if ( empty_cart_hash || actions.is_lang_switched == 1 || actions.force_reset == 1 ) {
        wcml_reset_cart_fragments();
    }

});

function wcml_reset_cart_fragments(){
    try {
        jQuery(function () {
            jQuery(document.body).trigger('wc_fragment_refresh');
            //backward compatibility for WC < 3.0
            sessionStorage.removeItem('wc_fragments');
        });
    } catch(err){}
}
