<div class="postbox wpml-form-row wcml-row-attributes">
    <div title="<?php _e('Click to toggl'); ?>" class="handlediv"><br></div>
    <h3 class="hndle">
        <span><?php _e('Product tabs', 'wpml-wcml'); ?></span>
    </h3>

    <div class="inside">
        <table id="prod_tabs" class="wcml-attr-table js-table">
            <?php $index = 1;
            foreach( $template_data['orig_tabs'] as $key => $values ):
            $trnsl_tab_id = isset($template_data['tr_tabs'][$key]['id'])?$template_data['tr_tabs'][$key]['id']:'';
            $orig_tab_id = $template_data['orig_tabs'][$key]['id'];

            if($values['type'] == 'product'){
                $label = get_the_title($orig_tab_id);

                $orig_title = get_the_title($orig_tab_id);
                $trnsl_title = get_the_title($trnsl_tab_id);
                $title_input_name = '_product_tabs_'.$lang.'[title][]';

                $orig_heading = get_post($orig_tab_id)->post_content;
                if($trnsl_tab_id){
                    $trnsl_heading = get_post($trnsl_tab_id)->post_content;
                }else{
                    $trnsl_heading = '';
                }
                $heading_input_name = '_product_tabs_'.$lang.'[content][]';
            }else{
                $label = str_replace( '_', ' ' , ucfirst( $key ) );
                $orig_title = $values['title'];
                $trnsl_title = isset($template_data['tr_tabs'][ $key ]['title']) ? $template_data['tr_tabs'][ $key ]['title'] : '';
                $title_input_name = '_product_tabs_'.$lang.'[core_title]['.$key.']';

                $orig_heading = $values['heading'];
                $trnsl_heading = isset( $template_data['tr_tabs'][ $key ]['heading']) ? $template_data['tr_tabs'][$key]['heading']: '';
                $heading_input_name = '_product_tabs_'.$lang.'[core_heading]['.$key.']';
            }
            ?>
                <tr class="wcml-line-row">
                    <th colspan="4">
                        <label class="custom_attr_label"><?php echo $label; ?></label>
                    </th>

                </tr>

                <tr class="wcml-first-row" row-index="<?php echo $index; ?>">
                    <th>
                        <label class="custom_attr_label"><?php _e('Title', 'wpml-wcml'); ?></label>
                    </th>
                    <td>
                        <input readonly class="original_value" value="<?php echo $orig_title ?>" type="text"/>
                    </td>
                    <td <?php if( $orig_heading ): ?> rowspan="2" <?php endif ?> class="button-copy-cell">
                        <a class="button-copy button-secondary" title="<?php _e('Copy from original'); ?>"
                           id="">
                            <i class="otgs-ico-copy"></i>
                        </a>
                    </td>
                    <td>
                        <?php if( $values['type'] == 'product' ): ?>
                            <input type="hidden" name="<?php echo '_product_tabs_'.$lang.'[id][]'; ?>" value="<?php echo $trnsl_tab_id; ?>" />
                        <?php endif;?>
                        <input
                            class="translated_value <?php if ($is_duplicate_product): ?> js-dup-disabled<?php endif; ?>"<?php if ($is_duplicate_product): ?> readonly<?php endif; ?>
                            type="text" name="<?php echo $title_input_name; ?>"
                            value="<?php echo $trnsl_title ?>"
                            placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>" <?php if ($is_duplicate_product): ?> readonly<?php endif; ?> />

                    </td>
                </tr>
                <?php if( $orig_heading ): ?>
                    <tr class="wcml-last-row" row-index="<?php echo $index; ?>">
                        <th>
                            <label class="custom_attr_label"><?php _e('Heading', 'wpml-wcml'); ?></label>
                        </th>
                        <td>
                            <?php if( $values['type'] == 'product' ): ?>
                                <textarea readonly class="original_value" rows="4"><?php echo $orig_heading; ?></textarea>
                            <?php else: ?>
                                <input readonly class="original_value" value="<?php echo $orig_heading ?>" type="text"/>
                            <?php endif; ?>
                        </div>
                        <div class="wcml_editor_buttons">
                            <?php if($template_data['original']): ?>
                                <button type="button" class="button-secondary wcml_popup_close"><?php _e('Close', 'wpml-wcml') ?></button>
                            <?php else: ?>
                                <h3><?php printf(__('%s translation', 'wpml-wcml'),$template_data['lang_name']); ?></h3>
                                <button type="button" class="button-secondary wcml_popup_cancel"><?php _e('Cancel', 'wpml-wcml') ?></button>
                                <button type="button" class="button-secondary wcml_popup_ok"><?php _e('Ok', 'wpml-wcml') ?></button>
                            <?php endif; ?>

                        </td>
                    </tr>
                <?php endif; ?>

            <?php $index++; endforeach; ?>
        </table>
    </div>
</div>