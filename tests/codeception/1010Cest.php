<?php
//Check Taxomonies Warnings


class TenCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('check if taxomonies warnings are correct');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ///////////////////////////////////////////////////
        // Check if taxonomies warnings are correct      //
        //////////////////////////////////////////////////

        $I->amGoingTo('Create 1 Shipping Class');

        $I->amOnPage('/wp-admin/admin.php?page=wc-settings&tab=shipping&section=classes');

        $I->waitForElementVisible('.wc-shipping-class-add',10);

        $I->click('Add Shipping Class');

        $I->fillField('.wc-shipping-class-name div.edit input[data-attribute="name"]','Shippo Classy');

        $I->wait(2);

        $I->click('Save Shipping Classes');

        $I->wait(5);

        $I->waitForElementVisible('.wc-shipping-class-name .view',5);

        $I->amGoingTo('Create 2 Product Categories');

        $I->amOnPage('/wp-admin/edit-tags.php?taxonomy=product_cat&post_type=product');

        $I->see('Product Categories');

        $I->fillField('tag-name','Shoes');

        $I->click('Add New Product Category');

        $I->wait(2);

        $I->fillField('tag-name','Bags');

        $I->click('Add New Product Category');

        $I->wait(2);

        $I->amGoingTo('Create 3 Product Tags');

        $I->amOnPage('/wp-admin/edit-tags.php?taxonomy=product_tag&post_type=product');

        $I->see('Product Tags');

        $I->fillField('tag-name','Nike');

        $I->click('Add New Product Tag');

        $I->wait(2);

        $I->fillField('tag-name','Adidas');

        $I->click('Add New Product Tag');

        $I->wait(2);

        $I->fillField('tag-name','Reebok');

        $I->click('Add New Product Tag');

        $I->wait(2);

        $I->amGoingTo('to  check warnings in WCML');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml');

        $I->expectTo('see that we have warnings in tabs');

        $I->seeElement('.js-tax-tab-product_cat', ['title' => 'You have untranslated terms!']);

        $I->seeElement('.js-tax-tab-product_cat>.otgs-ico-warning');

        $I->wait(2);

        $I->seeElement('.js-tax-tab-product_tag', ['title' => 'You have untranslated terms!']);

        $I->seeElement('.js-tax-tab-product_tag>.otgs-ico-warning');

        $I->wait(2);

        $I->seeElement('.js-tax-tab-product_shipping_class', ['title' => 'You have untranslated terms!']);

        $I->seeElement('.js-tax-tab-product_shipping_class>.otgs-ico-warning');

        $I->expectTo('see if in wcml status we have correct warnings');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=status');

        /* Check Categories Section */

        $I->amGoingTo('check product categories section');

        $I->seeElement('.js-tax-translation-product_cat>.otgs-ico-warning');

        $I->see('2 Product Categories are missing translations.', '.js-tax-translation-product_cat');

        $I->seeElement('.js-tax-translation-product_cat>.button-secondary');

        $I->seeLink('Translate Product Categories', '/wp-admin/admin.php?page=wpml-wcml&tab=product_cat');

        $I->click('Translate Product Categories');

        $I->seeInCurrentUrl('page=wpml-wcml&tab=product_cat');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=status');

        $I->seeLink('Exclude from translation', '#ignore-product_cat');

        $I->click('.ignore-product_cat');

        $I->wait(3);

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=status');

        $I->wait(2);

        $I->expectTo('see if category status change to not require translation');

        $I->seeElement('.js-tax-translation-product_cat>.otgs-ico-ok');

        $I->see('Product Categories do not require translation.', '.js-tax-translation-product_cat');

        $I->dontseeElement('.js-tax-translation-product_cat>.button-secondary');

        $I->click('.unignore-product_cat');

        $I->wait(3);

        $I->see('2 Product Categories are missing translations.', '.js-tax-translation-product_cat');

        $I->seeElement('.js-tax-translation-product_cat>.button-secondary');

        $I->seeLink('Translate Product Categories', '/wp-admin/admin.php?page=wpml-wcml&tab=product_cat');

        /*  End Check Categories Section */

        /* Check Tags Section */

        $I->seeElement('.js-tax-translation-product_tag>.otgs-ico-warning');

        $I->see('3 Product Tags are missing translations.', '.js-tax-translation-product_tag');

        $I->seeElement('.js-tax-translation-product_tag>.button-secondary');

        $I->seeLink('Translate Product Tags', '/wp-admin/admin.php?page=wpml-wcml&tab=product_tag');

        $I->click('Translate Product Tags');

        $I->seeInCurrentUrl('page=wpml-wcml&tab=product_tag');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=status');

        $I->seeLink('Exclude from translation', '#ignore-product_tag');

        $I->click('.ignore-product_tag');

        $I->wait(3);

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=status');

        $I->expectTo('see if tag status change to not require translation');

        $I->seeElement('.js-tax-translation-product_tag>.otgs-ico-ok');

        $I->see('Product Tags do not require translation.', '.js-tax-translation-product_tag');

        $I->dontseeElement('.js-tax-translation-product_tag>.button-secondary');

        $I->click('.unignore-product_tag');

        $I->wait(3);

        $I->see('3 Product Tags are missing translations.', '.js-tax-translation-product_tag');

        $I->seeElement('.js-tax-translation-product_tag>.button-secondary');

        $I->seeLink('Translate Product Tags', '/wp-admin/admin.php?page=wpml-wcml&tab=product_tag');

        /*  End Check Tags Section */

        /* Check Tags Section */

        $I->seeElement('.js-tax-translation-product_shipping_class>.otgs-ico-warning');

        $I->see('1 Shipping Classes are missing translations.', '.js-tax-translation-product_shipping_class');

        $I->seeElement('.js-tax-translation-product_shipping_class>.button-secondary');

        $I->seeLink('Translate Shipping Classes', '/wp-admin/admin.php?page=wpml-wcml&tab=product_shipping_class');

        $I->click('Translate Shipping Classes');

        $I->seeInCurrentUrl('page=wpml-wcml&tab=product_shipping_class');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=status');

        $I->seeLink('Exclude from translation', '#ignore-product_shipping_class');

        $I->click('.ignore-product_shipping_class');

        $I->wait(3);

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=status');

        $I->expectTo('see if shipping class status change to not require translation');

        $I->seeElement('.js-tax-translation-product_shipping_class>.otgs-ico-ok');

        $I->see('Shipping Classes do not require translation.', '.js-tax-translation-product_shipping_class');

        $I->dontseeElement('.js-tax-translation-product_shipping_class>.button-secondary');

        $I->click('.unignore-product_shipping_class');

        $I->wait(3);

        $I->see('1 Shipping Classes are missing translations.', '.js-tax-translation-product_shipping_class');

        $I->seeElement('.js-tax-translation-product_shipping_class>.button-secondary');

        $I->seeLink('Translate Shipping Classes', '/wp-admin/admin.php?page=wpml-wcml&tab=product_shipping_class');

        /*  End Check Shipping Classes Section */

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
