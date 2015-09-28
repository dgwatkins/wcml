<div class="postbox wpml-form-row wcml-row-attributes">
    <div title="<?php _e('Click to toggl'); ?>" class="handlediv"><br></div>
    <h3 class="hndle">
        <span><?php _e('Bookings', 'woocommerce-multilingual'); ?></span>
    </h3>

    <div class="inside">
        <table id="prod_bookings" class="wcml-attr-table js-table">

            <?php if( get_post_meta( $product_id,'_wc_booking_has_resources',true) == 'yes' ): ?>

                <tr class="wcml-first-row" >
                    <th>
                        <label class="custom_attr_label"><?php _e('Resources Label', 'woocommerce-multilingual'); ?></label>
                    </th>
                    <td>
                        <input readonly class="original_value" value="<?php echo get_post_meta( $product_id,'_wc_booking_resouce_label',true) ?>" type="text"/>
                    </td>
                    <td class="button-copy-cell">
                        <a class="button-copy button-secondary" title="<?php _e('Copy from original'); ?>"
                           id="">
                            <i class="otgs-ico-copy"></i>
                        </a>
                    </td>
                    <td>
                        <input
                            class="translated_value <?php if ($is_duplicate_product): ?> js-dup-disabled<?php endif; ?>"<?php if ($is_duplicate_product): ?> readonly<?php endif; ?>
                            type="text" name="<?php echo '_wc_booking_resouce_label'; ?>"
                            value="<?php echo $tr_product_id ? get_post_meta( $tr_product_id,'_wc_booking_resouce_label',true) : ''; ?>"
                            placeholder="<?php esc_attr_e('Enter translation', 'woocommerce-multilingual') ?>" <?php if ($is_duplicate_product): ?> readonly<?php endif; ?> />

                    </td>
                </tr>

            <?php endif; ?>

            <?php foreach( $template_data[ 'resources' ] as $original_resource_id => $trnsl_resource_id ): ?>
                <tr class="wcml-line-row" >
                    <th colspan="4">
                        <label class="custom_attr_label"><?php _e('Resources', 'woocommerce-multilingual'); ?></label>
                    </th>

                </tr>
                <tr class="wcml-first-row">
                    <th>
                        <label class="custom_attr_label"><?php _e('Title', 'woocommerce-multilingual'); ?></label>
                    </th>
                    <td>
                        <input readonly class="original_value" value="<?php echo get_the_title( $original_resource_id ) ?>" type="text"/>
                    </td>
                    <td class="button-copy-cell">
                        <a class="button-copy button-secondary" title="<?php _e('Copy from original'); ?>"
                           id="">
                            <i class="otgs-ico-copy"></i>
                        </a>
                    </td>
                    <td>
                        <input type="hidden" name="<?php echo 'wc_booking_resources_'.$lang.'[id][]'; ?>" value="<?php echo $trnsl_resource_id; ?>" />
                        <?php if( empty( $trnsl_resource_id ) ): ?>
                            <input type="hidden" name="<?php echo 'wc_booking_resources_'.$lang.'[orig_id][]'; ?>" value="<?php echo $original_resource_id; ?>" />
                        <?php endif;?>
                        <input
                            class="translated_value <?php if ($is_duplicate_product): ?> js-dup-disabled<?php endif; ?>"<?php if ($is_duplicate_product): ?> readonly<?php endif; ?>
                            type="text" name="<?php echo 'wc_booking_resources_'.$lang.'[title][]';; ?>"
                            value="<?php echo $res_translation_exist ? get_the_title( $trnsl_resource_id ) : ''; ?>"
                            placeholder="<?php esc_attr_e('Enter translation', 'woocommerce-multilingual') ?>" <?php if ($is_duplicate_product): ?> readonly<?php endif; ?> />

                    </td>
                </tr>
            <?php endforeach; ?>

            <?php
            $index = 1;
            foreach( $template_data[ 'persons' ] as $original_person_id => $trnsl_person_id ): ?>
                <?php if( !$index ) :?>
                    <tr class="wcml-line-row">
                        <th colspan="4">
                            <label class="custom_attr_label"><?php _e('Person Types', 'woocommerce-multilingual'); ?></label>
                        </th>

                    </tr>
                <?php endif; ?>
                <tr class="wcml-first-row" row-index="<?php echo $index; ?>">
                    <th>
                        <label class="custom_attr_label"><?php _e('Person Type Name', 'woocommerce-multilingual'); ?></label>
                    </th>
                    <td>
                        <input readonly class="original_value" value="<?php echo get_the_title( $original_person_id ) ?>"
                               type="text"/>
                    </td>
                    <td rowspan="2" class="button-copy-cell">
                        <a class="button-copy button-secondary" title="<?php _e('Copy from original'); ?>"
                           id="">
                            <i class="otgs-ico-copy"></i>
                        </a>
                    </td>
                    <td>
                        <input type="hidden" name="<?php echo 'wc_booking_persons_'.$lang.'[id][]'; ?>" value="<?php echo $trnsl_person_id; ?>" />
                        <?php if( empty( $trnsl_person_id ) ): ?>
                            <input type="hidden" name="<?php echo 'wc_booking_persons_'.$lang.'[orig_id][]'; ?>" value="<?php echo $original_person_id; ?>" />
                        <?php endif;?>
                        <input
                            class="translated_value <?php if ($is_duplicate_product): ?> js-dup-disabled<?php endif; ?>"<?php if ($is_duplicate_product): ?> readonly<?php endif; ?>
                            type="text" name="<?php echo 'wc_booking_persons_'.$lang.'[title][]'; ?>"
                            value="<?php echo $per_translation_exist  ? get_the_title( $trnsl_person_id ) : ''; ?>"
                            placeholder="<?php esc_attr_e('Enter translation', 'woocommerce-multilingual') ?>" <?php if ($is_duplicate_product): ?> readonly<?php endif; ?> />

                    </td>
                </tr>
                <tr class="wcml-last-row" row-index="<?php echo $index; ?>">
                    <th>
                        <label class="custom_attr_label"><?php _e('Description', 'woocommerce-multilingual'); ?></label>
                    </th>
                    <td>
                        <input readonly class="original_value" value="<?php echo get_post( $original_person_id )->post_excerpt ?>"
                               type="text"/>
                    </td>
                    <td>
                        <input
                            class="translated_value <?php if ($is_duplicate_product): ?> js-dup-disabled<?php endif; ?>"<?php if ($is_duplicate_product): ?>
                            readonly<?php endif; ?> type="text" name="<?php echo 'wc_booking_persons_'.$lang.'[description][]'; ?>"
                            value="<?php echo $per_translation_exist ? get_post( $trnsl_person_id )->post_excerpt : ''; ?>"
                            placeholder="<?php esc_attr_e('Enter translation', 'woocommerce-multilingual') ?>
                " <?php if ($is_duplicate_product): ?> readonly<?php endif; ?> />

                    </td>
                </tr>
                <?php $index++; endforeach; ?>
        </table>
    </div>
</div>