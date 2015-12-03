/*jshint devel:true */
/*global jQuery, document, WPML_translation_editor */

var WCML = WCML || {};

WCML.Translation_Editor = function () {
	"use strict";

	var init = function () {
		jQuery(document).ready(function () {
            jQuery('.js-wcml-translation-dialog-trigger').on('click', editProduct);
		});
	};

	var editProduct = function() {
        if (typeof WPML_translation_editor !== 'undefined') {
            WPML_translation_editor.mainEditor.showDialog( {type     : 'product',
                                                        id       : jQuery(this).data('id'),
                                                        job_id   : jQuery(this).data('job_id'),
                                                        language : jQuery(this).data('language')});
        }
    };
    
	init();

};

WCML.translationEditor = new WCML.Translation_Editor();

