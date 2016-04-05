jQuery(document).ready(function () {

	jQuery(document).on( 'WPML_TM.editor.view_ready', function( event, view ) {
		if ( view.field.field_type.substr( 0, 8 ) === 'file-url' ) {
			view.$el.append(
				'<div style="display: inline-block;width: 100%;">' +
					'<button type="button" class="button-secondary wcml_file_paths_button" style="float: right;margin-right: 17px;">'+strings.choose+'</button>' +
				'</div>' );
		}
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