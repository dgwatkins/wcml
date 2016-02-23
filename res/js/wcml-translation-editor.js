/*jshint devel:true */
/*global jQuery, document, WPML_translation_editor, window, _ */

var WCML = WCML || {};

WCML.Translation_Editor = function () {
	"use strict";

	var init = function () {
		
		jQuery(document).ready(function () {
            jQuery('.js-wcml-translation-dialog-trigger').on('click', editProduct);
			jQuery(document).on('WPML_translation_editor.translation_saved', translationSaved );

			// See if we should open a translation job from query params
			var post_id = getURLParameter('post_id');
			if (post_id) {
				var lang = getURLParameter('lang');
				if (lang) {
					jQuery( 'a[data-id="' + post_id + '"]').each( function() {
						if (jQuery(this).data('language') === lang) {
							var link = jQuery(this);
							_.defer( function() {
								link.trigger('click');
							});
							
						}
					});
				}
			}

			jQuery(document).on( 'click', '.wcml_file_paths_button', function( event ){
				var downloadable_file_frame;
				var file_path_field;
				var file_paths;

				var el = jQuery(this);

				file_path_field = el.parent().find('.translated_value');
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
						title:     el.data('choose'),
						priority:  20,
						filterable: 'uploaded'
					})
				];

				// Create the media frame.
				downloadable_file_frame = wp.media.frames.downloadable_file = wp.media({
					// Set the title of the modal.
					title: el.data('choose'),
					library: {
						type: ''
					},
					button: {
						text: el.data('update')
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
					jQuery.removeCookie('_icl_current_language', { path: '/wp-admin' });
				});

				// Finally, open the modal.
				downloadable_file_frame.open();
			});
		});
	};

	var getURLParameter = function(sParam) {
		var sPageURL = window.location.search.substring(1);
		var sURLVariables = sPageURL.split('&');
		for (var i = 0; i < sURLVariables.length; i++) 
		{
			var sParameterName = sURLVariables[i].split('=');
			if (sParameterName[0] == sParam) 
			{
				return sParameterName[1];
			}
		}
		return '';
	};
	
	var editProduct = function() {
        if (typeof WPML_translation_editor !== 'undefined') {
            WPML_translation_editor.mainEditor.showDialog( {job_type : 'wc_product',
															id       : jQuery(this).data('id'),
															job_id   : jQuery(this).data('id'),
															target   : jQuery(this).data('language')});
        }
    };
	
	var translationSaved = function (e, data) {
		if ( data.job_details.job_type == 'wc_product' ) {
			var id = data.job_details.job_id;
			var links = jQuery('.js-wcml-translation-dialog-trigger[data-id="' + id + '"]');
			links.each( function () {
				if (jQuery(this).data('language') == data.job_details.target) {
					jQuery(this).after(data.response.status_link);
					jQuery(this).remove();
					jQuery('.js-wcml-translation-dialog-trigger').off('click');
					jQuery('.js-wcml-translation-dialog-trigger').on('click', editProduct);
				}
			});
		}
	};

	init();

};

WCML.translationEditor = new WCML.Translation_Editor();

