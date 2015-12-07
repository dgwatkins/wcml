<?php

$product_images = $woocommerce_wpml->products->product_images_ids($product_id);

$trid = $sitepress->get_element_trid($product_id, 'post_' . $product->post_type);
$product_translations = $sitepress->get_element_translations($trid, 'post_' . $product->post_type, true, true);
$check_on_permissions = false;
if (!current_user_can('wpml_operate_woocommerce_multilingual')) {
    $check_on_permissions = true;
}

$button_labels = array(
    'save' => esc_attr__('Save', 'woocommerce-multilingual'),
    'update' => esc_attr__('Update', 'woocommerce-multilingual'),
);

$is_duplicate_product = false;

if (isset($product_translations[$language]) && get_post_meta($product_translations[$language]->element_id, '_icl_lang_duplicate_of', true) == $product_id) {
    $is_duplicate_product = true;
}

?>

<div class="wpml-dialog wpml-dialog-translate wcml-pt-form">
    <header class="wpml-dialog-header">
        <h2 class="wpml-dialog-title"><?php printf(__('Product translation:  %s', 'woocommerce-multilingual'), '<strong>' . $product->post_title . '</strong>'); ?></h2>
        <a href="<?php echo get_post_permalink($product_id); ?>" class="view"
           title="<?php printf(__('View "%s"', 'woocommerce-multilingual'), $product->post_title); ?>"><?php _e('View Product', 'woocommerce-multilingual'); ?> </a>
        <i class="otgs-ico-close wpml-dialog-close-button"></i>
    </header>
    <form class="wpml-dialog-body"
          id="poststuff"> <?php //   IMpoRTANT This ID must stay like this if it is impossible -> create additional div ?>
        <header class="wpml-translation-header">
            <?php if( $is_duplicate_product ): ?>
                <h3 class="js-wcml_duplicate_product_notice" >
                <span style="height: 38px"><?php printf(__('This product is an exact duplicate of the %s product.', 'wcml-wpml'),
                    $active_languages[ $original_language ]['display_name'] ); ?></span>
                    <a class="button-primary duplicate_edit" ><?php _e('Edit independently', 'woocommerce-multilingual') ?></a>
                </h3>
                <h3 class="js-wcml_duplicate_product_undo" style="display: none;" >
                    <a class="button-secondary duplicate_cancel"><?php _e('Undo (keep this product as a duplicate)', 'woocommerce-multilingual') ?></a>
                </h3>
            <?php endif; ?>
            <h3 class="wpml-header-original"><?php _e('Original', 'woocommerce-multilingual'); ?>:
                <span class="wpml-title-flag">
                    <img src="<?php echo $sitepress->get_flag_url($original_language) ?>"
                         alt="<?php echo $active_languages[$original_language]['english_name'] ?>"/>
                </span>
                <strong><?php echo $active_languages[$original_language]['english_name'] ?></strong>
            </h3>

            <h3 class="wpml-header-translation"><?php _e('Translation to', 'woocommerce-multilingual'); ?>:
                <span class="wpml-title-flag">
                    <img src="<?php echo $sitepress->get_flag_url($language) ?>"
                         alt="<?php echo $active_languages[$language]['english_name'] ?>"/>
                </span>
                <strong><?php echo $active_languages[$language]['english_name'] ?></strong>
            </h3>

            <div class="wpml-copy-container">
                <a class="button-secondary button-copy-all" title="<?php _e('Copy from original'); ?>">
                    <i class="otgs-ico-copy"></i>
                    <?php _e('Copy all fields from original', 'woocommerce-multilingual'); ?>
                </a>
            </div>
        </header>

        <div class="wpml-form-row">
            <label for="term-name"> <?php _e('Title', 'woocommerce-multilingual'); ?> </label>
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
            <label for="term-slug"><?php _e('Slug', 'woocommerce-multilingual'); ?></label>
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
            <label for="term-description"><?php _e('Content', 'woocommerce-multilingual'); ?>
                /<br><?php _e('Description', 'woocommerce-multilingual'); ?></label>
            <div class="mce_editor_origin">
            </div>
            <a class="button-copy button-secondary" title="<?php _e('Copy from original'); ?>" id="">
                <i class="otgs-ico-copy"></i>
            </a>
            <div class="mce_editor">
            </div>
            <textarea id="hidden_original_description_value" ><?php echo apply_filters( 'the_content', $product->post_content )?></textarea>
            <textarea id="hidden_translated_description_value" ><?php echo apply_filters( 'the_content', $trn_product ? $trn_product->post_content : '' ) ?></textarea>
        </div>

        <div class="postbox wpml-form-row wcml-row-excerpt">
            <div title="<?php _e('Click to toggl'); ?>" class="handlediv"><br></div>
            <h3 class="hndle">
                <span><?php _e('Excerpt', 'woocommerce-multilingual'); ?></span>
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

            <textarea id="hidden_original_excerpt_value" ><?php echo apply_filters( 'the_content', $product->post_excerpt ) ?></textarea>
            <textarea id="hidden_translated_excerpt_value" ><?php echo apply_filters( 'the_content', $trn_product ? $trn_product->post_excerpt : '' ) ?></textarea>
        </div>

        <?php
        $purchase_note = get_post_meta($product_id, '_purchase_note', true);
        ?>
        <div class="postbox wpml-form-row wcml-row-purchase-note <?php echo !$purchase_note ? 'closed' : '' ?> ">
            <div title="<?php _e('Click to toggle'); ?>" class="handlediv"><br></div>
            <h3 class="hndle">
                <span><?php _e('Purchase note', 'woocommerce-multilingual') ?><?php echo !$purchase_note ? '&nbsp;<em>' . __('(empty)', 'woocommerce-multilingual') . '</em>' : '' ?> </span>
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


        <?php $product_images = $woocommerce_wpml->products->product_images_ids( $product_id ); ?>
        <div class="postbox wpml-form-row wcml-row-images <?php echo empty( $product_images ) ? 'closed' : '' ?>">
            <div title="<?php _e('Click to toggle'); ?>" class="handlediv"><br></div>
            <h3 class="hndle">
                <span><?php _e('Images', 'woocommerce-multilingual') ?></span>
            </h3>

            <div class="inside">
                <?php echo $woocommerce_wpml->products->product_images_box($product_id, $language, $is_duplicate_product, $product_images ); ?>
            </div>
        </div>

        <?php $attributes = $woocommerce_wpml->products->get_custom_product_atributes($product_id); ?>
        <?php if ($attributes): ?>
            <div class="postbox wpml-form-row wcml-row-attributes">
                <div title="<?php _e('Click to toggl'); ?>" class="handlediv"><br></div>
                <h3 class="hndle">
                    <span><?php _e('Custom Product attributes', 'woocommerce-multilingual'); ?></span>
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
                                        class="translated_value <?php if ($is_duplicate_product): ?> js-dup-disabled<?php endif; ?>" <?php if ($is_duplicate_product): ?>
                                         readonly="readonly"<?php endif; ?>
                                        type="text" name="<?php echo $attr_key; ?>"
                                        value="<?php echo $trn_attribute['value'] ? $trn_attribute['value'] : ''; ?>"
                                        placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"  />
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

        if(isset( $product_type ) && $product_type == 'variable'): ?>

            <div class="postbox wpml-form-row wcml-row-images">
                <div title="<?php _e('Click to toggle'); ?>" class="handlediv"><br></div>
                <h3 class="hndle">
                    <span><?php _e('Variations', 'woocommerce-multilingual') ?></span>
                </h3>

                <div class="inside">
                    <?php echo $woocommerce_wpml->products->product_variations_box( $product_id, $language, $is_duplicate_product ); ?>
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
                        <span><?php _e( 'Custom Fields', 'woocommerce-multilingual' ) ?></span>
                    </h3>
                    <div class="inside">
                        <table id="prod_custom_fields" class="prod_custom_fields wcml-attr-table js-table">
            <?php $fields_to_translate_flag = false; } ?>
                            <tr class="wcml-first-row">
                                <th>
                                    <label > <?php echo $woocommerce_wpml->products->get_product_custom_field_label( $product_id, $custom_field ); ?> </label>
                                    </th>
                                <td>
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
        <a class="button-secondary cancel wpml-dialog-close-button" ><?php _e('Cancel', 'woocommerce-multilingual'); ?></a>
    </div>
    <div class="alignright">
        <?php $nonce = wp_create_nonce('update_product_actions'); ?>
        <a class="button-primary wpml-dialog-close-button" data-action="wcml_update_product"
           data-nonce="<?php echo $nonce; ?>"><?php _e('Save &amp; Close', 'woocommerce-multilingual'); ?></a>
        <a class="button-primary wpml-dialog-close-button" data-action="wcml_update_product"
           data-nonce="<?php echo $nonce; ?>" data-stay="true"><?php _e('Save', 'woocommerce-multilingual'); ?></a>
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