jQuery( function($){

	var WCML_WPML_Translation_Editor = {

		is_wcml_product: false,
		field_views: null,
		footer_view: null,
		
		init: function(){

			$(document).on( 'WPML_TM.editor.ready', WCML_WPML_Translation_Editor.do_editor_ready );
		
			$(document).ready(function () {

				if ( WCML_WPML_Translation_Editor.is_wcml_product ) {
					$(document).on( 'WPML_TM.editor.field_view_ready', WCML_WPML_Translation_Editor.show_file_paths_button );
					$(document).on( 'WPML_TM.editor.field_update_ui', WCML_WPML_Translation_Editor.field_update_ui );
					$(document).on( 'click', '.wcml_file_paths_button', WCML_WPML_Translation_Editor.show_downloadable_files_frame );
	
					$(document).on( 'blur', '#job_field_title .js-translated-value', WCML_WPML_Translation_Editor.update_slug );
					$(document).on( 'focus', '#job_field_slug .js-translated-value', WCML_WPML_Translation_Editor.update_slug );
					$(document).on( 'blur', '#job_field_slug .js-translated-value', WCML_WPML_Translation_Editor.update_slug );
				}

			});

		},

		show_file_paths_button: function( event, view ) {
			if ( view.field.field_type.substr( 0, 8 ) === 'file-url' ) {
				view.$el.append(
					'<div style="display: inline-block;width: 100%;">' +
					'<button type="button" class="button-secondary wcml_file_paths_button" style="float: right;margin-right: 17px;">'+strings.choose+'</button>' +
					'</div>' );
			}
		},

		show_downloadable_files_frame: function( event ){
			var downloadable_file_frame;
			var file_path_field;
			var file_paths;

			var el = $(this);

			file_path_field = el.closest('.wpml-form-row').find('.translated_value');
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
						file_paths = attachment.url;

				} );

				file_path_field.val( file_paths );
			});

			// Set post to 0 and set our custom type
			downloadable_file_frame.on( 'ready', function() {
				downloadable_file_frame.uploader.options.uploader.params = {
					type: 'downloadable_product'
				};
			});

			// Finally, open the modal.
			downloadable_file_frame.open();
		},

		update_slug: function(){

			var title = $('#job_field_title .js-translated-value').val();
			var slug_field = $('#job_field_slug .js-translated-value');

			if( title != '' && slug_field.val() == '' ){

				slug_field.prop('disabled', 'on');
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: { action: 'wcml_editor_auto_slug', title: title },
					success: function(response) {
						slug_field.val( response.slug );
						slug_field.removeAttr('disabled');
						$('#icl_tm_copy_link_slug').prop('disabled', 'on');
					}

				});

			}

		},
		
		do_editor_ready : function( event, job_type, field_views, footer_view ) {
			WCML_WPML_Translation_Editor.is_wcml_product = job_type === 'post_product';
			if ( WCML_WPML_Translation_Editor.is_wcml_product ) {
				WCML_WPML_Translation_Editor.field_views = field_views;
				WCML_WPML_Translation_Editor.footer_view = footer_view;
				
				WCML_WPML_Translation_Editor.update_save_button_state();
			}
		},
		
		update_save_button_state: function() {
			var one_of_these_fields_is_required = [ 'title', 'product_content', 'product_excerpt' ];
			var disable_buttons = true;

			_.each( WCML_WPML_Translation_Editor.field_views, function( view ) {
				var translation = view.getTranslation();
				if ( translation !== '' && jQuery.inArray( view.getFieldType(), one_of_these_fields_is_required ) !== -1 ) {
					disable_buttons = false;
				}
			});
			
			WCML_WPML_Translation_Editor.footer_view.disableSaveButtons( disable_buttons );
		},
		
		field_update_ui: function( event, view ) {
			WCML_WPML_Translation_Editor.update_save_button_state();
		}

	};

	WCML_WPML_Translation_Editor.init();

});


/*
jQuery(document).ready(function () {

	jQuery(document).on( 'WPML_TM.editor.view_ready', function( event, view ) {
		if ( view.field.field_type.substr( 0, 8 ) === 'file-url' ) {
			view.$el.append(
				'<div style="display: inline-block;width: 100%;">' +
					'<button type="button" class="button-secondary wcml_file_paths_button" style="float: right;margin-right: 17px;">'+strings.choose+'</button>' +
				'</div>' );
		}
	});

	jQuery(document).on( 'blur', '#job_field_title .js-translated-value', function(){

		var title = jQuery(this).val();
		//alert( 'CE MAMA BRACU ' + title );

	});

	jQuery(document).on( 'click', '.wcml_file_paths_button', function( event ){
		var downloadable_file_frame;
		var file_path_field;
		var file_paths;

		var el = jQuery(this);

		file_path_field = el.closest('.wpml-form-row').find('.translated_value');
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

		// Finally, open the modal.
		downloadable_file_frame.open();
	});
});

*/