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

