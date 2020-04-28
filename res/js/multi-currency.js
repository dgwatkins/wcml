jQuery( function($){
    WCML_Multi_Currency = {

        _currency_languages_saving : 0,

        init:  function(){

            $(document).ready( function(){

                WCML_Multi_Currency.setup_multi_currency_toggle();

                WCML_Multi_Currency.setup_currencies_sorting();

                $(document).on('change','#wcml_currency_options_gateway_code_paypal', WCML_Multi_Currency.preset_paypal_email);
                $(document).on('change','#wcml_currency_options_gateway_code_stripe', WCML_Multi_Currency.preset_stripe_settings);

                if($('#wcml_mc_options').length){
                    WCML_Multi_Currency.wcml_mc_form_submitted = false;
                    WCML_Multi_Currency.read_form_fields_status();

                    window.onbeforeunload = function(e) {
                        if(
                            !WCML_Multi_Currency.wcml_mc_form_submitted
                            && WCML_Multi_Currency.form_fields_changed()
                        ){
                            return $('#wcml_warn_message').val();
                        }
                    }

                    $('#wcml_mc_options').on('submit', function(){
                        WCML_Multi_Currency.wcml_mc_form_submitted = true;
                    })
                }

            } );

        },

        setup_multi_currency_toggle: function(){

            $('#multi_currency_independent').change(function(){

                if($(this).attr('checked') == 'checked'){
                    $('#currency-switcher, #currency-switcher-widget, #currency-switcher-product, #multi-currency-per-language-details, #online-exchange-rates').fadeIn();
                }else{
                    $('#currency-switcher, #currency-switcher-widget, #currency-switcher-product, #multi-currency-per-language-details, #online-exchange-rates').fadeOut();
                }

            })
        },

        setup_currencies_sorting: function(){

            $('#wcml_currencies_order').sortable({
                update: function(){
                    var currencies_order = [];
                    $('#wcml_currencies_order').find('li').each(function(){
                        currencies_order.push($(this).attr('cur'));
                    });
                    $.ajax({
                        type: "POST",
                        url: ajaxurl,
                        dataType: 'json',
                        data: {
                            action: 'wcml_currencies_order',
                            wcml_nonce: $('#wcml_currencies_order_order_nonce').val(),
                            order: currencies_order.join(';')
                        },
                        success: function(resp){
                            if ( resp.success ) {
                                fadeInAjxResp('.wcml_currencies_order_ajx_resp', resp.data.message);
                                $('.wcml-ui-dialog').each(function(){
                                    WCML_Currency_Switcher_Settings.currency_switcher_preview( $(this) );
                                });
                            }
                        }
                    });
                }
            });

        },

        preset_paypal_email: function(){

            var paypal_value_input = $('input[name="currency_options[gateways_settings][paypal][value]"]');
            var paypal_warning = $('.paypal-gateway-warning');

            paypal_value_input.val( $(this).find(":selected").data('email') );

            if(  $(this).find(":selected").data('is-valid') ){
                paypal_warning.hide();
                paypal_value_input.removeAttr('readonly');
            }else{
                paypal_warning.show();
                paypal_value_input.val('');
                paypal_value_input.attr('readonly','readonly');
            }
        },

        preset_stripe_settings: function(){

            $('input[name="currency_options[gateways_settings][stripe][publishable_key]"]').val( $(this).find(":selected").data('publishable-key') );
            $('input[name="currency_options[gateways_settings][stripe][secret_key]"]').val( $(this).find(":selected").data('secret-key') );

        },

        read_form_fields_status: function(){
            this.mc_form_status = $('#wcml_mc_options').serialize();
        },

        form_fields_changed: function(){
            return this.mc_form_status != $('#wcml_mc_options').serialize();
        },
    }

    WCML_Multi_Currency.init();
} );