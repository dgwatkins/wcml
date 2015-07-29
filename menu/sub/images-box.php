<table id="prod_images_<?php echo $lang ?>" class="widefat prod_images js-table">
    <tbody>
        <?php if ( isset( $empty_images ) ): ?>
            <tr>
                <td><?php _e('Please set images for product','wpml-wcml'); ?></td>
            </tr>
        <?php else: ?>
            <?php foreach ( $product_images as $prod_image ) : ?>
                <tr>
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

                    <td>
                        <?php echo wp_get_attachment_image( $prod_image , 'thumbnail'); ?>
                    </td>
                    <td>
                        <?php _e('Title','wpml-wcml');  ?>
                        <input type="text" value="<?php echo $attachment_data->post_title ?>" readonly="readonly"/>
                        <input type="text" name="images[title]" value="<?php echo $images_texts['title']; ?>" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"/>
                    </td>

                    <td>
                        <?php _e('Caption','wpml-wcml');  ?>
                        <input type="text" value="<?php echo $attachment_data->post_excerpt ?>" readonly="readonly"/>
                        <input type="text" name="images[caption]" value="<?php echo $images_texts['caption']; ?>" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"/>
                    </td>

                    <td>
                        <?php _e('Description','wpml-wcml');  ?>
                        <input type="text" value="<?php echo $attachment_data->post_content ?>" readonly="readonly"/>
                        <input type="text" name="images[description]" value="<?php echo $images_texts['description']; ?>" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"/>
                    </td>

            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>