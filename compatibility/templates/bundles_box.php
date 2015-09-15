<?php if(!isset($template_data['empty_bundles'])){  ?>
    <div class="postbox wpml-form-row wcml-row-bundles">
        <div title="<?php _e('Click to toggle'); ?>" class="handlediv"><br></div>
        <h3 class="hndle">
            <span><?php _e('Product Bundles', 'wpml-wcml') ?></span>
        </h3>

        <div class="inside">
            <table id="prod_bundles" class="wcml-attr-table prod_bundles js-table">
                <tbody>
                    <?php
                    $i = 0;

                    foreach( $template_data['bundles_data'] as $key => $bundle ) :
                        $index = '';
                        if( $bundle['original']['override_bundle_title'] == 'yes' && $bundle['original']['override_bundle_desc'] == 'yes' ){
                            $index = $i;
                            $i++;
                        }
                        ?>

                        <tr class="wcml-line-row" >
                            <th>
                                <label class="custom_attr_label"><?php echo $bundle['bundle_name']; ?></label>
                            </th>

                        </tr>

                        <?php if( isset( $bundle['empty_bundles'] ) ) : ?>
                            <tr class="wcml-first-row" row-index="<?php echo $index; ?>">
                                <th>
                                    <label class="custom_attr_label"><?php _e('You need translate bundled product first', 'wpml-wcml'); ?></label>
                                </th>
                            </tr>
                        <?php continue; endif; ?>

                        <?php if( $bundle['original']['override_bundle_title']  == 'yes' ): ?>

                            <tr class="wcml-first-row" row-index="<?php echo $index; ?>">
                                <th>
                                    <label class="custom_attr_label"><?php _e('Name', 'wpml-wcml'); ?></label>
                                </th>
                                <td>
                                    <input readonly class="original_value" value="<?php echo $bundle['original']['bundle_title'] ?>"
                                           type="text"/>
                                </td>
                                <td <?php echo $index ? 'rowspan="2"' : '' ?> class="button-copy-cell">
                                    <a class="button-copy button-secondary" title="<?php _e('Copy from original'); ?>"
                                       id="">
                                        <i class="otgs-ico-copy"></i>
                                    </a>
                                </td>
                                <td>
                                    <input
                                        class="translated_value <?php if ($is_duplicate_product): ?> js-dup-disabled<?php endif; ?>"<?php if ($is_duplicate_product): ?> readonly<?php endif; ?>
                                        type="text" name="<?php echo  'bundles['.$bundle['bundle_id']. '][title]'; ?>"
                                        value="<?php echo $bundle['translated']['bundle_title']; ?>"
                                        placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>" <?php if ($is_duplicate_product): ?> readonly<?php endif; ?> />

                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php if( $bundle['original']['override_bundle_desc']  == 'yes' ): ?>
                            <tr class="wcml-last-row" row-index="<?php echo $index; ?>">
                                <th>
                                    <label class="custom_attr_label"><?php _e('Description', 'wpml-wcml'); ?></label>
                                </th>
                                <td>
                                    <input readonly class="original_value" value="<?php echo $bundle['original']['bundle_desc'] ?>"
                                           type="text"/>
                                </td>
                                <td>
                                    <input
                                        class="translated_value <?php if ($is_duplicate_product): ?> js-dup-disabled<?php endif; ?>"<?php if ($is_duplicate_product): ?> readonly<?php endif; ?>
                                    type="text" name="<?php echo 'bundles['.$bundle['bundle_id'].'][desc]'; ?>"
                                    value="<?php echo $bundle['translated']['bundle_desc']; ?>"
                                    placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>
                                    " <?php if ($is_duplicate_product): ?> readonly<?php endif; ?> />
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php } ?>