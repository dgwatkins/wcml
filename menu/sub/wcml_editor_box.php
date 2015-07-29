<div id="term-description" class="wcml_editor">
    <?php if(!$woocommerce_wpml->settings['first_editor_call']):
        wp_editor( $value, 'wcmleditor'.$name, array( 'textarea_name'=> $name, 'textarea_rows'=>4, 'editor_class'=>'wcml_content_tr' ) ); ?>
    <?php else: ?>
        <div id="wp-wcmleditor<?php echo $name ?>-wrap" class="wp-core-ui wp-editor-wrap">
            <div id="wp-wcml<?php echo $name ?>-editor-tools" class="wp-editor-tools hide-if-no-js">
                <div id="wp-wcmleditor<?php echo $name ?>-media-buttons" class="wp-media-buttons">
                    <a href="#" id="insert-media-button" class="button insert-media add_media" data-editor="wcmleditor<?php echo $name ?>" title="<?php _e('Add Media'); ?>">
                        <span class="wp-media-buttons-icon"></span>
                        <?php _e('Add Media'); ?>
                    </a>
                </div>
                <div class="wp-editor-tabs">
                    <a id="wcmleditor<?php echo $name ?>-html" class="wp-switch-editor switch-html" ><?php _e('Text'); ?></a>
                    <a id="wcmleditor<?php echo $name ?>-tmce" class="wp-switch-editor switch-tmce" ><?php _e('Visual'); ?></a>
                </div>
            </div>
            <div id="wp-wcmleditor<?php echo $name ?>-editor-container" class="wp-editor-container">
                <textarea class="wp-editor-area" rows="4" autocomplete="off" cols="22" name="<?php echo $name ?>" id="wcmleditor<?php echo $name?>" aria-hidden="true"><?php echo $value ?></textarea>
            </div>
        </div>
    <?php endif; ?>
</div>