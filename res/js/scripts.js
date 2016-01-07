jQuery(document).ready(function($){
    var discard = false;

    window.onbeforeunload = function(e) {
        if(discard){
            return $('#wcml_warn_message').val();
        }
    }

    $('.wcml-section input[type="submit"]').click(function(){
        discard = false;
    });

   $('.wcml_search').click(function(){
       window.location = $('.wcml_products_admin_url').val()+'&cat='+$('.wcml_product_category').val()+'&trst='+$('.wcml_translation_status').val()+'&st='+$('.wcml_product_status').val()+'&slang='+$('.wcml_translation_status_lang').val();
   });

   $('.wcml_search_by_title').click(function(){
       window.location = $('.wcml_products_admin_url').val()+'&s='+$('.wcml_product_name').val();
   });

   $('.wcml_reset_search').click(function(){
       window.location = $('.wcml_products_admin_url').val();
   });
   
    if (typeof TaxonomyTranslation != 'undefined') {
        TaxonomyTranslation.views.TermView = TaxonomyTranslation.views.TermView.extend({
            initialize: function () {
                TaxonomyTranslation.views.TermView.__super__.initialize.apply(this, arguments);
                this.listenTo(this.model, 'translationSaved', this.render_overlay);
            },
            render_overlay: function () {
                var taxonomy = TaxonomyTranslation.classes.taxonomy.get("taxonomy");
                $.ajax({
                    type: "post",
                    url: ajaxurl,
                    dataType: 'json',
                    data: {
                        action: "wcml_update_term_translated_warnings",
                        taxonomy: taxonomy,
                        wcml_nonce: $('#wcml_update_term_translated_warnings_nonce').val()
                    },
                    success: function (response) {
                        if (response.hide) {
                            $('.js-tax-tab-' + taxonomy).removeAttr('title');
                            $('.js-tax-tab-' + taxonomy + ' i.icon-warning-sign').remove();
                        }
                    }
                })
            }
        });

    }

   $(document).on('click', '.js-tax-translation li a[href^=#ignore-]', function(){
                
       var taxonomy = $(this).attr('href').replace(/#ignore-/, '');

       var spinner = '<span class="spinner" style="visibility: visible; position: absolute" />';
       $(this).append(spinner);

       $.ajax({
           type : "post",
           url : ajaxurl,
           dataType: 'json',
           data : {
               action: "wcml_ingore_taxonomy_translation",
               taxonomy: taxonomy, 
               wcml_nonce: $('#wcml_ingore_taxonomy_translation_nonce').val()
           },
           success: function(response) {
               
               if(response.html){
                   
                   $('.js-tax-translation li.js-tax-translation-' + taxonomy).html(response.html);
                   
                   $('.js-tax-tab-' + taxonomy).removeAttr('title');
				   $('.js-tax-tab-' + taxonomy + ' i.otgs-ico-warning').remove();
                   
                   
               }
               
           }
       })       

       return false;
   })
   
   $(document).on('click', '.js-tax-translation li a[href^=#unignore-]', function(){
                
       var taxonomy = $(this).attr('href').replace(/#unignore-/, '');

       var spinner = '<span class="spinner" style="visibility: visible; position: absolute" />';
       $(this).append(spinner);

       $.ajax({
           type : "post",
           url : ajaxurl,
           dataType: 'json',
           data : {
               action: "wcml_uningore_taxonomy_translation",
               taxonomy: taxonomy, 
               wcml_nonce: $('#wcml_ingore_taxonomy_translation_nonce').val()
           },
           success: function(response) {
               if(response.html){
                   $('.js-tax-translation li.js-tax-translation-' + taxonomy).html(response.html);
                   if(response.warn){
					   $('.js-tax-tab-' + taxonomy).append('&nbsp;<i class="otgs-ico-warning"></i>');
                   }
                   
               }
           }
       })       

       return false;
   })
   
   
   $(document).on('submit', '#wcml_tt_sync_variations', function(){

       var this_form = $('#wcml_tt_sync_variations');
       var data = this_form.serialize();
       this_form.find('.wpml_tt_spinner').fadeIn();
       this_form.find('input[type=submit]').attr('disabled', 'disabled');
       
       $.ajax({
           type : "post",
           url : ajaxurl,
           dataType: 'json',
           data : data,
           success: function(response) {
               this_form.find('.wcml_tt_sycn_preview').html(response.progress);
               if(response.go){                   
                   this_form.find('input[name=last_post_id]').val(response.last_post_id);
                   this_form.find('input[name=languages_processed]').val(response.languages_processed);
                   this_form.trigger('submit');
               }else{
                   this_form.find('input[name=last_post_id]').val(0);
                   this_form.find('.wpml_tt_spinner').fadeOut();
                   this_form.find('input').removeAttr('disabled');
                   jQuery('#wcml_tt_sync_assignment').fadeOut();
                   jQuery('#wcml_tt_sync_desc').fadeOut();
               }
               
           }
       });
       
       return false;
       
   });


    $(document).on('submit', '#wcml_tt_sync_assignment', function(){

        var this_form = $('#wcml_tt_sync_assignment');
        var parameters = this_form.serialize();

        this_form.find('.wpml_tt_spinner').fadeIn();
        this_form.find('input').attr('disabled', 'disabled');

        $('.wcml_tt_sync_row').remove();

        $.ajax({
            type:       "POST",
            dataType:   'json',
            url:        ajaxurl,
            data:       'action=wcml_tt_sync_taxonomies_in_content_preview&wcml_nonce='+$('#wcml_sync_taxonomies_in_content_preview_nonce').val()+'&' + parameters,
            success:
                function(ret){

                    this_form.find('.wpml_tt_spinner').fadeOut();
                    this_form.find('input').removeAttr('disabled');

                    if(ret.errors){
                        this_form.find('.errors').html(ret.errors);
                    }else{
                        jQuery('#wcml_tt_sync_preview').html(ret.html);
                        jQuery('#wcml_tt_sync_assignment').fadeOut();
                        jQuery('#wcml_tt_sync_desc').fadeOut();
                    }

                }

        });

        return false;

    });

    $(document).on('click', 'form.wcml_tt_do_sync a.submit', function(){

        var this_form = $('form.wcml_tt_do_sync');
        var parameters = this_form.serialize();

        this_form.find('.wpml_tt_spinner').fadeIn();
        this_form.find('input').attr('disabled', 'disabled');

        jQuery.ajax({
            type:       "POST",
            dataType:   'json',
            url:        ajaxurl,
            data:       'action=wcml_tt_sync_taxonomies_in_content&wcml_nonce='+$('#wcml_sync_taxonomies_in_content_nonce').val()+'&' + parameters,
            success:
                function(ret){

                    this_form.find('.wpml_tt_spinner').fadeOut();
                    this_form.find('input').removeAttr('disabled');

                    if(ret.errors){
                        this_form.find('.errors').html(ret.errors);
                    }else{
                        this_form.closest('.wcml_tt_sync_row').html(ret.html);
                    }

                }

        });

        return false;


    });

   var wcml_product_rows_data = new Array();
   var wcml_get_product_fields_string = function(row){
       var string = '';
       row.find('input[type=text], textarea').each(function(){
           string += $(this).val();
       });       
       
       return string;
   }

    //wc 2.0.*
    if($('.wcml_file_paths').size()>0){
        // Uploading files
        var downloadable_file_frame;
        var file_path_field;
        var file_paths;

        $(document).on( 'click', '.wcml_file_paths', function( event ){

            var $el = $(this);

            file_path_field = $el.parent().find('textarea');
            file_paths      = file_path_field.val();

            event.preventDefault();

            // If the media frame already exists, reopen it.
            if ( downloadable_file_frame ) {
                downloadable_file_frame.open();
                return;
            }

            var downloadable_file_states = [
                // Main states.
                new wp.media.controller.Library({
                    library:   wp.media.query(),
                    multiple:  true,
                    title:     $el.data('choose'),
                    priority:  20,
                    filterable: 'uploaded'
                })
            ];

            // Create the media frame.
            downloadable_file_frame = wp.media.frames.downloadable_file = wp.media({
                // Set the title of the modal.
                title: $el.data('choose'),
                library: {
                    type: ''
                },
                button: {
                    text: $el.data('update')
                },
                multiple: true,
                states: downloadable_file_states
            });

            // When an image is selected, run a callback.
            downloadable_file_frame.on( 'select', function() {

                var selection = downloadable_file_frame.state().get('selection');

                selection.map( function( attachment ) {

                    attachment = attachment.toJSON();

                    if ( attachment.url )
                        file_paths = file_paths ? file_paths + "\n" + attachment.url : attachment.url

                } );

                file_path_field.val( file_paths );
            });

            // Set post to 0 and set our custom type
            downloadable_file_frame.on( 'ready', function() {
                downloadable_file_frame.uploader.options.uploader.params = {
                    type: 'downloadable_product'
                };
            });

            downloadable_file_frame.on( 'close', function() {
                // TODO: /wp-admin should be a variable. Some plugions, like WP Better Security changes the name of this dir.
                $.removeCookie('_icl_current_language', { path: '/wp-admin' });
            });

            // Finally, open the modal.
            downloadable_file_frame.open();
        });
    }

    //wc 2.1.*
    if($('.wcml_file_paths_button').size()>0){
        // Uploading files
        var downloadable_file_frame;
        var file_path_field;
        var file_paths;

        $(document).on( 'click', '.wcml_file_paths_button', function( event ){

            var $el = $(this);

            file_path_field = $el.parent().find('.wcml_file_paths_file');
            file_paths      = file_path_field.val();

            event.preventDefault();

            // If the media frame already exists, reopen it.
            if ( downloadable_file_frame ) {
                downloadable_file_frame.open();
                return;
            }

            var downloadable_file_states = [
                // Main states.
                new wp.media.controller.Library({
                    library:   wp.media.query(),
                    multiple:  true,
                    title:     $el.data('choose'),
                    priority:  20,
                    filterable: 'uploaded'
                })
            ];

            // Create the media frame.
            downloadable_file_frame = wp.media.frames.downloadable_file = wp.media({
                // Set the title of the modal.
                title: $el.data('choose'),
                library: {
                    type: ''
                },
                button: {
                    text: $el.data('update')
                },
                multiple: true,
                states: downloadable_file_states
            });

            // When an image is selected, run a callback.
            downloadable_file_frame.on( 'select', function() {

                var selection = downloadable_file_frame.state().get('selection');

                selection.map( function( attachment ) {

                    attachment = attachment.toJSON();

                    if ( attachment.url )
                        file_paths = attachment.url

                } );

                file_path_field.val( file_paths );
            });

            // Set post to 0 and set our custom type
            downloadable_file_frame.on( 'ready', function() {
                downloadable_file_frame.uploader.options.uploader.params = {
                    type: 'downloadable_product'
                };
            });

            downloadable_file_frame.on( 'close', function() {
                // TODO: /wp-admin should be a variable. Some plugions, like WP Better Security changes the name of this dir.
                $.removeCookie('_icl_current_language', { path: '/wp-admin' });
            });

            // Finally, open the modal.
            downloadable_file_frame.open();
        });
    }


    $('#wcml_custom_exchange_rates').submit(function(){
        
        var thisf = $(this);
        
        thisf.find(':submit').parent().prepend(icl_ajxloaderimg + '&nbsp;')
        thisf.find(':submit').prop('disabled', true);
        
        $.ajax({
            
            type: 'post',
            dataType: 'json',
            url: ajaxurl,
            data: thisf.serialize(),
            success: function(){
                thisf.find(':submit').prev().remove();    
                thisf.find(':submit').prop('disabled', false);
            }
            
        })
        
        return false;
    })
    
    function wcml_remove_custom_rates(post_id){
        
        var thisa = $(this);
        
        $.ajax({
            
            type: 'post',
            dataType: 'json',
            url: ajaxurl,
            data: {action: 'wcml_remove_custom_rates', 'post_id': post_id},
            success: function(){
                thisa.parent().parent().parent().fadeOut(function(){ $(this).remove()});
            }
            
        })
        
        return false;
        
    }

    $(document).on('click', '#wcml_dimiss_non_default_language_warning', function(){
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
    });


    $(document).on('click', '.edit_base_slug', function(e) {
        e.preventDefault();

        if( !$(this).hasClass('dis_base') ){

            var elem = $(this);

            $.ajax({
                type : "post",
                url : ajaxurl,
                dataType: 'json',
                data : {
                    action: "wcml_edit_base",
                    base: elem.attr('data-base'),
                    language: elem.attr('data-language'),
                    wcml_nonce: $('#wcml_edit_base_nonce').val()
                },
                success: function(response) {
                    jQuery(document.body).append(response);

                    $('.wcml-base-dialog').css( 'top', $(document).height()/2 );

                    $('body').animate({
                        scrollTop: $('.wcml-base-dialog').offset().top - $(window).height()/2
                    },0);

                    $('.wcml-base-dialog').fadeIn();
                }
            })

        }

    });


    $(document).on('click', '.wcml_save_base', function(e) {
        e.preventDefault();

        var elem = $(this);
        var icon = '.'+elem.attr('data-base')+'_'+elem.attr('data-language');
        $.ajax({
            type : "post",
            url : ajaxurl,
            dataType: 'json',
            data : {
                action: "wcml_update_base_translation",
                base: elem.attr('data-base'),
                base_value: $('#base-original').val(),
                base_translation: $('#base-translation').val(),
                language: elem.attr('data-language'),
                wcml_nonce: $('#wcml_update_base_nonce').val()
            },
            success: function(response) {
                $(icon).find('i').remove();
                $(icon).append('<i class="otgs-ico-edit" >');
                $('.wcml-base-dialog').remove();
            }
        })
    });

    $(document).on('click', '.wcml_cancel_base,.wcml_fade_block', function(e) {
        e.preventDefault();
        $('.wcml-base-dialog').remove();
        $('.wcml_fade_block').remove();

    });

    $(document).on('click', '.wcml_ignore_link', function(e){
        e.preventDefault();

        var elem = $(this);
        var setting = elem.attr('data-setting');

        $.ajax({
            type : "post",
            url : ajaxurl,
            dataType: 'json',
            data : {
                action: "wcml_ignore_warning",
                setting: setting,
                wcml_nonce: $('#wcml_ignore_warning_nonce').val()
            },
            success: function(response) {
                elem.closest('.error').remove();
            }
        })

        return false;

    });

    $(document).on('click', '.hide-rate-block', function(){


        var wrap = $(this).closest('.wcml-wrap');

        $(this).attr('disabled', 'disabled');
        var ajaxLoader = $('<span class="spinner" style="visibility: visible;margin: 0">');
        var setting = jQuery(this).data('setting');
        $(this).parent().append(ajaxLoader);
        $(this).remove();

        $.ajax({
            type: 'post',
            url: ajaxurl,
            dataType:'json',
            data: {
                action: 'wcml_update_setting_ajx',
                setting: setting,
                value: 1,
                nonce: $('#wcml_settings_nonce').val()
            },
            success: function(response){
                wrap.hide();
            }
        });
        return false;
    });


});

