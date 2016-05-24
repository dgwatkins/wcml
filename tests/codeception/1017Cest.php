<?php
//Check Filtering in Product Tab


class SeventeenCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('check if filter options are working correct in Product Tab');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ///////////////////////////////////////////////////////////////
        // Check if filter options are working correct in Product Tab //
        ///////////////////////////////////////////////////////////////

        $I->amGoingTo('Create some dummy products');

        // Create 2nd Product in ENG

        $I->amOnPage('/wp-admin/post-new.php?post_type=product');

        $I->see('Add New Product');

        $I->fillField('Product name', 'Test Product 2');

        $I->click('#content-html');

        $I->fillField('#content', 'Test Product 2');

        $I->executeJS('window.scrollTo(0,500);');

        $I->fillField('_regular_price', '10');

        $I->checkOption('#product_catdiv #product_cat-all #product_catchecklist li:nth-child(1) input');

        $I->executeJS('window.scrollTo(0,-40);');

        $I->click('#publish');

        $I->waitForElement('div#message.updated.notice.notice-success.is-dismissible button.notice-dismiss','10');

        $I->wait(2);

        // Create 3rd Product in ENG

        $I->amOnPage('/wp-admin/post-new.php?post_type=product');

        $I->see('Add New Product');

        $I->fillField('Product name', 'Test Product 3');

        $I->click('#content-html');

        $I->fillField('#content', 'Test Product 3');

        $I->executeJS('window.scrollTo(0,500);');

        $I->fillField('_regular_price', '10');

        $I->checkOption('#product_catdiv #product_cat-all #product_catchecklist li:nth-child(2) input');

        $I->executeJS('window.scrollTo(0,-40);');

        $I->click('#publish');

        $I->wait(2);

        $I->waitForElement('div#message.updated.notice.notice-success.is-dismissible button.notice-dismiss','10');

        $I->wait(2);

        // Create 4th Product in ENG

        $I->amOnPage('/wp-admin/post-new.php?post_type=product');

        $I->see('Add New Product');

        $I->fillField('Product name', 'Test Product 4');

        $I->click('#content-html');

        $I->fillField('#content', 'Test Product 4');

        $I->executeJS('window.scrollTo(0,500);');

        $I->fillField('_regular_price', '10');

        $I->checkOption('#product_catdiv #product_cat-all #product_catchecklist li:nth-child(2) input');

        $I->executeJS('window.scrollTo(0,-40);');

        $I->click('#publish');

        $I->wait(2);

        $I->waitForElement('div#message.updated.notice.notice-success.is-dismissible button.notice-dismiss','10');

        $I->wait(2);

        // Create 1st Product in GR

        $I->amOnPage('/wp-admin/post-new.php?post_type=product&lang=el');

        $I->see('Add New Product');

        $I->fillField('Product name', 'Δοκιμαστικό Προϊόν 4');

        $I->click('#content-html');

        $I->fillField('#content', 'Δοκιμαστικό Προϊόν 4');

        $I->executeJS('window.scrollTo(0,500);');

        $I->fillField('_regular_price', '10');

        $I->executeJS('window.scrollTo(0,-40);');

        $I->wait(2);

        $I->click('#publish');

        $I->wait(2);

        $I->waitForElement('div#message.updated.notice.notice-success.is-dismissible button.notice-dismiss','10');

        $I->wait(2);

        // I am going and check filters in Product tab

        $I->amGoingTo('I am going and check filters in Product tab');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml');

        $I->see('WooCommerce Multilingual');

        // Check of all the filters are there

        $I->amGoingTo('Check of all the filters are there');

        $I->seeElement('.wcml_translation_status_lang');

        $I->seeElement('.wcml_product_category');

        $I->seeElement('.wcml_translation_status');

        $I->seeElement('.wcml_product_status');

        $I->seeElement('.wcml_search');

        $I->seeElement('input.wcml_product_name[type="search"]');

        $I->seeElement('.wcml_search_by_title[value="search"]');

        $I->wait(1);

        // Check Language Filter

        $I->amGoingTo('Check Language Filter');

        $I->selectOption('.wcml_translation_status_lang','Greek');
        
        $I->click('.wcml_search');

        $I->wait(1);

        $I->seeInCurrentUrl('page=wpml-wcml&tab=products&cat=0&trst=all&st=all&slang=el');

        $I->addNameProducts();

        $I->seeElement('.wpml-list-table tr td.column-thumb[data-from-title="Δοκιμαστικό Προϊόν 4"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-title[data-from-title="Δοκιμαστικό Προϊόν 4"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-languages[data-from-title="Δοκιμαστικό Προϊόν 4"]');

        $I->seeElement('.wpml-list-table tr td.column-categories[data-from-title="Δοκιμαστικό Προϊόν 4"]');

        $I->seeElement('.wpml-list-table tr td.column-product_type[data-from-title="Δοκιμαστικό Προϊόν 4"]');

        $I->seeElement('.wpml-list-table tr td.column-date[data-from-title="Δοκιμαστικό Προϊόν 4"]');

        $I->amGoingTo('Check Language Filter');

        $I->selectOption('.wcml_translation_status_lang','English');

        $I->click('.wcml_search');

        $I->wait(1);

        $I->seeInCurrentUrl('page=wpml-wcml&tab=products&cat=0&trst=all&st=all&slang=en');

        $I->addNameProducts();

        //Test Product 2

        $I->seeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-title[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-languages[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.column-categories[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.column-product_type[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.column-date[data-from-title="Test Product 2"]');

        //Test Product 3

        $I->seeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 3"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-title[data-from-title="Test Product 3"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-languages[data-from-title="Test Product 3"]');

        $I->seeElement('.wpml-list-table tr td.column-categories[data-from-title="Test Product 3"]');

        $I->seeElement('.wpml-list-table tr td.column-product_type[data-from-title="Test Product 3"]');

        $I->seeElement('.wpml-list-table tr td.column-date[data-from-title="Test Product 3"]');

        //Test Product 3

        $I->seeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-title[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-languages[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.column-categories[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.column-product_type[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.column-date[data-from-title="Test Product 4"]');
        
        // Check Category Filter

        $I->amGoingTo('Check Category Filter');

        $I->selectOption('.wcml_product_category','Shoes');

        $I->click('.wcml_search');

        $I->wait(1);

        $I->seeInCurrentUrl('page=wpml-wcml&tab=products&cat=24&trst=all&st=all&slang=en');

        $I->addNameProducts();

        //Test Product 3

        $I->seeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 3"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-title[data-from-title="Test Product 3"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-languages[data-from-title="Test Product 3"]');

        $I->seeElement('.wpml-list-table tr td.column-categories[data-from-title="Test Product 3"]');

        $I->seeElement('.wpml-list-table tr td.column-product_type[data-from-title="Test Product 3"]');

        $I->seeElement('.wpml-list-table tr td.column-date[data-from-title="Test Product 3"]');

    }

    public function _after(AcceptanceTester $I)
    {

    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		
    }
}
