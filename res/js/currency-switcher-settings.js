
jQuery( function($){

    WCML_Currency_Switcher_Settings = {

        _currency_languages_saving : 0,

        init:  function(){

            $(document).ready( function(){

                $(document).on('click','input[name="currency_switcher_style"]', WCML_Currency_Switcher_Settings.update_currency_switcher_style);
                $(document).on('click','.currency_switcher_save', WCML_Currency_Switcher_Settings.save_currency_switcher_settings);
                $(document).on('click','.delete_currency_switcher', WCML_Currency_Switcher_Settings.delete_currency_switcher);

                $(document).on('change','#wcml_curr_sel_orientation', WCML_Currency_Switcher_Settings.set_currency_switcher_orientation);
                $(document).on('change','.js-wcml-cs-colorpicker-preset', WCML_Currency_Switcher_Settings.set_currency_switcher_color_pre_set );

                $(document).on('keyup','input[name="wcml_curr_template"]', WCML_Currency_Switcher_Settings.setup_currency_switcher_template_keyup);
                $(document).on('change','input[name="wcml_curr_template"]', WCML_Currency_Switcher_Settings.setup_currency_switcher_template_change);

            } );

        },

       initColorPicker : function() {
           $('.wcml-ui-dialog .js-wcml-cs-panel-colors').find('.js-wcml-cs-colorpicker').wpColorPicker({
                change: function(e){
                    WCML_Currency_Switcher_Settings.currency_switcher_preview();
                },
                clear: function(e){
                    WCML_Currency_Switcher_Settings.currency_switcher_preview();
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

            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: ajaxurl,
                data: {
                    action: 'wcml_currencies_switcher_save_settings',
                    wcml_nonce: dialog.find('#wcml_currencies_switcher_save_settings_nonce').val(),
                    switcher_id: dialog.find('#wcml_currencies_switcher_id').val(),
                    widget_id: dialog.find('#wcml-cs-widget').val(),
                    switcher_style: dialog.find('input[name="currency_switcher_style"]:checked').val(),
                    orientation: dialog.find('#wcml_curr_sel_orientation').val(),
                    template: template,
                    color_scheme: color_scheme
                },
                success: function(e){
                    dialog.find('.ui-dialog-titlebar-close').trigger('click');
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

        currency_switcher_preview: _.debounce( function (){

            var dialog =  $('.wcml-ui-dialog' );

            var template = dialog.find('.wcml-dialog-container input[name="wcml_curr_template"]').val();
            if(!template){
                template = dialog.find('.wcml-dialog-container #currency_switcher_default').val();
            }

            var ajaxLoader = $('<span class="spinner" style="visibility: visible;">');
            dialog.find('.wcml-dialog-container #wcml_curr_sel_preview').html(ajaxLoader);

            var color_scheme = {};
            jQuery('.wcml-ui-dialog input.js-wcml-cs-colorpicker').each( function(){
                color_scheme[ $(this).attr('name') ] = $(this).val();
            });

            var switcher_id = dialog.find('#wcml_currencies_switcher_id').val();

            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: 'wcml_currencies_switcher_preview',
                    wcml_nonce: dialog.find('#wcml_currencies_switcher_preview_nonce').val(),
                    switcher_type: dialog.find('input[name="currency_switcher_style"]:checked').val(),
                    orientation: dialog.find('#'+switcher_id+'_orientation_value').val(),
                    template: template,
                    color_scheme: color_scheme
                },
                success: function(resp){
                    dialog.find('#wcml_curr_sel_preview').html(resp);
                }
            });
        }, 500),

        set_currency_switcher_color_pre_set: function (){

            var color_sheme = $(this).val();

            if( settings.pre_selected_colors[color_sheme] != 'undefined' ){
                var selected_scheme = settings.pre_selected_colors[color_sheme];
                var color;
                for ( color in selected_scheme ) {
                    $('.wcml-ui-dialog input[name="'+color+'"]').val( selected_scheme[ color ] );
                    $('.wcml-ui-dialog input[name="'+color+'"]').closest('.wp-picker-container').find('.wp-color-result').css( 'background-color', selected_scheme[color] );
                }
            }

            WCML_Currency_Switcher_Settings.currency_switcher_preview();
        },

        update_currency_switcher_style: function(e){

            if( $(this).val() == 'list' ){
                $(this).closest('div').find('#wcml_curr_sel_orientation_list_wrap').show();
            }else{
                $(this).closest('div').find('#wcml_curr_sel_orientation_list_wrap').hide();
            }
            WCML_Currency_Switcher_Settings.currency_switcher_preview();
        },

        set_currency_switcher_orientation: function(e){
            $('.wcml-dialog-container #wcml_curr_sel_orientation_value').val( $(this).val() );
            WCML_Currency_Switcher_Settings.currency_switcher_preview();
        },

        setup_currency_switcher_template_keyup: function(e){
            discard = true;
            $(this).closest('.wcml-section').find('.button-wrap input').css("border-color","#1e8cbe");
            WCML_Currency_Switcher_Settings.currency_switcher_preview();
        },

        setup_currency_switcher_template_change: function(e){
            if(!$(this).val()){
                $('input[name="wcml_curr_template"]').val($('#currency_switcher_default').val())
            }
        }
    }

    WCML_Currency_Switcher_Settings.init();

} );