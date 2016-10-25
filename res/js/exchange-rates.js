jQuery( function($){

    WCMLExchangeRates = {

        init: function(){

            $('#online-exchange-rates').on( 'change', '#exchange-rates-online', WCMLExchangeRates.toggleManualAutomatic );

            $('#online-exchange-rates').on( 'click', '#update-rates-manually', WCMLExchangeRates.updateRatesManually);

            $('#online-exchange-rates').on( 'change', 'input[name=exchange-rates-service]', WCMLExchangeRates.selectService );

            $('#online-exchange-rates').on( 'change', 'input[name=update-schedule]', WCMLExchangeRates.updateFrequency );



        },

        toggleManualAutomatic: function(){

            if($(this).attr('checked') == 'checked'){
                $('#exchange-rates-online-wrap').fadeIn();
            }else{
                $('#exchange-rates-online-wrap').fadeOut();
            }

        },

        updateRatesManually: function(){

            var updateButton = $(this);
            $('#update-rates-error').html('');
            $('#update-rates-spinner').css({ visibility: 'visible' });
            updateButton.attr('disabled', 'disabled');

            $.ajax({
                type: "post",
                url: ajaxurl,
                dataType: 'json',
                data: {
                    action: "wcml_update_exchange_rates",
                    wcml_nonce: $('#update-exchange-rates-nonce').val()
                },
                success: function (response) {

                    if (response.success) {
                        $('#update-rates-success').fadeIn();
                        $('#update-rates-time .time').html( response.last_updated );
                    }else{
                        if( response.error ){
                            $('#update-rates-error').html( response.error ).fadeIn();
                        }
                    }

                    $('#update-rates-spinner').css({ visibility: 'hidden' });
                    updateButton.removeAttr('disabled');

                }
            })

        },

        selectService: function(){

            $('.exchange-rate-api-key-wrap').hide();
            $(this).parent().find('.exchange-rate-api-key-wrap').show();

        },

        updateFrequency: function(){

            $('[name="update-weekly-day"], [name="update-monthly-day"]').attr('disabled', 'disabled');
            $(this).parent().find('select').removeAttr('disabled');

        }
    }



    WCMLExchangeRates.init();

});
