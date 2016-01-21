<?php global $sitepress_settings;
$default_language = $sitepress->get_default_language();
?>
<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">

    <div class="wcml-section">
        <div class="wcml-section-header">
            <h3>
                <?php _e('Product Translation Interface','woocommerce-multilingual'); ?>
                <i class="otgs-ico-help wcml-tip"
                   data-tip="<?php _e( 'The recommended way to translate products is using the products translation table in the WooCommerce Multilingual admin. Choose to go to the native WooCommerce interface, if your products include custom sections that require direct access.', 'woocommerce-multilingual' ) ?>"></i>
            </h3>
        </div>
        <div class="wcml-section-content">

            <ul>
                <li>
                    <p><?php _e('Choose what to do when clicking on the translation controls for products:', 'woocommerce-multilingual'); ?></p>
                </li>
                <li>
                    <input type="radio" name="trnsl_interface" value="1" <?php echo $woocommerce_wpml->settings['trnsl_interface'] == '1'?'checked':''; ?> id="wcml_trsl_interface_wcml" />
                    <label for="wcml_trsl_interface_wcml"><?php _e('Open the WooCommerce Multilingual product translation interface', 'woocommerce-multilingual'); ?></label>
                </li>
                <li>
                    <input type="radio" name="trnsl_interface" value="0" <?php echo $woocommerce_wpml->settings['trnsl_interface'] == '0'?'checked':''; ?> id="wcml_trsl_interface_native" />
                    <label for="wcml_trsl_interface_native"><?php _e('Go to the native WooCommerce product editing screen', 'woocommerce-multilingual'); ?></label>
                </li>
            </ul>

        </div> <!-- .wcml-section-content -->

    </div> <!-- .wcml-section -->

    <div class="wcml-section">

        <div class="wcml-section-header">
            <h3>
                <?php _e('Products synchronization', 'woocommerce-multilingual'); ?>
                <i class="otgs-ico-help wcml-tip"
                   data-tip="<?php _e( 'Configure specific product properties that should be synced to translations.', 'woocommerce-multilingual' ) ?>"></i>
            </h3>
        </div>

        <div class="wcml-section-content">

            <ul>
                <li>
                    <input type="checkbox" name="products_sync_date" value="1" <?php echo checked(1, $woocommerce_wpml->settings['products_sync_date']) ?> id="wcml_products_sync_date" />
                    <label for="wcml_products_sync_date"><?php _e('Sync publishing date for translated products.', 'woocommerce-multilingual'); ?></label>
                </li>
                <li>
                    <input type="checkbox" name="products_sync_order" value="1" <?php echo checked(1, $woocommerce_wpml->settings['products_sync_order']) ?> id="wcml_products_sync_order" />
                    <label for="wcml_products_sync_order"><?php _e('Sync products and product taxonomies order.', 'woocommerce-multilingual'); ?></label>
                </li>
            </ul>

        </div>

    </div>


    <div class="wcml-section">

        <div class="wcml-section-header">
            <h3>
                <?php _e('File Paths Synchronization ', 'woocommerce-multilingual'); ?>
                <i class="otgs-ico-help wcml-tip"
                   data-tip="<?php _e( 'If you are using downloadable products, you can choose to have their paths synchronized, or seperate for each language.', 'woocommerce-multilingual' ) ?>"></i>
            </h3>
        </div>

        <div class="wcml-section-content">

            <ul>
                <li>
                    <input type="radio" name="wcml_file_path_sync" value="1" <?php echo $woocommerce_wpml->settings['file_path_sync'] == '1'?'checked':''; ?> id="wcml_file_path_sync_auto" />
                    <label for="wcml_file_path_sync_auto"><?php _e('Use the same file paths in all languages', 'woocommerce-multilingual'); ?></label>
                </li>
                <li>
                    <input type="radio" name="wcml_file_path_sync" value="0" <?php echo $woocommerce_wpml->settings['file_path_sync'] == '0'?'checked':''; ?> id="wcml_file_path_sync_self" />
                    <label for="wcml_file_path_sync_self"><?php _e('Different file paths for each language', 'woocommerce-multilingual'); ?></label>
                </li>
            </ul>


        </div> <!-- .wcml-section-content -->

    </div> <!-- .wcml-section -->

    <?php wp_nonce_field('wcml_save_settings_nonce', 'wcml_nonce'); ?>
    <p class="wpml-margin-top-sm">
        <input type='submit' name="wcml_save_settings" value='<?php esc_attr( _e( 'Save changes', 'woocommerce-multilingual' ) ); ?>' class='button-primary'/>
    </p>
</form>
    <a class="alignright"
    href="<?php echo admin_url( 'admin.php?page=' . basename( WCML_PLUGIN_PATH ) . '/menu/sub/troubleshooting.php' ); ?>"><?php _e( 'Troubleshooting page', 'woocommerce-multilingual' ); ?></a>
