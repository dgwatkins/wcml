<?php
//actions
global $woocommerce_wpml, $sitepress,$wpdb, $wp_taxonomies;

$current_language = $sitepress->get_current_language();
$active_languages = $sitepress->get_active_languages();

$all_products_taxonomies = array();
$products_and_variation_taxonomies = get_taxonomies(array('object_type'=>array('product','product_variation')),'objects');

//don't use get_taxonomies for product, because when one more post type registered for product taxonomy functions returned taxonomies only for product type
foreach($wp_taxonomies as $key=>$taxonomy){
    if(in_array('product',$taxonomy->object_type) && !array_key_exists($key,$products_and_variation_taxonomies)){
        $all_products_taxonomies[$key] = $taxonomy;
    }
}



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
    <h2><?php _e('WooCommerce Multilingual', 'woocommerce-multilingual') ?></h2>
    <a class="nav-tab <?php echo $current_tab == 'products' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('admin.php?page=wpml-wcml'); ?>"><?php _e('Products', 'woocommerce-multilingual') ?></a>

    <?php if( current_user_can('wpml_operate_woocommerce_multilingual')): ?>
        <?php foreach($all_products_taxonomies as $tax_key => $tax): if(!$sitepress->is_translated_taxonomy($tax_key) || $tax_key == 'product_type') continue; ?>
            <a class="js-tax-tab-<?php echo $tax_key ?> nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == $tax_key)?'nav-tab-active':''; ?>" href="<?php echo admin_url('admin.php?page=wpml-wcml&tab='.$tax_key); ?>" <?php if(!WCML_Terms::is_fully_translated($tax_key)): ?>title="<?php esc_attr_e('You have untranslated terms!', 'woocommerce-multilingual'); ?>"<?php endif;?>>
                <?php echo $tax->labels->name ?>
                <?php if(!WCML_Terms::is_fully_translated($tax_key)): ?>
                    &nbsp;<i class="otgs-ico-warning"></i>
                <?php endif; ?>
            </a>

            <input type="hidden" id="wcml_update_term_translated_warnings_nonce" value="<?php echo wp_create_nonce('wcml_update_term_translated_warnings_nonce') ?>" />

        <?php endforeach; ?>

        <?php foreach($products_and_variation_taxonomies as $tax_key => $tax): if(!$sitepress->is_translated_taxonomy($tax_key)) continue; ?>
            <a class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == $tax_key)?'nav-tab-active':''; ?>" href="<?php echo admin_url('admin.php?page=wpml-wcml&tab='.$tax_key); ?>" <?php if(!WCML_Terms::is_fully_translated($tax_key)): ?>title="<?php esc_attr_e('You have untranslated terms!', 'woocommerce-multilingual'); ?>"<?php endif;?>>
                <?php echo $tax->labels->name ?>
                <?php if(!WCML_Terms::is_fully_translated($tax_key)): ?>
                    &nbsp;<i class="otgs-ico-warning"></i>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>

    <?php endif; ?>

    <?php if(current_user_can('wpml_manage_woocommerce_multilingual')): ?>
        <a class="nav-tab <?php echo $current_tab == 'settings' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo admin_url( 'admin.php?page=wpml-wcml&tab=settings' ); ?>"><?php _e( 'Settings', 'woocommerce-multilingual' ) ?></a>
        <a class="nav-tab <?php echo $current_tab == 'multi-currency' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('admin.php?page=wpml-wcml&tab=multi-currency'); ?>"><?php _e('Multi-currency', 'woocommerce-multilingual') ?></a>
        <a class="nav-tab <?php echo $current_tab == 'slugs' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('admin.php?page=wpml-wcml&tab=slugs'); ?>"><?php _e('Store URLs', 'woocommerce-multilingual') ?></a>
        <a class="nav-tab <?php echo $current_tab == 'status' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url('admin.php?page=wpml-wcml&tab=status'); ?>"><?php _e('Status', 'woocommerce-multilingual') ?></a>
    <?php endif; ?>





	<div class="wcml-wrap">
        <?php if(!isset($_GET['tab']) && current_user_can('wpml_manage_woocommerce_multilingual')): ?>
            <?php  include WCML_PLUGIN_PATH . '/menu/sub/products.php'; ?>
        <?php elseif( isset($_GET['tab']) && $_GET['tab'] == 'multi-currency' && current_user_can('wpml_manage_woocommerce_multilingual') ): ?>
            <?php include WCML_PLUGIN_PATH . '/menu/sub/multi-currency.php'; ?>
        <?php elseif(isset($all_products_taxonomies[$current_tab]) || isset($products_and_variation_taxonomies[$current_tab]) && current_user_can('wpml_operate_woocommerce_multilingual')): ?>
            <?php include WCML_PLUGIN_PATH . '/menu/sub/product-taxonomy.php'; ?>
        <?php elseif( isset($_GET['tab']) && $_GET['tab'] == 'slugs' && current_user_can('wpml_operate_woocommerce_multilingual') ): ?>
            <?php include WCML_PLUGIN_PATH . '/menu/sub/slugs.php'; ?>
        <?php elseif( isset($_GET['tab']) && $_GET['tab'] == 'status' && current_user_can('wpml_operate_woocommerce_multilingual') ): ?>
            <?php include WCML_PLUGIN_PATH . '/menu/sub/status.php'; ?>
        <?php elseif((isset($_GET['tab']) && $_GET['tab'] == 'settings') || !current_user_can('wpml_manage_woocommerce_multilingual')): ?>
            <?php include WCML_PLUGIN_PATH . '/menu/sub/settings.php'; ?>
        <?php endif; ?>
    </div>
</div>

