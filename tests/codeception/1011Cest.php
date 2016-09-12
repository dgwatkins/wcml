<?php
//Check Product Translation Interface


class ElevenCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('check if product translation interface is working correct');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ///////////////////////////////////////////////////////////////
        // Check if product translation interface is working correct //
        ///////////////////////////////////////////////////////////////

        $I->amGoingTo('Check WCML produt interface');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=settings');

        $I->see('WooCommerce Multilingual');

        $I->see('Settings', '.nav-tab-active');

        $I->click('#wcml_trsl_interface_wcml');

        $I->click('Save changes');

        $I->wait(1);

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml');

        $I->see('Products', '.nav-tab-active');

        $I->seeElement('td.wpml-col-languages span i.otgs-ico-original');

        $I->seeElement('td.wpml-col-languages a');

        $I->click('td.wpml-col-languages a');

        $I->seeInCurrentUrl('/wp-admin/admin.php?page=wpml-translation-management%2Fmenu%2Ftranslations-queue.php');

        $I->see('Product translation:');

        $I->fillField('#wpml-translation-editor-wrapper #job_field_title input.translated_value','Δοκιμαστικό Προϊόν');

        $I->fillField('#wpml-translation-editor-wrapper #product_content_translated_editor textarea#product_content','Δοκιμαστικό Προϊόν');

        $I->checkOption('#wpml-translation-editor-wrapper input.js-translation-complete');

        $I->click('button.js-save-and-close');

        $I->wait(3);

        $I->see('WooCommerce Multilingual');

        $I->click('Test Product');

        $I->seeInCurrentUrl('/wp-admin/post.php?post=');

        $I->see('Edit Product');

        $I->waitForElement('.icl_toggle_show_translations',10);

        $value = $I->grabTextFrom('.icl_toggle_show_translations');

        if ($value=='show'){

            $I->click('show');
        }
        
        $I->click('#icl_translations_table a');

        $I->seeInCurrentUrl('/wp-admin/admin.php?page=wpml-translation-management%2Fmenu%2Ftranslations-queue.php');

        $I->see('Product translation:');

        $I->click('.wpml-translation-action-buttons div.alignleft button.cancel');

        $I->wait(3);

        /* Check with native screen */

        $I->amGoingTo('change translation method');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=settings');

        $I->see('WooCommerce Multilingual');

        $I->see('Settings', '.nav-tab-active');

        $I->click('#wcml_trsl_interface_native');

        $I->click('Save changes');

        $I->wait(1);

        $I->amOnPage('/wp-admin/edit.php?post_type=product');

        $I->see('Products');

        $I->click('Test Product');

        $I->seeInCurrentUrl('/wp-admin/post.php?post=');

        $I->see('Edit Product');

        $I->click('.icl_translations_table a');

        $I->see('Add New Product');

        $I->amGoingTo('Check if the mandatory fields are locked');

        $I->seeElement('.error');

        $I->seeElement('#woocommerce-product-data #product-type[disabled="disabled"]');

        $I->seeElement('#woocommerce-product-data label[for="product-type"] img.wcml_lock_icon');

        $I->seeElement('#woocommerce-product-data label[for="_virtual"] input#_virtual[disabled="disabled"]');

        $I->seeElement('#woocommerce-product-data label[for="_virtual"] img.wcml_lock_icon');

        $I->seeElement('#woocommerce-product-data label[for="_downloadable"] input#_downloadable[disabled="disabled"]');

        $I->seeElement('#woocommerce-product-data label[for="_downloadable"] img.wcml_lock_icon');

        $I->seeElement('._regular_price_field input#_regular_price[readonly="readonly"]');

        $I->seeElement('._regular_price_field img.wcml_lock_icon');

        $I->seeElement('._sale_price_field input#_sale_price[readonly="readonly"]');

        $I->seeElement('._sale_price_field img.wcml_lock_icon');

        $I->dontSeeElement('.wcml_custom_prices_options_block');

        $I->executeJS('window.scrollTo(0,500);');

        $I->click('Schedule');

        $I->seeElement('.sale_price_dates_fields input#_sale_price_dates_from[readonly="readonly"]');

        $I->seeElement('.sale_price_dates_fields img.wcml_lock_icon');

        $I->seeElement('.sale_price_dates_fields input#_sale_price_dates_to[readonly="readonly"]');

        $I->seeElement('.sale_price_dates_fields img.wcml_lock_icon:nth-of-type(2)');

        $I->click('Inventory');

        $I->seeElement('#inventory_product_data ._sku_field input#_sku[readonly="readonly"]');

        $I->seeElement('#inventory_product_data ._sku_field img.wcml_lock_icon');

        $I->seeElement('#inventory_product_data ._manage_stock_field input#_manage_stock[disabled="disabled"]');

        $I->seeElement('#inventory_product_data ._manage_stock_field img.wcml_lock_icon');

        $I->seeElement('#inventory_product_data ._stock_status_field select#_stock_status[disabled="disabled"]');

        $I->seeElement('#inventory_product_data ._stock_status_field img.wcml_lock_icon');

        $I->seeElement('#inventory_product_data ._sold_individually_field input#_sold_individually[disabled="disabled"]');

        $I->seeElement('#inventory_product_data ._sold_individually_field img.wcml_lock_icon');

        $I->click('Shipping');

        $I->seeElement('#shipping_product_data ._weight_field input#_weight[readonly="readonly"]');

        $I->seeElement('#shipping_product_data ._weight_field img.wcml_lock_icon');

        $I->seeElement('#shipping_product_data .dimensions_field input#product_length[readonly="readonly"]');

        $I->seeElement('#shipping_product_data .dimensions_field img.wcml_lock_icon');

        $I->seeElement('#shipping_product_data .dimensions_field input[name="_width"][readonly="readonly"]');

        $I->seeElement('#shipping_product_data .dimensions_field img.wcml_lock_icon:nth-of-type(2)');

        $I->seeElement('#shipping_product_data .dimensions_field input[name="_height"][readonly="readonly"]');

        $I->seeElement('#shipping_product_data .dimensions_field img.wcml_lock_icon:nth-of-type(3)');

        $I->dontSeeElement('#shipping_product_data .dimensions_field select#product_shipping_class[disabled="disabled"]');

        $I->click('Linked Products');

        $I->seeElement('#linked_product_data #s2id_upsell_ids.select2-container-disabled');

        $I->seeElement('#linked_product_data img.wcml_lock_icon:nth-of-type(1)');

        $I->seeElement('#linked_product_data #s2id_crosssell_ids.select2-container-disabled');

        $I->seeElement('#linked_product_data div.options_group p.form-field img.wcml_lock_icon');

        $I->seeElement('#linked_product_data #s2id_parent_id.select2-container-disabled');

        $I->seeElement('#linked_product_data .show_if_simple.show_if_external img.wcml_lock_icon');

        $I->click('Attributes','.attribute_options');

        $I->seeElement('#product_attributes select.attribute_taxonomy[disabled="disabled"]');

        $I->seeElement('#product_attributes img.wcml_lock_icon:nth-of-type(1)');

        $I->seeElement('#product_attributes button.add_attribute[disabled="disabled"]');

        $I->seeElement('#product_attributes img.wcml_lock_icon:nth-of-type(2)');

        $I->seeElement('#product_attributes button.save_attributes[disabled="disabled"]');

        $I->seeElement('#product_attributes div.toolbar img.wcml_lock_icon');

        $I->click('Advanced');

        $I->dontSeeElement('#advanced_product_data ._purchase_note_field textarea#_purchase_note[readonly="readonly"]');

        $I->seeElement('#advanced_product_data .menu_order_field input#menu_order[readonly="readonly"]');

        $I->seeElement('#advanced_product_data .menu_order_field img.wcml_lock_icon');

        $I->seeElement('#advanced_product_data .comment_status_field input#comment_status[disabled="disabled"]');

        $I->seeElement('#advanced_product_data .comment_status_field img.wcml_lock_icon');

        $I->amGoingTo('Check WCML produt interface');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=settings');

        $I->see('WooCommerce Multilingual');

        $I->see('Settings', '.nav-tab-active');

        $I->click('#wcml_trsl_interface_wcml');

        $I->click('Save changes');

        $I->wait(3);

    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
