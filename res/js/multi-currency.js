
jQuery( function($){

    WCML_Multi_Currency = {

        init:  function(){

            $(document).ready( function(){

                WCML_Multi_Currency.setup_multi_currency_toggle();

                //$(document).on('click','.wcml_add_currency', WCML_Multi_Currency.add_currency);
                //$(document).on('click','.cancel_currency', WCML_Multi_Currency.cancel_add_currency);

                $(document).on('change','.currency_code select', WCML_Multi_Currency.select_currency);

                $(document).on('click','.save_currency', WCML_Multi_Currency.save_currency);
                $(document).on('click','.delete_currency', WCML_Multi_Currency.delete_currency);

                $(document).on('click', '.wpml-dialog-container .currency_options_save', WCML_Multi_Currency.save_currency_options);

                $(document).on('click','.js-display-tooltip', WCML_Multi_Currency.tooltip);

                $(document).on('click', '.currency_languages a.off_btn', WCML_Multi_Currency.enable_currency_for_language);
                $(document).on('click', '.currency_languages a.off_btn', WCML_Multi_Currency.disable_currency_for_language);

                $(document).on('change', '.default_currency select', WCML_Multi_Currency.change_default_currency);

                $(document).on('click', '#wcml_dimiss_non_default_language_warning', WCML_Multi_Currency.dismiss_non_default_language_warning);

                WCML_Multi_Currency.setup_currencies_sorting();

                $(document).on('click','input[name="currency_switcher_style"]', WCML_Multi_Currency.update_currency_switcher_style);

                $(document).on('change','#wcml_curr_sel_orientation', WCML_Multi_Currency.set_currency_switcher_orientation);

                $(document).on('keyup','input[name="wcml_curr_template"]', WCML_Multi_Currency.setup_currency_switcher_template_keyup);
                $(document).on('change','input[name="wcml_curr_template"]', WCML_Multi_Currency.setup_currency_switcher_template_change);

            } );



        },

        setup_multi_currency_toggle: function(){

            $('#multi_currency_independent').change(function(){

                if($(this).attr('checked') == 'checked'){
                    $('#currency-switcher').fadeIn();
                    $('#multi-currency-per-language-details').fadeIn();
                }else{
                    $('#multi-currency-per-language-details').fadeOut();
                    $('#currency-switcher').fadeOut();
                }

            })


        },

        /*
        add_currency: function(){

            discard = true;
            $('.js-table-row-wrapper .curr_val_code').html($('.js-table-row-wrapper select').val());
            var $tableRow = $('.js-table-row-wrapper .js-table-row').clone();
            var $LangTableRow = $('.js-currency_lang_table tr').clone();
            var $DelTableRow = $('#currency-delete-table tr.currency_default').clone();
            $('#currency-table').find('tr.default_currency').before( $tableRow );
            $('#currency-lang-table').find('tr.default_currency').before( $LangTableRow );
            $('#currency-delete-table tr.currency_default:last').after( $DelTableRow );

        },
        */

        /*
        cancel_add_currency: function(){
            var $tableRow = $(this).closest('tr');
            $tableRow.removeClass('edit-mode');
            if($tableRow.find('.currency_current_code').val()){
                $tableRow.find('.currency_code .code_val').show();
                $tableRow.find('.currency_code select').hide();
                $tableRow.find('.currency_value span.curr_val').show();
                $tableRow.find('.currency_value input').hide();
                $tableRow.find('.currency_changed').show();
                $tableRow.find('.edit_currency').show();
                $tableRow.find('.delete_currency').show();
                $tableRow.find('.save_currency').hide();
                $tableRow.find('.cancel_currency').hide();
                $tableRow.find('.wcml-error').remove();
            }else{
                var index = $tableRow[0].rowIndex;
                $('#currency-lang-table tr').eq(index).remove();
                $tableRow.remove();
            }
        },
        */

        select_currency: function(){
            $(this).parent().find('.curr_val_code').html($(this).val());
        },

        save_currency: function(e){

            discard = false;
            e.preventDefault();

            var $this = $(this);
            var $ajaxLoader = $('<span class="spinner">');
            var $messageContainer = $('<span class="wcml-error">');

            $this.prop('disabled',true);

            var parent = $(this).closest('tr');

            parent.find('.save_currency').hide();
            parent.find('.cancel_currency').hide();
            $ajaxLoader.insertBefore($this).show();

            $currencyCodeWraper = parent.find('.currency_code');
            $currencyValueWraper = parent.find('.currency_value');

            var currency_code = $currencyCodeWraper.find('select[name=code]').val();
            var currency_value = $currencyValueWraper.find('input').val();
            var flag = false;

            if(currency_code == ''){
                if(parent.find('.currency_code .wcml-error').size() == 0){
                    parent.find('.currency_code').append( $messageContainer );
                    $messageContainer.text( $currencyCodeWraper.data('message') );
                    // empty
                }
                flag = true;
            }else{
                if(parent.find('.currency_code .wcml-error').size() > 0){
                    parent.find('.currency_code .wcml-error').remove();
                }
            }

            if(currency_value == ''){
                if(parent.find('.currency_value .wcml-error').size() == 0){

                    parent.find('.currency_value').append( $messageContainer );
                    $messageContainer.text( $currencyCodeWraper.data('message') );
                    // empty
                }
                flag = true;
            }else{
                if(parent.find('.currency_value .wcml-error').size() > 0){
                    parent.find('.currency_value .wcml-error').remove();
                }
            }

            if(!WCML_Multi_Currency.is_number(currency_value)){
                if(parent.find('.currency_value .wcml-error').size() == 0){
                    parent.find('.currency_value').append( $messageContainer );
                    $messageContainer.text( $currencyValueWraper.data('message') );
                    // numeric
                }
                flag = true;
            }else{
                if(parent.find('.currency_value .wcml-error').size() > 0){
                    parent.find('.currency_value .wcml-error').remove();
                }
            }

            if(flag){
                $ajaxLoader.remove();
                $this.prop('disabled',false);
                parent.find('.save_currency').show();
                parent.find('.cancel_currency').show();
                return false;
            }

            $.ajax({
                type : "post",
                url : ajaxurl,
                dataType: 'json',
                data : {
                    action: "wcml_new_currency",
                    wcml_nonce: $('#new_currency_nonce').val(),
                    currency_code : currency_code,
                    currency_value : currency_value
                },
                error: function(respnse) {
                    // TODO: add error handling
                },
                success: function(response) {

                    parent.closest('tr').attr('id', 'currency_row_' + currency_code);
                    $('#currency-lang-table tr:last').prev().attr('id', 'currency_row_langs_' + currency_code);

                    $('#currency_row_langs_' + currency_code + ' .off_btn').attr('data-currency', currency_code);
                    $('#currency_row_langs_' + currency_code + ' .on_btn').attr('data-currency', currency_code);

                    parent.find('.currency_code .code_val').html(response.currency_name_formatted);
                    parent.find('.currency_code .currency_value span').html(response.currency_meta_info);

                    parent.find('.currency_code').prepend(response.currency_options);

                    parent.find('.currency_code select[name="code"]').remove();
                    parent.find('.currency_value input').remove();

                    parent.find('.edit_currency').data('currency', currency_code).show();
                    parent.find('.delete_currency').data('currency', currency_code).show();

                    $('.js-table-row-wrapper select option[value="'+currency_code+'"]').remove();
                    $('.currency_languages select').each(function(){
                        $(this).append('<option value="'+currency_code+'">'+currency_code+'</option>');
                    });

                    $('#wcml_currencies_order').append('<li class="wcml_currencies_order_'+currency_code+'" cur="'+currency_code+'>'+response.currency_name_formatted_without_rate+'</li>');
                    currency_switcher_preview();
                },
                complete: function() {
                    $ajaxLoader.remove();
                    $this.prop('disabled',false);
                }
            });

            return false;

        },

        delete_currency: function(e){

            e.preventDefault();

            var currency = $(this).data('currency');

            $('#currency_row_' + currency + ' .currency_action_update').hide();
            var ajaxLoader = $('<span class="spinner">');
            $(this).hide();
            $(this).parent().append(ajaxLoader).show();

            $.ajax({
                type : "post",
                url : ajaxurl,
                data : {
                    action: "wcml_delete_currency",
                    wcml_nonce: $('#del_currency_nonce').val(),
                    code: currency
                },
                success: function(response) {
                    $('#currency_row_' + currency).remove();
                    $('#currency_row_langs_' + currency).remove();
                    $('#wcml_currencies_order .wcml_currencies_order_'+ currency).remove();

                    $.ajax({
                        type : "post",
                        url : ajaxurl,
                        data : {
                            action: "wcml_currencies_list",
                            wcml_nonce: $('#currencies_list_nonce').val()
                        },
                        success: function(response) {
                            $('.js-table-row-wrapper select').html(response);
                        }
                    });
                    currency_switcher_preview();
                },
                done: function() {
                    ajaxLoader.remove();
                }
            });

            return false;

        },

        save_currency_options: function(){

            var parent = $(this).closest('.wpml-dialog-container');

            var chk_rate = WCML_Multi_Currency.check_on_numeric(parent,'.ext_rate');
            var chk_deci = WCML_Multi_Currency.check_on_numeric(parent,'.decimals_number');
            var chk_autosub = WCML_Multi_Currency.check_on_numeric(parent,'.abstract_amount');

            if(chk_rate || chk_deci || chk_autosub){
                return false;
            }

            $('.wcml-currency-options-dialog :submit, .wcml-currency-options-dialog :button').prop('disabled', true);
            var currency = $(this).data('currency');

            var ajaxLoader = $('<span class="spinner" style="position:absolute;margin-left:-30px;"></span>');

            ajaxLoader.show();
            $(this).parent().prepend(ajaxLoader);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: parent.find('[name^="currency_options"]').serialize(),
                success: function(response){
                    $('.wcml-currency-options-dialog').fadeOut(function () {
                        ajaxLoader.remove();
                        $('.wcml-currency-options-dialog :submit, .wcml-currency-options-dialog :button').prop('disabled', false);

                        $('#currency_row_' + currency + ' .currency_code .code_val').html(response.currency_name_formatted);
                        $('#currency_row_' + currency + ' .currency_value span').html(response.currency_meta_info);


                    });
                }

            })

            return false;
        },

        check_on_numeric: function(parent, elem){

            var messageContainer = $('<span class="wcml-error">');

            if(!WCML_Multi_Currency.is_number(parent.find(elem).val())){
                if(parent.find(elem).parent().find('.wcml-error').size() == 0){
                    parent.find(elem).parent().append( messageContainer );
                    messageContainer.text( parent.find(elem).data('message') );
                }
                return true;
            }else{
                if(parent.find(elem).parent().find('.wcml-error').size() > 0){
                    parent.find(elem).parent().find('.wcml-error').remove();
                }
                return false;
            }

        },

        tooltip: function(){
            var $thiz = $(this);

            // hide this pointer if other pointer is opened.
            $('.wp-pointer').fadeOut(100);

            $(this).pointer({
                content: '<h3>'+$thiz.data('header')+'</h3><p>'+$thiz.data('content')+'</p>',
                position: {
                    edge: 'left',
                    align: 'center',
                    offset: '15 0'
                }
            }).pointer('open');
        },

        enable_currency_for_language: function(e){
            //TODO Sergey: Seriously check on that
            e.preventDefault();
            $(this).closest('ul').children('li').toggleClass('on');

            var index = $(this).closest('tr')[0].rowIndex;
            $('.currency_languages select[rel="'+$(this).data('language')+'"]').append('<option value="'+$(this).data('currency')+'">'+$(this).data('currency')+'</option>');
            WCML_Multi_Currency.update_currency_lang($(this),1,0);

        },

        disable_currency_for_language: function(e){
            //TODO Sergey: Seriously check on that
            e.preventDefault();

            $(this).closest('ul').children('li').toggleClass('on');

            //  var enbl_elem = $(this).closest('ul').find('.on').removeClass('on');
            var flag = true;
            var lang = $(this).data('language');

            //$('#currency-lang-table .on_btn[data-language="'+lang+'"]').each(function(){
            //    if($(this).parent().hasClass('on'))
            //        flag = false;
            //});

            //if(flag){
            //    enbl_elem.addClass('on');
            //    alert($('#wcml_warn_disable_language_massage').val());
            //    return;
            //}
            // $(this).parent().removeClass('on');
            var index = $(this).closest('tr')[0].rowIndex;

            if($('.currency_languages select[rel="'+$(this).data('language')+'"]').val() == $(this).data('currency')){
                WCML_Multi_Currency.update_currency_lang($(this),0,1);
            }else{
                WCML_Multi_Currency.update_currency_lang($(this),0,0);
            }
            $('.currency_languages select[rel="'+$(this).data('language')+'"] option[value="'+$(this).data('currency')+'"]').remove();

        },

        update_currency_lang: function(elem, value, upd_def){

            $('input[name="wcml_mc_options"]').attr('disabled','disabled');
            var lang = elem.data('language');
            var code = elem.data('currency');
            discard = true;
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'wcml_update_currency_lang',
                    value: value,
                    lang: lang,
                    code: code,
                    wcml_nonce: $('#update_currency_lang_nonce').val()
                },
                success: function(){
                    if(upd_def){
                        WCML_Multi_Currency.update_default_currency(lang,0);
                    }
                },
                complete: function() {
                    $('input[name="wcml_mc_options"]').removeAttr('disabled');
                    discard = false;
                }
            });

        },

        change_default_currency: function(){
            WCML_Multi_Currency.update_default_currency($(this).attr('rel'), $(this).val());
        },

        update_default_currency: function(lang, code){
            discard = true;
            $.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'wcml_update_default_currency',
                    lang: lang,
                    code: code,
                    wcml_nonce: $('#wcml_update_default_currency_nonce').val()
                },
                complete: function(){
                    discard = false;
                }
            });
        },

        is_number: function(n){
            return !isNaN(parseFloat(n)) && isFinite(n);
        },

        dismiss_non_default_language_warning: function(){

            $(this).attr('disabled', 'disabled');
            var ajaxLoader = $('<span class="spinner">');
            $(this).parent().append(ajaxLoader);
            ajaxLoader.show();
            $.ajax({
                type: 'post',
                url: ajaxurl,
                dataType:'json',
                data: {
                    action: 'wcml_update_setting_ajx',
                    setting: 'dismiss_non_default_language_warning',
                    value: 1,
                    nonce: $('#wcml_settings_nonce').val()
                },
                success: function(response){
                    location.reload();
                }
            });

        },

        setup_currencies_sorting: function(){

            $('#wcml_currencies_order').sortable({
                update: function(){
                    $('.wcml_currencies_order_ajx_resp').fadeIn();
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
                            fadeInAjxResp('.wcml_currencies_order_ajx_resp', resp.message);
                            WCML_Multi_Currency.currency_switcher_preview();
                        }
                    });
                }
            });

        },

        currency_switcher_preview: function (){
            var template = $('input[name="wcml_curr_template"]').val();
            if(!template){
                template = $('#currency_switcher_default').val();
            }

            var ajaxLoader = $('<span class="spinner" style="visibility: visible;">');
            $('#wcml_curr_sel_preview').html(ajaxLoader);

            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: 'wcml_currencies_switcher_preview',
                    wcml_nonce: $('#wcml_currencies_switcher_preview_nonce').val(),
                    switcher_type: $('input[name="currency_switcher_style"]:checked').val(),
                    orientation: $('#wcml_curr_sel_orientation').val(),
                    template: template
                },
                success: function(resp){
                    $('#wcml_curr_sel_preview').html(resp);
                }
            });
        },

        update_currency_switcher_style: function(e){
            $(this).closest('ul').find('select').hide();
            $(this).closest('li').find('select').show();
            WCML_Multi_Currency.currency_switcher_preview();
        },

        set_currency_switcher_orientation: function(e){
            WCML_Multi_Currency.currency_switcher_preview();
        },

        setup_currency_switcher_template_keyup: function(e){
            discard = true;
            $(this).closest('.wcml-section').find('.button-wrap input').css("border-color","#1e8cbe");
            WCML_Multi_Currency.currency_switcher_preview();
        },

        setup_currency_switcher_template_change: function(e){
            if(!$(this).val()){
                $('input[name="wcml_curr_template"]').val($('#currency_switcher_default').val())
            }
        }





    }


    WCML_Multi_Currency.init();


} );