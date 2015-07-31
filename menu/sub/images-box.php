<table id="prod_images_<?php echo $lang ?>" class="prod_images js-table">
    <tbody>
        <?php if ( isset( $empty_images ) ): ?>
            <tr>
                <td><?php _e('Please set images for product','wpml-wcml'); ?></td>
            </tr>
        <?php else: ?>
            <?php foreach ( $product_images as $prod_image ) : ?>
                <tr class="prod_images-first-row">
                    <?php
                    $attachment_data = $wpdb->get_row( $wpdb->prepare( "SELECT post_title,post_excerpt,post_content FROM $wpdb->posts WHERE ID = %d", $prod_image ) );
                    $trnsl_prod_image = apply_filters( 'translate_object_id', $prod_image, 'attachment', false, $lang );
                    $images_texts = array( 'title' => '', 'caption' => '', 'description' => '' );
                    if ( !is_null( $trnsl_prod_image ) ){
                        $trnsl_attachment_data = $wpdb->get_row( $wpdb->prepare( "SELECT post_title,post_excerpt,post_content FROM $wpdb->posts WHERE ID = %d", $trnsl_prod_image ) );
                        $images_texts['title'] = $trnsl_attachment_data->post_title;
                        $images_texts['caption'] = $trnsl_attachment_data->post_excerpt;
                        $images_texts['description'] = $trnsl_attachment_data->post_content;
                    } ?>

                    <td rowspan="3">
                        <?php echo wp_get_attachment_image( $prod_image , 'thumbnail'); ?>
                    </td>
                    <th>
                        <?php _e('Title','wpml-wcml');  ?>
                    </th>
                    <td>
                        <input type="text" value="<?php echo $attachment_data->post_title ?>" readonly="readonly"/>
                    </td>
                    <td rowspan="3" class="button-copy-cell">
                        <a class="button-copy" title="<?php _e( 'Copy from original' ); ?>"><i
                                class="otgs-ico-copy otgs-ico-32"></i></a>
                    </td>

                    <td>
                        <input type="text" name="images[title]" value="<?php echo $images_texts['title']; ?>" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"/>
                    </td>
                    </tr>
                <tr>
                    <th>
                        <?php _e('Caption','wpml-wcml');  ?>
                    </th>
                    <td>

                        <input type="text" value="<?php echo $attachment_data->post_excerpt ?>" readonly="readonly"/>

                    </td>
                    <td>
                        <input type="text" name="images[caption]" value="<?php echo $images_texts['caption']; ?>" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"/>
                    </td>
                </tr>
                <tr class="prod_images-last-row">
                    <th>
                        <?php _e('Description','wpml-wcml');  ?>
                    </th>
                    <td>
                        <input type="text" value="<?php echo $attachment_data->post_content ?>" readonly="readonly"/>
                    </td>
                    <td>
                        <input type="text" name="images[description]" value="<?php echo $images_texts['description']; ?>" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"/>
                    </td>
                </tr>


            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>