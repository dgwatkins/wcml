<?php

$product_images = $woocommerce_wpml->products->product_images_ids( $product_id );
$product_contents = $woocommerce_wpml->products->get_product_contents( $product_id );
$trid = $sitepress->get_element_trid( $product_id, 'post_'.$product->post_type );
$product_translations = $sitepress->get_element_translations( $trid, 'post_'.$product->post_type, true, true );
$check_on_permissions = false;
if( !current_user_can( 'wpml_operate_woocommerce_multilingual' ) ){
    $check_on_permissions = true;
}

$button_labels = array(
    'save'      => esc_attr__('Save', 'wpml-wcml'),
    'update'    => esc_attr__('Update', 'wpml-wcml'),
);

$is_duplicate_product = false;

if( isset( $product_translations[$language] ) && get_post_meta( $product_translations[$language]->element_id, '_icl_lang_duplicate_of', true ) == $product_id ){
    $is_duplicate_product = true;
}

?>
<?php //var_dump($language); ?>
<div class="wpml-dialog wpml-dialog-translate wcml-pt-form">
    <header class="wpml-dialog-header">
        <h2 class="wpml-dialog-title"><?php printf( __( 'Product translation:  %s', 'wpml-wcml' ), '<strong>'.$product->post_title.'</strong>' ); ?></h2>
        <a href="<?php echo get_post_permalink( $product_id ); ?>" class="view"
           title="<?php printf( __( 'View "%s"', 'wpml-wcml' ), $product->post_title ); ?>"><?php _e( 'View Product', 'wpml-wcml' ); ?> </a>
        <?php //TODO Sergey: close Dialog on wpml-dialog-close (not on icon classes) ?>
        <i class="otgs-ico-close wpml-dialog-close"></i>
        <?php if( $is_duplicate_product ): ?>
            <span class="js-wcml_duplicate_product_notice_<?php echo $language ?>" >
                <?php printf(__('This product is an exact duplicate of the %s product.', 'wcml-wpml'),
                    $active_languages[ $original_language ]['display_name'] ); ?>&nbsp;
                <a href="#edit-<?php echo $product_id ?>_<?php echo $language ?>"><?php _e('Edit independently.', 'wpml-wcml') ?></a>
            </span>
            <span class="js-wcml_duplicate_product_undo_<?php echo $language ?>" style="display: none;" >
                <a href="#undo-<?php echo $product_id ?>_<?php echo $language ?>"><?php _e('Undo (keep this product as a duplicate)', 'wpml-wcml') ?></a>
            </span>
        <?php endif; ?>
    </header>
    <form class="wpml-dialog-body"
          id="poststuff"> <?php //   IMpoRTANT This ID must stay like this if it is impossible -> create additional div ?>
        <header class="wpml-translation-header">
            <h3 class="wpml-header-original"><?php _e( 'Original', 'wpml-wcml' ); ?>:
                <span class="wpml-title-flag">
                    <img src="<?php echo $sitepress->get_flag_url( $original_language ) ?>"  alt="<?php echo $active_languages[ $original_language ]['english_name'] ?>"/>
                    </span>
                <strong><?php echo $active_languages[ $original_language ]['english_name'] ?></strong>
            </h3>

            <h3 class="wpml-header-translation"><?php _e( 'Translation to', 'wpml-wcml' ); ?>:
                <span class="wpml-title-flag">
                    <img src="<?php echo $sitepress->get_flag_url( $language ) ?>"  alt="<?php echo $active_languages[ $language ]['english_name'] ?>"/>
                </span>
                <strong><?php echo $active_languages[ $language ]['english_name'] ?></strong>
            </h3>
            <a class="button-copy button-copy-all" title="<?php _e( 'Copy from original' ); ?>"><i
                    class="otgs-ico-copy"></i> <?php _e( 'Copy all fields from original', 'wpml-wcml' ); ?></a>
        </header>



        <div class="wpml-form-row">
            <label for="term-name"> <?php _e( 'Title', 'wpml-wcml' ); ?> </label>
            <input disabled id="term-name-original" class="original_value" value="<?php echo $product->post_title  ?>" type="text">
            <a class="button-copy" title="<?php _e( 'Copy from original' ); ?>"><i
                    class="otgs-ico-copy otgs-ico-32"></i></a>
            <input id="term-name" class="translated_value" value="<?php echo $trn_product ? $trn_product->post_title : '' ?>" type="text">
        </div>

        <div class="wpml-form-row">
            <label for="term-slug"><?php _e( 'Slug', 'wpml-wcml' ); ?></label>
            <input disabled id="term-slug-original" class="original_value" value="<?php echo $product->post_name  ?>" type="text">
            <a class="button-copy" title="<?php _e( 'Copy from original' ); ?>" id=""><i
                    class="otgs-ico-copy otgs-ico-32"></i></a>
            <input id="term-slug" class="translated_value" value="<?php echo $trn_product ? $trn_product->post_name : '' ?>" type="text">
        </div>

        <div class="wpml-form-row">
            <label for="term-description"><?php _e( 'Content', 'wpml-wcml' ); ?> /<br><?php _e( 'Description', 'wpml-wcml' ); ?></label>
            <textarea class="wcml_original_content" disabled id="term-description-original" class="original_value" cols="22" rows="4"><?php echo apply_filters( 'the_content', $product->post_content ); ?></textarea>
            <a class="button-copy" title="<?php _e( 'Copy from original' ); ?>" id=""><i
                    class="otgs-ico-copy otgs-ico-32"></i></a>

                <?php
                //wp_editor(  $trn_product->post_content, 'content_'.$language, array( 'textarea_name'=> 'content_'.$language, 'textarea_rows'=>4,  'editor_class'=>'wcml_content_tr' ) );
                echo $woocommerce_wpml->products->wcml_editor( 'content_'.$language, $trn_product ? $trn_product->post_content : ''); ?>

        </div>


        <div class="postbox wpml-form-row wcml-row-excerpt">
            <div title="<?php _e( 'Click to toggl' ); ?>" class="handlediv"><br></div>
            <h3 class="hndle"><span><?php _e( 'Excerpt', 'wpml-wcml' ); ?></span></h3>

            <div class="inside">
                <textarea class="wcml_original_excerpt" disabled id="term-description-original" class="original_value" cols="22" rows="4"><?php echo apply_filters( 'the_content', $product->post_excerpt ); ?></textarea>
                <a class="button-copy" title="<?php _e( 'Copy from original' ); ?>" id=""><i
                        class="otgs-ico-copy otgs-ico-32"></i></a>

                    <?php
                    //wp_editor(  $trn_product->post_excerpt, 'excerpt_'.$language, array( 'textarea_name'=> 'excerpt_'.$language, 'textarea_rows'=> 4,  'editor_class'=>'wcml_content_tr' ) );
                    echo $woocommerce_wpml->products->wcml_editor( 'excerpt_'.$language, $trn_product ? $trn_product->post_excerpt : '' ); ?>

            </div>
        </div>

        <?php //TODO Sergey: Add: IF no original THEN: class="postbox closed" and <em>(empty)</em> after title
        $purchase_note = get_post_meta( $product_id, '_purchase_note', true );
        ?>
        <div class="postbox wpml-form-row <?php echo !$purchase_note ? 'closed':'' ?> ">
            <div title="<?php _e( 'Click to toggle' ); ?>" class="handlediv"><br></div>
            <h3 class="hndle"><span><?php _e( 'Purchase note', 'wpml-wcml' ) ?>&nbsp;<em><?php !$purchase_note ? _e( '(empty)', 'wpml-wcml' ) : ''; ?></em> </span></h3>

            <div class="inside">
                <textarea disabled id="term-note-original" class="original_value" cols="22" rows="4"><?php echo $purchase_note; ?></textarea>
                <a class="button-copy" title="<?php _e( 'Copy from original' ); ?>" id=""><i
                        class="otgs-ico-copy otgs-ico-32"></i></a>
                <textarea class="translated_value" cols="22" rows="4"><?php echo get_post_meta( $trn_product->ID, '_purchase_note', true ); ?></textarea>
            </div>
        </div>

        <div class="postbox wpml-form-row">
            <div title="<?php _e( 'Click to toggle' ); ?>" class="handlediv"><br></div>
            <h3 class="hndle"><span><?php _e( 'Images', 'wpml-wcml' ) ?></span></h3>
                <?php echo $woocommerce_wpml->products->product_images_box( $product_id, $language, $is_duplicate_product ); ?>
        </div>

    </form>
</div>
<div class="wpml-dialog-footer wpml-sticky">
    <span class="errors icl_error_text"></span>

    <div class="wcml-pt-progress"></div>
    <div class="alignleft">
        <a class="button-secondary cancel wpml-dialog-close-button" href="#"><?php _e( 'Cancel', 'wpml-wcml' ); ?></a>
    </div>
    <div class="alignright">
        <a class="button-primary wpml-dialog-close-button" data-action="do_something_before_close" href="#"><?php _e( 'Save &amp; Close', 'wpml-wcml' ); ?></a>
        <a class="button-primary wpml-dialog-close-button" data-action="do_something_before_close" data-stay="true" href="#"><?php _e( 'Save', 'wpml-wcml' ); ?></a>
    </div>
</div>

<?php
if(!$woocommerce_wpml->settings['first_editor_call']){
    //load editor js
    if ( class_exists( '_WP_Editors' ) )
        _WP_Editors::editor_js();
    $woocommerce_wpml->settings['first_editor_call'] = true;
    $woocommerce_wpml->update_settings();
}

?>
<script type="text/javascript">
    //TODO Sergey: I disabled remembering open/close state and order because it wasn't working anyway, bu if you know how to make it work feel free
    postboxes.save_state = function () {
        return;
    };
    postboxes.save_order = function () {
        return;
    };
    postboxes.add_postbox_toggles();

    var editor_width = (jQuery('.wpml-dialog-body').width * 0.3) - 30;
    jQuery('.wcml_original_content,.wcml_original_excerpt').cleditor({
        width: editor_width,
        height: 195,
        controls:     // controls to add to the toolbar
            " | source "
    });
    jQuery('.wcml_original_content').cleditor()[0].disable(true);
    jQuery('.wcml_original_excerpt').cleditor()[0].disable(true);

</script>

<?php /*
<tr class="outer" data-prid="<?php echo $product->ID; ?>" <?php echo !isset( $display_inline ) ? 'display="none"' : ''; ?> >
    <td colspan="3">
        <div class="wcml_product_row" id="prid_<?php echo $product->ID; ?>" <?php echo isset($pr_edit) ? 'style="display:block;"':''; ?>>
            <div class="inner">
                <table class="fixed wcml_products_translation">
                    <thead>
                        <tr>
                            <th scope="col"><?php _e('Language', 'wpml-wcml') ?></th>
                            <?php $product_contents_labels = $woocommerce_wpml->products->get_product_contents_labels($product_id);?>
                            <?php foreach ($product_contents_labels as $product_content) : ?>
                                <th scope="col"><?php echo $product_content; ?></th>
                            <?php endforeach; ?>
                            <?php
                            $attributes = $woocommerce_wpml->products->get_product_atributes($product_id);
                            foreach($attributes as $key=>$attribute): ?>
                                <?php if(!$attribute['is_taxonomy']): ?>
                                    <th scope="col"><?php echo  $attribute['name']; ?></th>
                                <?php else: ?>
                                    <?php unset($attributes[$key]); ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php
                            do_action('wcml_extra_titles',$product_id);
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lang_codes as $key=>$lang) : if($key != $default_language && $check_on_permissions && ! $woocommerce_wpml->products->user_can_translate_product( $trid, $key )) continue;?>

                            <tr rel="<?php echo $key; ?>">
                                <td>
                                    <?php echo $lang; ?>
                                    <?php if($default_language == $key && current_user_can('wpml_operate_woocommerce_multilingual') ): ?>
                                        <a class="edit-translation-link" title="<?php __("edit product", "wpml-wcml") ?>" href="<?php echo get_edit_post_link($product_id); ?>"><i class="otgs-ico-edit"></i></a>
                                    <?php else: ?>
                                        <input type="hidden" name="icl_language" value="<?php echo $key ?>" />
                                        <input type="hidden" name="job_id" value="<?php echo $job_id ?>" />
                                        <input type="hidden" name="end_duplication[<?php echo $product_id ?>][<?php echo $key ?>]" value="<?php echo !intval($is_duplicate_product) ?>" />
                                        <?php $button_label = isset($product_translations[$key]) && !is_null($product_translations[$key]->element_id) ? $button_labels['update'] : $button_labels['save'] ;?>
                                        <input type="submit" name="product#<?php echo $product_id ?>#<?php echo $key ?>" disabled value="<?php echo $button_label ?>" class="button-secondary wcml_update">
                                        <span class="wcml_spinner spinner"></span>
                                    <?php endif; ?>
                                </td>
                                <?php
                                if(!current_user_can('wpml_manage_woocommerce_multilingual') && isset($product_translations[$key])){
                                    $tr_status = $wpdb->get_row($wpdb->prepare("SELECT status,translator_id FROM ". $wpdb->prefix ."icl_translation_status WHERE translation_id = %d",$product_translations[$key]->translation_id));

                                    if(!is_null($tr_status) && get_current_user_id() != $tr_status->translator_id ){
                                        if($tr_status->status == ICL_TM_IN_PROGRESS){ ?>
                                            <td><?php _e('Translation in progress', 'wpml-wcml'); ?><br>&nbsp;</td>
                                            <?php continue;
                                        }elseif($tr_status->status == ICL_TM_WAITING_FOR_TRANSLATOR && !$job_id ){
                                            $tr_job_id = $wpdb->get_var($wpdb->prepare("
                                                                    SELECT j.job_id
                                                                        FROM {$wpdb->prefix}icl_translate_job j
                                                                        JOIN {$wpdb->prefix}icl_translation_status s ON j.rid = s.rid
                                                                    WHERE s.translation_id = %d
                                                                ", $product_translations[$key]->translation_id ) );
                                            ?>
                                            <td><?php printf('<a href="%s" class="button-secondary">'.__('Take this and edit', 'wpml-wcml').'</a>', admin_url('admin.php?page=wpml-wcml&tab=products&prid=' . $product->ID.'&job_id='.$tr_job_id)); ?><br>&nbsp;</td>
                                            <?php continue;
                                        }
                                    }
                                }

                                foreach ($product_contents as $product_content) : ?>
                                    <td>
                                        <?php
                                        $trn_contents  = $woocommerce_wpml->products->get_product_content_translation($product_id,$product_content,$key);

                                        $missing_translation = false;
                                        if($default_language == $key){
                                            $product_fields_values[$product_content] = $trn_contents;
                                        }else{
                                            if(isset($product_fields_values[$product_content]) &&
                                                !empty($product_fields_values[$product_content]) &&
                                                empty($trn_contents)
                                            ){
                                                $missing_translation = true;
                                            }
                                        }

                                        if(!$woocommerce_wpml->products->check_custom_field_is_single_value($product_id,$product_content)){
                                            echo $woocommerce_wpml->products->custom_box($product_id,$product_content,$trn_contents,$key,$lang,$is_duplicate_product);
                                        }else if(in_array($product_content, array('_file_paths'))): ?>
                                            <?php
                                            $file_paths = '';
                                            if( is_array($trn_contents) ){
                                                foreach($trn_contents as $trn_content){
                                                    $file_paths = $file_paths ? $file_paths . "\n" .$trn_content : $trn_content;
                                                }
                                            } ?>
                                            <?php if($default_language == $key): ?>
                                                <textarea value="<?php echo $file_paths; ?>" disabled="disabled"><?php echo $file_paths; ?></textarea>
                                            <?php else: ?>
                                                <textarea value="<?php echo $file_paths; ?>" name='<?php echo $product_content.'_'.$key ?>' class="wcml_file_paths_textarea<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>><?php echo $file_paths; ?></textarea>
                                                <button type="button" class="button-secondary wcml_file_paths<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>><?php _e('Choose a file', 'wpml-wcml') ?></button>
                                            <?php endif;?>
                                        <?php elseif($product_content == 'title'): ?>
                                            <?php if($default_language == $key): ?>
                                                <textarea rows="1" disabled="disabled"><?php echo $trn_contents['title']; ?></textarea><br>
                                            <?php else: ?>
                                                <textarea class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" name="<?php echo $product_content.'_'.$key; ?>" rows="2" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> ><?php echo $trn_contents['title']; ?></textarea>
                                            <?php endif;?>
                                            <div class="edit_slug_block">
                                                <?php $hide = !$trn_contents['name'] ? 'hidden' : ''; ?>
                                                    <a href="javascript:void(0)" class="edit_slug_show_link <?php echo $hide; ?>"><?php $default_language == $key ? _e('Show slug', 'wpml-wcml') : _e('Edit slug', 'wpml-wcml') ?></a>
                                                    <a href="javascript:void(0)" class="edit_slug_hide_link  <?php echo $hide; ?>"><?php _e('Hide', 'wpml-wcml') ?></a>
                                                    </br>
                                                    <?php if($default_language == $key): ?>
                                                        <input type="text" value="<?php echo $trn_contents['name']; ?>" class="edit_slug_input" disabled="disabled" />
                                                    <?php else: ?>
                                                        <input type="text" value="<?php echo $trn_contents['name']; ?>" class="edit_slug_input <?php echo $hide; ?>" name="<?php echo 'post_name_'.$key; ?>"  <?php echo $hide?'disabled="disabled"':''; ?> />
                                                    <?php endif;?>
                                                    <?php if(!$trn_contents['name']): ?>
                                                        <span class="edit_slug_warning"><?php _e('Please save translation before edit slug', 'wpml-wcml') ?></span>
                                                    <?php endif;?>
                                            </div>
                                        <?php elseif(is_array($trn_contents)): ?>
                                            <?php foreach ($trn_contents as $tax_key=>$trn_content) : ?>
                                                <?php if($default_language == $key): ?>
                                                    <textarea rows="1" disabled="disabled"><?php echo $trn_content; ?></textarea>
                                                <?php else: ?>
                                                    <input class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" type="text" name="<?php echo $product_content.'_'.$key.'['.$tax_key.']'; ?>" value="<?php echo $trn_content ?>" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> /><br>
                                                <?php endif;?>
                                            <?php endforeach; ?>
                                        <?php elseif(in_array($product_content,array('content','excerpt'))): ?>
                                            <?php if($default_language == $key): ?>
                                                <button type="button" class="button-secondary wcml_edit_content origin_content"><?php _e('Show content', 'wpml-wcml') ?></button>
                                            <?php else: ?>
                                                <button type="button" class="button-secondary wcml_edit_content<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>><?php _e('Edit translation', 'wpml-wcml') ?></button>
                                                <?php if($missing_translation): ?>
                                                    <span class="wcml_field_translation_<?php echo $product_content ?>_<?php echo $key ?>">
                                                        <p class="missing-translation">
	                                                        <i class="otgs-ico-warning"></i>
                                                            <?php _e('Translation missing', 'wpml-wcml'); ?>
                                                        </p>
                                                    </span>
                                                <?php endif; ?>
                                            <?php endif;?>
                                            <div class="wcml_editor">
                                                <a class="media-modal-close wcml_close_cross" href="javascript:void(0);" title="<?php esc_attr_e('Close', 'wpml-wcml') ?>"><span class="media-modal-icon"></span></a>
                                                <div class="wcml_editor_original">
                                                    <h3><?php _e('Original content:', 'wpml-wcml') ?></h3>
                                                    <?php
                                                    if($product_content == 'content'){
                                                        $original_content = apply_filters('the_content', $product->post_content);
                                                    }else{
                                                        $original_content = apply_filters('the_content', $product->post_excerpt);
                                                    }
                                                    ?>
                                                    <textarea class="wcml_original_content"><?php echo $original_content; ?></textarea>

                                                </div>
                                                <div class="wcml_line"></div>
                                                <div class="wcml_editor_translation">
                                                    <?php if($default_language != $key): ?>
                                                        <?php
                                                        $tr_id = apply_filters( 'translate_object_id',$product_id, 'product', true, $key);
                                                        if(!$woocommerce_wpml->settings['first_editor_call']):
                                                             wp_editor($trn_contents, 'wcmleditor'.$product_content.$tr_id.$key, array('textarea_name'=>$product_content .'_'.$key,'textarea_rows'=>20,'editor_class'=>'wcml_content_tr')); ?>
                                                        <?php else: ?>
                                                            <div id="wp-wcmleditor<?php echo $product_content.$tr_id.$key ?>-wrap" class="wp-core-ui wp-editor-wrap">
                                                                <div id="wp-wcml<?php echo $product_content.$tr_id.$key ?>-editor-tools" class="wp-editor-tools hide-if-no-js">
                                                                    <div id="wp-wcmleditor<?php echo $product_content.$tr_id.$key ?>-media-buttons" class="wp-media-buttons">
                                                                        <a href="#" id="insert-media-button" class="button insert-media add_media" data-editor="wcmleditor<?php echo $product_content.$tr_id.$key ?>" title="<?php _e('Add Media'); ?>">
                                                                            <span class="wp-media-buttons-icon"></span>
                                                                            <?php _e('Add Media'); ?>
                                                                        </a>
                                                                    </div>
                                                                    <div class="wp-editor-tabs">
                                                                        <a id="wcmleditor<?php echo $product_content.$tr_id.$key ?>-html" class="wp-switch-editor switch-html" ><?php _e('Text'); ?></a>
                                                                        <a id="wcmleditor<?php echo $product_content.$tr_id.$key ?>-tmce" class="wp-switch-editor switch-tmce" ><?php _e('Visual'); ?></a>
                                                                    </div>
                                                                </div>
                                                                <div id="wp-wcmleditor<?php echo $product_content.$tr_id.$key ?>-editor-container" class="wp-editor-container">
                                                                    <textarea class="wcml_content_tr wp-editor-area" rows="20" autocomplete="off" cols="40" name="<?php echo $product_content .'_'.$key; ?>" id="wcmleditor<?php echo $product_content.$tr_id.$key ?>" aria-hidden="true"><?php echo $trn_contents ?></textarea>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>

                                                    <?php endif; ?>
                                                </div>
                                                <div class="wcml_editor_buttons">
                                                    <?php if($default_language == $key): ?>
                                                        <button type="button" class="button-secondary wcml_popup_close"><?php _e('Close', 'wpml-wcml') ?></button>
                                                    <?php else: ?>
                                                        <h3><?php printf(__('%s translation', 'wpml-wcml'),$lang); ?></h3>
                                                        <button type="button" class="button-secondary wcml_popup_cancel"><?php _e('Cancel', 'wpml-wcml') ?></button>
                                                        <button type="button" class="button-secondary wcml_popup_ok"><?php _e('Ok', 'wpml-wcml') ?></button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php elseif(in_array($product_content,array('images'))):
                                            echo $woocommerce_wpml->products->product_images_box($product_id,$key,$is_duplicate_product); ?>
                                        <?php elseif(in_array($product_content,array('variations'))):
                                            echo $woocommerce_wpml->products->product_variations_box($product_id,$key,$is_duplicate_product); ?>
                                        <?php elseif($product_content == '_file_paths'): ?>
                                            <textarea placeholder="<?php esc_attr_e('Upload file', 'wpml-wcml') ?>" value="" name='<?php echo $product_content.'_'.$key ?>' class="wcml_file_paths_textarea<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>></textarea>
                                            <button type="button" class="button-secondary wcml_file_paths<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>><?php _e('Choose a file', 'wpml-wcml') ?></button>
                                        <?php else: ?>
                                            <?php if($default_language == $key): ?>
                                                <textarea rows="1" disabled="disabled"><?php echo $trn_contents; ?></textarea><br>
                                            <?php else: ?>
                                                <textarea class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" name="<?php echo $product_content.'_'.$key; ?>" rows="2" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> ><?php echo $trn_contents; ?></textarea>
                                            <?php endif;?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <?php do_action('wcml_gui_additional_box',$product_id,$key,$is_duplicate_product); ?>
                                <?php
                                foreach ($attributes as $attr_key=>$attribute):  ?>
                                    <td>
                                        <?php $trn_attribute = $woocommerce_wpml->products->get_custom_attribute_translation($product_id, $attr_key, $attribute, $key); ?>
                                        <label class="custom_attr_label"><?php _e('name','wpml-wcml'); ?></label>
                                        <br>
                                        <?php if (!$trn_attribute): ?>
                                            <input type="text" name="<?php echo $attr_key . '_name_' . $key ; ?>" value="" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> />
                                        <?php else: ?>
                                            <?php if($default_language == $key): ?>
                                                <textarea rows="1" disabled="disabled"><?php echo $trn_attribute['name']; ?></textarea>
                                            <?php else: ?>
                                                <input class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" type="text" name="<?php echo $attr_key . '_name_' . $key; ?>" value="<?php echo $trn_attribute['name']; ?>" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>" <?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> />
                                            <?php endif;?>
                                        <?php endif;?>
                                        <br>
                                        <label class="custom_attr_label"><?php _e('values','wpml-wcml'); ?></label>
                                        <br>
                                        <?php if (!$trn_attribute): ?>
                                            <input class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" type="text" name="<?php echo $attr_key . '_' . $key ; ?>" value="" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>" <?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>/>
                                        <?php else: ?>
                                            <?php if($default_language == $key): ?>
                                                <textarea rows="1" disabled="disabled"><?php echo $trn_attribute['value']; ?></textarea>
                                            <?php else: ?>
                                                <input class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" type="text" name="<?php echo $attr_key . '_' . $key; ?>" value="<?php echo $trn_attribute['value']; ?>" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>" <?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> />
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a class="button-primary wpml-dialog-close-button" data-action="do_something_before_close" href="#">CLOSE WITH ACTION</a>

                <a class="button-primary wpml-dialog-close-button" href="#">CLOSE WITHOUT ACTION</a>
            </div>
        </div>

    <?php
    if(!$woocommerce_wpml->settings['first_editor_call']){
        //load editor js
        if ( class_exists( '_WP_Editors' ) )
        _WP_Editors::editor_js();
        $woocommerce_wpml->settings['first_editor_call'] = true;
        $woocommerce_wpml->update_settings();
    }

    ?>
    </td>
</tr>

 */ ?>
