<?php
//actions
global $woocommerce_wpml, $sitepress,$wpdb, $wp_taxonomies;

$current_language = $sitepress->get_current_language();
$active_languages = $sitepress->get_active_languages();

$all_products_taxonomies = array();
$all_products_taxonomies['product_shipping_class'] = 'Shipping Classes';
$all_products_taxonomies['product_cat'] = 'Product Categories';
$all_products_taxonomies['product_tag'] = 'Product Tags';

if(isset($_GET['tab'])){
    $current_tab = $_GET['tab'];
    if(!current_user_can('wpml_manage_woocommerce_multilingual')){
        $current_tab = 'products';
    }
}else{
    $current_tab = 'products';
}


?>

<div class="wrap">
    <div id="icon-wpml" class="icon32"><br /></div>
    <h1><?php _e('WooCommerce Multilingual', 'woocommerce-multilingual') ?></h1>
    <a class="nav-tab <?php echo $current_tab == 'products' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('admin.php?page=wpml-wcml'); ?>"><?php _e('Products', 'woocommerce-multilingual') ?></a>

    <?php if( current_user_can('wpml_operate_woocommerce_multilingual')): ?>
        <?php foreach($all_products_taxonomies as $tax_key => $tax): ?>
            <a class="js-tax-tab-<?php echo $tax_key ?> nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == $tax_key)?'nav-tab-active':''; ?>" href="<?php echo admin_url('admin.php?page=wpml-wcml&tab='.$tax_key); ?>" <?php if(!WCML_Terms::is_fully_translated($tax_key)): ?>title="<?php esc_attr_e('You have untranslated terms!', 'woocommerce-multilingual'); ?>"<?php endif;?>>
                <?php echo $tax ?>
                <?php if(!WCML_Terms::is_fully_translated($tax_key)): ?>
                    &nbsp;<i class="otgs-ico-warning"></i>
                <?php endif; ?>
            </a>

            <input type="hidden" id="wcml_update_term_translated_warnings_nonce" value="<?php echo wp_create_nonce('wcml_update_term_translated_warnings_nonce') ?>" />

        <?php endforeach; ?>
        <a class="nav-tab <?php echo $current_tab == 'product-attributes' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('admin.php?page=wpml-wcml&tab=product-attributes'); ?>"><?php _e('Product Attributes', 'woocommerce-multilingual') ?></a>
    <?php endif; ?>

    <?php if(current_user_can('wpml_manage_woocommerce_multilingual')): ?>
        <a class="nav-tab <?php echo $current_tab == 'settings' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo admin_url( 'admin.php?page=wpml-wcml&tab=settings' ); ?>"><?php _e( 'Settings', 'woocommerce-multilingual' ) ?></a>
        <a class="nav-tab <?php echo $current_tab == 'multi-currency' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('admin.php?page=wpml-wcml&tab=multi-currency'); ?>"><?php _e('Multi-currency', 'woocommerce-multilingual') ?></a>
        <a class="nav-tab <?php echo $current_tab == 'slugs' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('admin.php?page=wpml-wcml&tab=slugs'); ?>"><?php _e('Store URLs', 'woocommerce-multilingual') ?></a>
        <a class="nav-tab <?php echo $current_tab == 'status' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('admin.php?page=wpml-wcml&tab=status'); ?>"><?php _e('Status', 'woocommerce-multilingual') ?></a>
    <?php endif; ?>

	<div>
        <?php if(!isset($_GET['tab']) && current_user_can('wpml_manage_woocommerce_multilingual')): ?>
            <?php  include WCML_PLUGIN_PATH . '/menu/sub/products.php'; ?>
        <?php elseif( isset($_GET['tab']) && $_GET['tab'] == 'multi-currency' && current_user_can('wpml_manage_woocommerce_multilingual') ): ?>
            <?php include WCML_PLUGIN_PATH . '/menu/sub/multi-currency.php'; ?>
        <?php elseif(isset($all_products_taxonomies[$current_tab]) && current_user_can('wpml_operate_woocommerce_multilingual')): ?>
            <?php include WCML_PLUGIN_PATH . '/menu/sub/product-taxonomy.php'; ?>
        <?php elseif( isset($_GET['tab']) && $_GET['tab'] == 'product-attributes' && current_user_can('wpml_operate_woocommerce_multilingual')): ?>
            <?php include WCML_PLUGIN_PATH . '/menu/sub/product-attributes.php'; ?>
        <?php elseif( isset($_GET['tab']) && $_GET['tab'] == 'slugs' && current_user_can('wpml_operate_woocommerce_multilingual') ): ?>
            <?php include WCML_PLUGIN_PATH . '/menu/sub/slugs.php'; ?>
        <?php elseif( isset($_GET['tab']) && $_GET['tab'] == 'status' && current_user_can('wpml_operate_woocommerce_multilingual') ): ?>
            <?php include WCML_PLUGIN_PATH . '/menu/sub/status.php'; ?>
        <?php elseif((isset($_GET['tab']) && $_GET['tab'] == 'settings') || !current_user_can('wpml_manage_woocommerce_multilingual')): ?>
            <?php include WCML_PLUGIN_PATH . '/menu/sub/settings.php'; ?>
        <?php endif; ?>
    </div>

</div>

<?php if( !isset( $woocommerce_wpml->settings['rate-block'] ) ): ?>
    <div class="wrap wcml-wrap">
            <span>
                <?php echo sprintf(__('Thank you for using %s! You can express your love and support by %s rating our plugin and saying that %s for you.', 'woocommerce-multilingual'),'<strong>WooCommerce Multilingual</strong>', '<a href="https://wordpress.org/support/view/plugin-reviews/woocommerce-multilingual?filter=5#postform" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>', '<a href="https://wordpress.org/plugins/woocommerce-multilingual/?compatibility[version]='.$woocommerce_wpml->get_supported_wp_version().'&compatibility[topic_version]='.WCML_VERSION.'&compatibility[compatible]=1#compatibility" target="_blank">'.__('it works','woocommerce-multilingual').'</a>')?>
            </span>
            <span>
                <a class="wcml-dismiss-warning hide-rate-block" data-setting="rate-block" ><?php _e('Hide','woocommerce-multilingual') ?></a>
                <?php wp_nonce_field('wcml_settings', 'wcml_settings_nonce'); ?>
            </span>
    </div>
<?php endif; ?>
