jQuery( function($){

    WCMLExchangeRates = {

        init: function(){

            $('#online-exchange-rates').on( 'change', '#exchange-rates-manual', WCMLExchangeRates.toggleManualAutomatic );
            $('#online-exchange-rates').on( 'change', '#exchange-rates-online', WCMLExchangeRates.toggleManualAutomatic );

            $('#online-exchange-rates').on( 'click', '#update-rates-manually', WCMLExchangeRates.updateRatesManually);

            $('#online-exchange-rates').on( 'change', 'input[name=exchange-rates-service]', WCMLExchangeRates.selectService );

        },

        toggleManualAutomatic: function(){

            var manual = $('#exchange-rates-manual').attr('checked') == 'checked';

            if( manual ){
                $('#exchange-rates-online-wrap').hide();
            }else{
                $('#exchange-rates-online-wrap').show();
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

            $('#online-exchange-rates .exchange-rate-api-key-wrap').hide();
            $(this).nextAll('.exchange-rate-api-key-wrap:first').show();

        }

    }

    WCMLExchangeRates.init();

});
