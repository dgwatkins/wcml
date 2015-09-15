<table id="prod_variations" class="wcml-attr-table prod_variations js-table">
    <tbody>
        <?php if(isset($template_data['empty_variations'])): ?>
            <tr>
                <td><?php _e('Please add variations to product','wpml-wcml'); ?></td>
            <tr>
        <?php elseif(!$woocommerce_wpml->settings['file_path_sync'] && isset($template_data['empty_translation'])): ?>
            <tr>
                <td><?php _e('Please save translation before translate variations file paths','wpml-wcml'); ?></td>
            </tr>
        <?php endif;?>

        <?php foreach($template_data['all_variations_ids'] as $variation_id):
            $tr_variation_id = $woocommerce_wpml->products->wcml_get_translated_variation( $variation_id, $lang );
            ?>

            <tr class="wcml-first-row" row-index="<?php //echo $index; ?>">
                <th>
                    <label class="custom_attr_label"><?php printf(__('%s Description', 'wpml-wcml'),'<strong>#' . $variation_id . ':</strong>'); ?></label>
                </th>
                <td>
                    <input readonly class="original_value" value="<?php echo get_post_meta( $variation_id, '_variation_description', true) ?>" type="text"/>
                </td>
                <td <?php //rowspan="2" ?> class="button-copy-cell">
                    <a class="button-copy button-secondary" title="<?php _e('Copy from original'); ?>" >
                        <i class="otgs-ico-copy"></i>
                    </a>
                </td>
                <td>
                    <input
                        class="translated_value <?php if ($is_duplicate_product): ?> js-dup-disabled<?php endif; ?>"<?php if ($is_duplicate_product): ?> readonly<?php endif; ?>
                        type="text" name="<?php echo 'variation_desc['.$tr_variation_id . ']'; ?>"
                        value="<?php echo get_post_meta( $tr_variation_id, '_variation_description', true) ?>"
                        placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>" <?php if ($is_duplicate_product): ?> readonly<?php endif; ?> />

                </td>
            </tr>
            <?php if( isset($is_downloable) && $is_downloable ): ?>
                <tr class="wcml-last-row" row-index="<?php echo $index; ?>">
                    <th>
                        <?php echo $template_data['all_file_paths'][$variation_id]['label']; ?>
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

                <tr>
                    <td><?php _e('Download URL','wpml-wcml'); ?></td>
                    <?php foreach($template_data['all_variations_ids'] as $variation_id): $file_paths = ''; ?>
                        <?php if(isset($template_data['all_file_paths'][$variation_id]['not_translated'])){
                            echo '<td></td>';
                            continue;
                        }
                        if(get_post_meta($variation_id,'_downloadable',true) == 'yes'): ?>
                            <td>
                                <?php if(version_compare(preg_replace('#-(.+)$#', '', $woocommerce->version), '2.1', '<')){
                                    $file_paths_array = maybe_unserialize($template_data['all_file_paths'][$variation_id]['value']);
                                    if($file_paths_array)
                                        foreach($file_paths_array as $trn_file_paths){
                                            $file_paths = $file_paths ? $file_paths . "\n" .$trn_file_paths : $trn_file_paths;
                                        }
                                    if($template_data['original']): ?>
                                        <textarea value="<?php echo $file_paths; ?>" class="wcml_file_paths_textarea" disabled="disabled"><?php echo $file_paths; ?></textarea>
                                    <?php else: ?>
                                        <textarea value="<?php echo $file_paths; ?>" name='<?php echo 'variations_file_paths['.$variation_id.']'; ?>' class="wcml_file_paths_textarea" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"><?php echo $file_paths; ?></textarea>
                                        <button type="button" class="button-secondary wcml_file_paths"><?php _e('Choose a file', 'wpml-wcml') ?></button>
                                    <?php endif;
                                }else{
                                    for($i=0;$i<$template_data['all_file_paths']['count'];$i++): ?>
                                        <?php if($template_data['original']): ?>
                                            <input type="text" value="<?php echo $template_data['all_file_paths'][$variation_id][$i]['label']; ?>" class="" disabled="disabled">
                                            <input type="text" value="<?php echo $template_data['all_file_paths'][$variation_id][$i]['value']; ?>" class="" disabled="disabled">
                                        <?php else: ?>
                                            <div>
                                                <input type="text" value="<?php echo isset($template_data['all_file_paths'][$variation_id][$i])?$template_data['all_file_paths'][$variation_id][$i]['label']:''; ?>" name='<?php echo 'variations_file_paths['.$variation_id.']['.$i.'][name]'; ?>' class="wcml_file_paths_name" placeholder="<?php esc_attr_e('Enter translation for name', 'wpml-wcml') ?>">
                                                <input type="text" value="<?php echo isset($template_data['all_file_paths'][$variation_id][$i])?$template_data['all_file_paths'][$variation_id][$i]['value']:''; ?>" name='<?php echo 'variations_file_paths['.$variation_id.']['.$i.'][file]'; ?>' class="wcml_file_paths_file" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"/>
                                                <button type="button" class="button-secondary wcml_file_paths_button"><?php _e('Choose a file', 'wpml-wcml') ?></button>
                                            </div>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                <?php }
                                ?>
                            </td>
                        <?php else: ?>
                            <td><?php _e('Variation is not downloadable','wpml-wcml'); ?></td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>

            <?php endif; ?>

        <?php endforeach; ?>
    </tbody>
</table>