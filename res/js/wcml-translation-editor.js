/*jshint devel:true */
/*global jQuery, document, WPML_translation_editor */

var WCML = WCML || {};

WCML.Translation_Editor = function () {
	"use strict";

	var init = function () {
		jQuery(document).ready(function () {
            jQuery('.js-wcml-translation-dialog-trigger').on('click', editProduct);
			jQuery(document).on('WPML_translation_editor.translation_saved', translationSaved );
		});
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

