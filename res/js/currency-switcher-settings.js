
jQuery( function($){

    WCML_Currency_Switcher_Settings = {

        _currency_languages_saving : 0,

        init:  function(){

            $(document).ready( function(){

                $(document).on('change','#currency_switcher_style', WCML_Currency_Switcher_Settings.update_currency_switcher_style);
                $(document).on('click','.currency_switcher_save', WCML_Currency_Switcher_Settings.save_currency_switcher_settings);
                $(document).on('click','.delete_currency_switcher', WCML_Currency_Switcher_Settings.delete_currency_switcher);

                $(document).on('change','.js-wcml-cs-colorpicker-preset', WCML_Currency_Switcher_Settings.set_currency_switcher_color_pre_set );

                $(document).on('keyup','input[name="wcml_curr_template"]', WCML_Currency_Switcher_Settings.setup_currency_switcher_template_keyup);
                $(document).on('change','input[name="wcml_curr_template"]', WCML_Currency_Switcher_Settings.setup_currency_switcher_template_change);

            } );

        },

       initColorPicker : function() {
           $('.wcml-ui-dialog .js-wcml-cs-panel-colors').find('.js-wcml-cs-colorpicker').wpColorPicker({
                change: function(e){
                    var dialog =  $( this ).closest( '.wcml-ui-dialog' );
                    WCML_Currency_Switcher_Settings.currency_switcher_preview( dialog );
                },
                clear: function(e){
                    var dialog =  $( this ).closest( '.wcml-ui-dialog' );
                    WCML_Currency_Switcher_Settings.currency_switcher_preview( dialog );
                }
            });
        },

        save_currency_switcher_settings: function(){

            var dialog =  $( this ).closest( '.wcml-ui-dialog' );
            var ajaxLoader = $('<span class="spinner" style="visibility: visible;">');

            $(this).parent().append( ajaxLoader );
            dialog.find(':submit,:button').prop('disabled', true);

            var template = dialog.find('input[name="wcml_curr_template"]').val();
            if(!template){
                template = dialog.find('#currency_switcher_default').val();
            }

            var color_scheme = {};
            dialog.find('input.js-wcml-cs-colorpicker').each( function(){
                color_scheme[ $(this).attr('name') ] = $(this).val();
            });


            var widget_name = dialog.find('#wcml-cs-widget option:selected').text();
            var switcher_id = dialog.find('#wcml_currencies_switcher_id').val();
            var widget_id = dialog.find('#wcml-cs-widget').val();

            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: ajaxurl,
                data: {
                    action: 'wcml_currencies_switcher_save_settings',
                    wcml_nonce: dialog.find('#wcml_currencies_switcher_save_settings_nonce').val(),
                    switcher_id: switcher_id,
                    widget_id: widget_id,
                    switcher_style: dialog.find('#currency_switcher_style').val(),
                    template: template,
                    color_scheme: color_scheme
                },
                success: function(e) {
                    dialog.find('.ui-dialog-titlebar-close').trigger('click');

                    if( typeof widget_id == 'undefined' ){
                        widget_id = switcher_id;
                    }

                    if( $('.wcml-currency-preview.' + widget_id ).length == 0 ){

                        var widget_row = $('.wcml-cs-empty-row').clone();
                        widget_row.removeClass('wcml-cs-empty-row');
                        widget_row.find('.wcml-currency-preview').addClass(widget_id);
                        widget_row.find('.wcml-cs-widget-name').html( widget_name );
                        widget_row.find('.edit_currency_switcher').attr('data-switcher', widget_id );
                        widget_row.find('.edit_currency_switcher').attr('data-dialog', 'wcml_currency_switcher_options_' + widget_id );
                        widget_row.find('.edit_currency_switcher').attr('data-content', 'wcml_currency_switcher_options_' + widget_id );
                        widget_row.find('.delete_currency_switcher').attr('data-switcher', widget_id );
                        widget_row.show();

                        $('.wcml-cs-list').find('tr.wcml-cs-empty-row').before( widget_row );
                    }

                    WCML_Currency_Switcher_Settings.currency_switcher_preview( dialog, true );
                }
            });
        },

        delete_currency_switcher: function(e){

            e.preventDefault();

            var switcher_id = $(this).data( 'switcher' );
            var switcher_row = $(this).closest('tr');
            var ajaxLoader = $('<span class="spinner" style="visibility: visible;">');
            $(this).parent().html( ajaxLoader );

            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: ajaxurl,
                data: {
                    action: 'wcml_delete_currency_switcher',
                    wcml_nonce: $('#wcml_delete_currency_switcher_nonce').val(),
                    switcher_id: switcher_id
                },
                success: function(e){
                    switcher_row.hide();
                }
            });
        },

        currency_switcher_preview: _.debounce( function ( dialog, update_settings ){

            var template = dialog.find('.wcml-dialog-container input[name="wcml_curr_template"]').val();
            if(!template){
                template = dialog.find('.wcml-dialog-container #currency_switcher_default').val();
            }

            var ajaxLoader = $('<span class="spinner" style="visibility: visible;">');
            dialog.find('.wcml-dialog-container #wcml_curr_sel_preview').html(ajaxLoader);

            var color_scheme = {};
            dialog.find('input.js-wcml-cs-colorpicker').each( function(){
                color_scheme[ $(this).attr('name') ] = $(this).val();
            });

            var switcher_id = dialog.find('#wcml_currencies_switcher_id').val();
            var switcher_style = dialog.find('#currency_switcher_style').val();

            $.ajax({
                type: "POST",
                url: ajaxurl,
                dataType: 'json',
                data: {
                    action: 'wcml_currencies_switcher_preview',
                    wcml_nonce: dialog.find('#wcml_currencies_switcher_preview_nonce').val(),
                    switcher_id: switcher_id,
                    switcher_style: switcher_style,
                    template: template,
                    color_scheme: color_scheme
                },
                success: function(resp){

                    if( $( '#wcml-cs-inline-styles-'+switcher_id+'-'+switcher_style).length == 0 ){
                        $( '#wcml-cs-inline-styles-'+switcher_id+'-'+switcher_style).html( resp.inline_css );
                    }else{
                        $('head').append( '<style type="text/css" id="wcml-cs-inline-styles-'+switcher_id+'-'+switcher_style+'">'+ resp.inline_css+'</style>' );
                    }

                    if( update_settings ){
                        if( switcher_id == 'new_widget'){
                            switcher_id = dialog.find('#wcml-cs-widget').val();
                        }
                        $('.wcml-currency-preview.'+switcher_id).html(resp.preview);
                    }else{
                        dialog.find('.wcml-currency-preview').html(resp.preview);
                    }
                }
            });
        }, 500),

        set_currency_switcher_color_pre_set: function (){

            var color_sheme = $(this).val();
            var dialog =  $( this ).closest( '.wcml-ui-dialog' );

            if( settings.pre_selected_colors[color_sheme] != 'undefined' ){
                var selected_scheme = settings.pre_selected_colors[color_sheme];
                var color;
                for ( color in selected_scheme ) {
                    $('.wcml-ui-dialog input[name="'+color+'"]').val( selected_scheme[ color ] );
                    $('.wcml-ui-dialog input[name="'+color+'"]').closest('.wp-picker-container').find('.wp-color-result').css( 'background-color', selected_scheme[color] );
                }
            }

            WCML_Currency_Switcher_Settings.currency_switcher_preview( dialog );
        },

        update_currency_switcher_style: function(e){
            var dialog =  $( this ).closest( '.wcml-ui-dialog' );
            WCML_Currency_Switcher_Settings.currency_switcher_preview( dialog );
        },

        setup_currency_switcher_template_keyup: function(e){
            var dialog =  $( this ).closest( '.wcml-ui-dialog' );
            discard = true;
            $(this).closest('.wcml-section').find('.button-wrap input').css("border-color","#1e8cbe");
            WCML_Currency_Switcher_Settings.currency_switcher_preview( dialog );
        },

        setup_currency_switcher_template_change: function(e){
            if(!$(this).val()){
                $('input[name="wcml_curr_template"]').val($('#currency_switcher_default').val())
            }
        }
    }

    WCML_Currency_Switcher_Settings.init();

} );