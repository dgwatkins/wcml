<?php
//Check Product Translation Editor


class NineteenCest
{
    public function _before(AcceptanceTester $I)
    {

        $I->wantTo('Check if columns are correct in Product Tab');

        // Login Procedure
        $I->wp_login('admin', '123456');

        $I->amGoingTo('Create a specific dummy product');

        $I->simpleProducts('Test for this Test',1);

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml');
        
        $I->see('WooCommerce Multilingual');

        $I->addNameProducts();

        $I->click('.wpml-list-table tr td.wpml-col-languages[data-from-title="Test for this Test1"] a[title="Greek: Add translation"]');

        // Check if fields are locked

        $I->seeElement('#icl_tm_editor #job_field_title input.original_value[readonly=""]');

        $I->seeElement('#icl_tm_editor #job_field_slug input.original_value[readonly=""]');

        $I->seeElement('#icl_tm_editor #job_field_product_content #wp-product_content_original-editor-container textarea[readonly=""]');

        $I->seeElement('#icl_tm_editor #product_excerpt_original[readonly=""]');

        $I->seeElement('#icl_tm_editor #job_field__purchase_note textarea[readonly=""]');

        $I->seeElement('.wpml-translation-action-buttons button.js-save-and-close[disabled=""]');

        $I->seeElement('.wpml-translation-action-buttons button.js-save[disabled=""]');

        // Check if slug is generated automatically

        $I->fillField('.translated_value[name="fields[title][data]"]','Δοκιμή');

        $I->click('#product_content');

        $I->fillField('#product_content','Δοκιμαστικό Κείμενο');

        $I->see('δοκιμή','.translated_value[name="fields[slug][data]"]');

    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
