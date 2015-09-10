<?php

$product_images = $woocommerce_wpml->products->product_images_ids($product_id);

$trid = $sitepress->get_element_trid($product_id, 'post_' . $product->post_type);
$product_translations = $sitepress->get_element_translations($trid, 'post_' . $product->post_type, true, true);
$check_on_permissions = false;
if (!current_user_can('wpml_operate_woocommerce_multilingual')) {
    $check_on_permissions = true;
}

$button_labels = array(
    'save' => esc_attr__('Save', 'wpml-wcml'),
    'update' => esc_attr__('Update', 'wpml-wcml'),
);

$is_duplicate_product = false;

if (isset($product_translations[$language]) && get_post_meta($product_translations[$language]->element_id, '_icl_lang_duplicate_of', true) == $product_id) {
    $is_duplicate_product = true;
}

?>
<?php //var_dump($language); ?>
<div class="wpml-dialog wpml-dialog-translate wcml-pt-form">
    <header class="wpml-dialog-header">
        <h2 class="wpml-dialog-title"><?php printf(__('Product translation:  %s', 'wpml-wcml'), '<strong>' . $product->post_title . '</strong>'); ?></h2>
        <a href="<?php echo get_post_permalink($product_id); ?>" class="view"
           title="<?php printf(__('View "%s"', 'wpml-wcml'), $product->post_title); ?>"><?php _e('View Product', 'wpml-wcml'); ?> </a>
        <i class="otgs-ico-close wpml-dialog-close-button"></i>
    </header>
    <form class="wpml-dialog-body"
          id="poststuff"> <?php //   IMpoRTANT This ID must stay like this if it is impossible -> create additional div ?>
        <header class="wpml-translation-header">
            <?php if( $is_duplicate_product ): ?>
                <h3 class="js-wcml_duplicate_product_notice" >
                <span style="height: 38px"><?php printf(__('This product is an exact duplicate of the %s product.', 'wcml-wpml'),
                    $active_languages[ $original_language ]['display_name'] ); ?></span>
                    <a class="button-primary duplicate_edit" ><?php _e('Edit independently', 'wpml-wcml') ?></a>
                </h3>
                <h3 class="js-wcml_duplicate_product_undo" style="display: none;" >
                    <a class="button-secondary duplicate_cancel"><?php _e('Undo (keep this product as a duplicate)', 'wpml-wcml') ?></a>
                </h3>
            <?php endif; ?>
            <h3 class="wpml-header-original"><?php _e('Original', 'wpml-wcml'); ?>:
                <span class="wpml-title-flag">
                    <img src="<?php echo $sitepress->get_flag_url($original_language) ?>"
                         alt="<?php echo $active_languages[$original_language]['english_name'] ?>"/>
                </span>
                <strong><?php echo $active_languages[$original_language]['english_name'] ?></strong>
            </h3>

            <h3 class="wpml-header-translation"><?php _e('Translation to', 'wpml-wcml'); ?>:
                <span class="wpml-title-flag">
                    <img src="<?php echo $sitepress->get_flag_url($language) ?>"
                         alt="<?php echo $active_languages[$language]['english_name'] ?>"/>
                </span>
                <strong><?php echo $active_languages[$language]['english_name'] ?></strong>
            </h3>

            <div class="wpml-copy-container">
                <a class="button-secondary button-copy-all" title="<?php _e('Copy from original'); ?>">
                    <i class="otgs-ico-copy"></i>
                    <?php _e('Copy all fields from original', 'wpml-wcml'); ?>
                </a>
            </div>
        </header>

        <div class="wpml-form-row">
            <label for="term-name"> <?php _e('Title', 'wpml-wcml'); ?> </label>
            <input readonly id="term-name-original" class="original_value" value="<?php echo $product->post_title ?>"
                   type="text">
            <a class="button-copy button-secondary" title="<?php _e('Copy from original'); ?>">
                <i class="otgs-ico-copy"></i>
            </a>
            <input id="term-name"
                   class="translated_value <?php if ($is_duplicate_product): ?> js-dup-disabled<?php endif; ?>"<?php if ($is_duplicate_product): ?> readonly<?php endif; ?>
                   name="title" value="<?php echo $trn_product ? $trn_product->post_title : '' ?>" type="text"/>
        </div>

        <div class="wpml-form-row">
            <label for="term-slug"><?php _e('Slug', 'wpml-wcml'); ?></label>
            <input readonly id="term-slug-original" class="original_value" value="<?php echo $product->post_name ?>"
                   type="text">
            <a class="button-copy button-secondary" title="<?php _e('Copy from original'); ?>" id="">
                <i class="otgs-ico-copy"></i>
            </a>
            <input id="term-slug"
                   class="translated_value <?php if ($is_duplicate_product): ?> js-dup-disabled<?php endif; ?>"<?php if ($is_duplicate_product): ?> readonly<?php endif; ?>
                   name="post_name" <?php echo !$trn_product ? 'empty="true"' : ''; ?>
                   value="<?php echo $trn_product ? $trn_product->post_name : '' ?>" type="text">
        </div>

        <div class="wpml-form-row original_description">
            <label for="term-description"><?php _e('Content', 'wpml-wcml'); ?>
                /<br><?php _e('Description', 'wpml-wcml'); ?></label>
            <div class="mce_editor_origin">
            </div>
            <a class="button-copy button-secondary" title="<?php _e('Copy from original'); ?>" id="">
                <i class="otgs-ico-copy"></i>
            </a>
            <div class="mce_editor">
            </div>
            <textarea id="hidden_original_description_value" ><?php echo  $product->post_content ?></textarea>
            <textarea id="hidden_translated_description_value" ><?php echo $trn_product ? $trn_product->post_content : '' ?></textarea>
        </div>

        <div class="postbox wpml-form-row wcml-row-excerpt">
            <div title="<?php _e('Click to toggl'); ?>" class="handlediv"><br></div>
            <h3 class="hndle">
                <span><?php _e('Excerpt', 'wpml-wcml'); ?></span>
            </h3>

            <div class="inside">
                <div class="mce_editor_origin">
                </div>
                <a class="button-copy button-secondary" title="<?php _e('Copy from original'); ?>" id="">
                    <i class="otgs-ico-copy"></i>
                </a>
                <div class="mce_editor">
                </div>
            </div>

            <textarea id="hidden_original_excerpt_value" ><?php echo  $product->post_excerpt?></textarea>
            <textarea id="hidden_translated_excerpt_value" ><?php echo $trn_product ? $trn_product->post_excerpt : '' ?></textarea>
        </div>

        <?php
        $purchase_note = get_post_meta($product_id, '_purchase_note', true);
        ?>
        <div class="postbox wpml-form-row wcml-row-purchase-note <?php echo !$purchase_note ? 'closed' : '' ?> ">
            <div title="<?php _e('Click to toggle'); ?>" class="handlediv"><br></div>
            <h3 class="hndle">
                <span><?php _e('Purchase note', 'wpml-wcml') ?><?php echo !$purchase_note ? '&nbsp;<em>' . __('(empty)', 'wpml-wcml') . '</em>' : '' ?> </span>
            </h3>

            <div class="inside">
                <textarea class="original_value" readonly cols="22"
                          rows="10"><?php echo $purchase_note; ?></textarea>
                <a class="button-copy button-secondary" title="<?php _e('Copy from original'); ?>" id="">
                    <i class="otgs-ico-copy"></i>
                </a>
                <textarea class="translated_value <?php if ($is_duplicate_product): ?> js-dup-disabled<?php endif; ?>"<?php if ($is_duplicate_product): ?> readonly<?php endif; ?>
                          cols="22"
                          rows="10"><?php echo $trn_product ? get_post_meta($trn_product->ID, '_purchase_note', true) : '' ?></textarea>
            </div>
        </div>

        <div class="postbox wpml-form-row wcml-row-images">
            <div title="<?php _e('Click to toggle'); ?>" class="handlediv"><br></div>
            <h3 class="hndle">
                <span><?php _e('Images', 'wpml-wcml') ?></span>
            </h3>

            <div class="inside">
                <?php echo $woocommerce_wpml->products->product_images_box($product_id, $language, $is_duplicate_product); ?>
            </div>
        </div>

        <?php $attributes = $woocommerce_wpml->products->get_product_atributes($product_id); ?>
        <?php if ($attributes): ?>
            <div class="postbox wpml-form-row wcml-row-attributes">
                <div title="<?php _e('Click to toggl'); ?>" class="handlediv"><br></div>
                <h3 class="hndle">
                    <span><?php _e('Custom Product attributes', 'wpml-wcml'); ?></span>
                </h3>

                <div class="inside">
                    <table id="prod_attributes" class="prod_attributes wcml-attr-table js-table">

                        <?php
                        $index = 0;
                        foreach ($attributes as $attr_key => $attribute): ?>
                            <tr class="wcml-first-row" row-index="<?php echo $index; ?>">
                                <th>
                                    <?php $trn_attribute = $woocommerce_wpml->products->get_custom_attribute_translation($product_id, $attr_key, $attribute, $language); ?>
                                    <label class="custom_attr_label"><?php _e('Name', 'wpml-wcml'); ?></label>
                                </th>
                                <td>
                                    <input readonly class="original_value" value="<?php echo $attribute['name'] ?>"
                                           type="text"/>
                                </td>
                                <td rowspan="2" class="button-copy-cell">
                                    <a class="button-copy button-secondary" title="<?php _e('Copy from original'); ?>"
                                       id="">
                                        <i class="otgs-ico-copy"></i>
                                    </a>
                                </td>
                                <td>
                                    <input
                                        class="translated_value <?php if ($is_duplicate_product): ?> js-dup-disabled<?php endif; ?>"<?php if ($is_duplicate_product): ?> readonly<?php endif; ?>
                                        type="text" name="<?php echo $attr_key . '_name'; ?>"
                                        value="<?php echo $trn_attribute['name'] ? $trn_attribute['name'] : ''; ?>"
                                        placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>" <?php if ($is_duplicate_product): ?> readonly<?php endif; ?> />

                                </td>
                            </tr>
                            <tr class="wcml-last-row" row-index="<?php echo $index; ?>">
                                <th>
                                    <label class="custom_attr_label"><?php _e('Value(s)', 'wpml-wcml'); ?></label>
                                </th>
                                <td>
                                    <input readonly class="original_value" value="<?php echo $attribute['value'] ?>"
                                           type="text"/>
                                </td>
                                <td>
                                    <input
                                        class="translated_value <?php if ($is_duplicate_product): ?> js-dup-disabled<?php endif; ?>"<?php if ($is_duplicate_product): ?>
                                        readonly"<?php endif; ?> type="text" name="<?php echo $attr_key; ?>"
                                    value="<?php echo $trn_attribute['value'] ? $trn_attribute['value'] : ''; ?>"
                                    placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>
                                    " <?php if ($is_duplicate_product): ?> readonly<?php endif; ?> />

                                </td>
                            </tr>
                        <?php $index++; endforeach; ?>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php
        if( !isset( $product_type ) ){
            foreach( wp_get_post_terms( $product_id, 'product_type', array( "fields" => "names" ) ) as $type ){
            $product_type = $type;
            }
        }

        if(!$woocommerce_wpml->settings['file_path_sync'] && isset( $product_type ) && $product_type == 'variable'): ?>

            <div class="postbox wpml-form-row wcml-row-images">
                <div title="<?php _e('Click to toggle'); ?>" class="handlediv"><br></div>
                <h3 class="hndle">
                    <span><?php _e('Variations files', 'wpml-wcml') ?></span>
                </h3>

                <div class="inside">
                    <?php echo $woocommerce_wpml->products->product_variations_box($product_id,$language,$is_duplicate_product); ?> ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
        $custom_fields = $woocommerce_wpml->products->get_product_custom_fields_to_translate( $product_id );
        $fields_to_translate_flag = true;

        foreach( $custom_fields as $custom_field ){
                if( $fields_to_translate_flag ){ ?>
                    <div class="postbox wpml-form-row wcml-row-custom-fields">
                        <div title="<?php _e( 'Click to toggle' ); ?>" class="handlediv"><br></div>
                        <h3 class="hndle">
                            <span><?php _e( 'Custom Fields', 'wpml-wcml' ) ?></span>
                        </h3>
                        <div class="inside">
                            <table id="prod_custom_fields" class="prod_custom_fields wcml-attr-table js-table">
                <?php $fields_to_translate_flag = false; } ?>
                                <tr class="wcml-first-row">
                                    <th>
                        <label > <?php echo $woocommerce_wpml->products->get_product_custom_field_label( $product_id, $custom_field ); ?> </label>
                                        </th><td>
                        <input readonly class="original_value" value="<?php echo get_post_meta( $product_id, $custom_field, true ) ?>"
                               type="text"></td>
                        <td><a class="button-copy button-secondary" title="<?php _e('Copy from original'); ?>">
                            <i class="otgs-ico-copy"></i>
                        </a></td>
                                    <td>
                        <input class="translated_value <?php if ($is_duplicate_product): ?> js-dup-disabled<?php endif; ?>"<?php if ($is_duplicate_product): ?> readonly<?php endif; ?>
                               name="<?php echo $custom_field; ?>" value="<?php echo $trn_product ? get_post_meta( $trn_product->ID, $custom_field, true) : '';  ?>" type="text"/></td></tr>

            <?php } ?>

            <?php  if( !$fields_to_translate_flag ){ ?>
                                </table>
                        </div>
                    </div>
            <?php }  ?>
    <?php
        /*
    elseif($product_content == '_file_paths'): ?>
    <textarea placeholder="<?php esc_attr_e('Upload file', 'wpml-wcml') ?>" value="" name='<?php echo $product_content.'_'.$key ?>' class="wcml_file_paths_textarea<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>></textarea>
    <button type="button" class="button-secondary wcml_file_paths<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>><?php _e('Choose a file', 'wpml-wcml') ?></button>
    */ ?>

        <?php //echo $woocommerce_wpml->products->custom_box( $product_id, $product_content, $trn_contents, $key, $lang, $is_duplicate_product ); ?>

        <?php do_action( 'wcml_gui_additional_box', $product_id, $language, $is_duplicate_product ); ?>

        <input type="hidden" name="original_product_id" value="<?php echo $product_id; ?>" />
        <input type="hidden" name="job_id" value="<?php echo $job_id; ?>" />
        <input type="hidden" name="language" value="<?php echo $language; ?>" />
        <input type="hidden" name="slang" value="<?php echo isset( $_GET['slang'] ) && $_GET['slang'] != 'all' ? $_GET['slang'] : false; ?>" />
        <input type="hidden" name="end_duplication" value="<?php echo !intval($is_duplicate_product) ?>" />
    </form>
</div>
<div class="wpml-dialog-footer wpml-sticky">
    <span class="errors icl_error_text"></span>

    <div class="wcml-pt-progress"></div>
    <div class="alignleft">
        <a class="button-secondary cancel wpml-dialog-close-button" ><?php _e('Cancel', 'wpml-wcml'); ?></a>
    </div>
    <div class="alignright">
        <?php $nonce = wp_create_nonce('update_product_actions'); ?>
        <a class="button-primary wpml-dialog-close-button" data-action="wcml_update_product"
           data-nonce="<?php echo $nonce; ?>"><?php _e('Save &amp; Close', 'wpml-wcml'); ?></a>
        <a class="button-primary wpml-dialog-close-button" data-action="wcml_update_product"
           data-nonce="<?php echo $nonce; ?>" data-stay="true"><?php _e('Save', 'wpml-wcml'); ?></a>
    </div>
</div>

<script type="text/javascript">
    postboxes.save_state = function () {
        return;
    };
    postboxes.save_order = function () {
        return;
    };
    postboxes.add_postbox_toggles();

    jQuery('.hidden_original_description>div').appendTo('.original_description .mce_editor_origin');
    jQuery('.hidden_translated_description>div').appendTo('.original_description .mce_editor');
    jQuery('.hidden_original_excerpt>div').appendTo('.wcml-row-excerpt .mce_editor_origin');
    jQuery('.hidden_translated_excerpt>div').appendTo('.wcml-row-excerpt .mce_editor');

    if( typeof tinyMCE !== 'undefined' ) {

        if(  tinyMCE.get('original_description_value') )
            tinyMCE.get('original_description_value').remove();

        if(  tinyMCE.get('original_excerpt_value') )
            tinyMCE.get('original_excerpt_value').remove();

        if(  tinyMCE.get('translated_description_value') )
            tinyMCE.get('translated_description_value').remove();

        if(  tinyMCE.get('translated_excerpt_value') )
            tinyMCE.get('translated_excerpt_value').remove();
    }

    if( jQuery('.original_description .mce_editor_origin > div').hasClass( 'tmce-active' ) ){
        jQuery('.original_description .mce_editor_origin .switch-tmce').trigger( 'click' );
    }

    if( jQuery('.wcml-row-excerpt .mce_editor_origin > div').hasClass( 'tmce-active' ) ){
        jQuery('.wcml-row-excerpt .mce_editor_origin .switch-tmce').click();
    }

    if( jQuery('.original_description .mce_editor > div').hasClass( 'tmce-active' ) ){
        jQuery('.original_description .mce_editor .switch-tmce').click();
    }

    if( jQuery('.wcml-row-excerpt .mce_editor > div').hasClass( 'tmce-active' ) ){
        jQuery('.wcml-row-excerpt .mce_editor .switch-tmce').click();
    }

    jQuery('#original_description_value').attr('readonly','readonly');
    jQuery('#original_excerpt_value').attr('readonly','readonly');

    jQuery('.original_description .mce_editor_origin .wcml_content_tr').trigger( 'setdefault' );
    jQuery('.original_description .mce_editor .wcml_content_tr').trigger( 'setdefault' );
    jQuery('.wcml-row-excerpt .mce_editor_origin .wcml_content_tr').trigger( 'setdefault' );
    jQuery('.wcml-row-excerpt .mce_editor .wcml_content_tr').trigger( 'setdefault' );

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

                                        <?php elseif(is_array($trn_contents)): ?>
                                            <?php foreach ($trn_contents as $tax_key=>$trn_content) : ?>
                                                <?php if($default_language == $key): ?>
                                                    <textarea rows="1" disabled="disabled"><?php echo $trn_content; ?></textarea>
                                                <?php else: ?>
                                                    <input class="<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" type="text" name="<?php echo $product_content.'_'.$key.'['.$tax_key.']'; ?>" value="<?php echo $trn_content ?>" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?> /><br>
                                                <?php endif;?>
                                            <?php endforeach; ?>


                                    </td>
                                <?php endforeach; ?>


                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </td>
</tr>

 */ ?>
