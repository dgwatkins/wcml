jQuery( document ).ready( function( $ ){
    //lock fields
    if( typeof lock_settings != 'undefined'  && typeof lock_settings.lock_fields != 'undefined' && lock_settings.lock_fields == 1 ) {

        $('#bto_config_group_inner .remove_row,.add_bto_group,.save_composition').each(function(){
            $(this).attr('disabled','disabled');
            $(this).after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
        });

        $('#bto_product_data .close_all,#bto_product_data .expand_all,#bto_product_data li,#bto_config_group_inner .handlediv').bind({
            click: function(e) {
                return false;
            }
        });


    }

});



