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

        $I->wait(2);

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

        $I->wait(2);

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

        $I->wait(2);

        $I->click('#publish');

        $I->wait(2);

        $I->waitForElement('div#message.updated.notice.notice-success.is-dismissible button.notice-dismiss','10');

        $I->wait(2);

        // Create 1st Product in GR

        $I->amOnPage('/wp-admin/post-new.php?post_type=product&lang=el');

        $I->see('Add New Product');

        $I->fillField('Product name', 'Δοκιμαστικό Προϊόν 5');

        $I->click('#content-html');

        $I->fillField('#content', 'Δοκιμαστικό Προϊόν 5');

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

        // Δοκιμαστικό Προϊον 5

        $I->seeElement('.wpml-list-table tr td.column-thumb[data-from-title="Δοκιμαστικό Προϊόν 5"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-title[data-from-title="Δοκιμαστικό Προϊόν 5"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-languages[data-from-title="Δοκιμαστικό Προϊόν 5"]');

        $I->seeElement('.wpml-list-table tr td.column-categories[data-from-title="Δοκιμαστικό Προϊόν 5"]');

        $I->seeElement('.wpml-list-table tr td.column-product_type[data-from-title="Δοκιμαστικό Προϊόν 5"]');

        $I->seeElement('.wpml-list-table tr td.column-date[data-from-title="Δοκιμαστικό Προϊόν 5"]');

        //Don't see Test Product 2 & 3 & 4

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 2"]');

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 3"]');

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 4"]');
        
        //Change language filter
        
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

        //Test Product 4

        $I->seeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-title[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-languages[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.column-categories[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.column-product_type[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.column-date[data-from-title="Test Product 4"]');

        //Don't see Δοκιμαστικό Προϊόν 4

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Δοκιμαστικό Προϊόν 4"]');
        
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

        //Test Product 4

        $I->seeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-title[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-languages[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.column-categories[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.column-product_type[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.column-date[data-from-title="Test Product 4"]');
        
        //Don't see Δοκιμαστικό Προϊόν 5 & Test Product 4

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 2"]');

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Δοκιμαστικό Προϊόν 5"]');

        // Reset Filter

        $I->click('.wcml_reset_search');

        $I->wait(2);

        // Check Translation Statuses

        $I->addNameProducts();

        $I->amGoingTo('Change the Translation Statuses of the products');

        // Complete Status

        $I->amGoingTo('Create a complete Status');

        $I->click('.wpml-list-table tr td.wpml-col-languages[data-from-title="Test Product 2"] a');

        $I->seeInCurrentUrl('/wp-admin/admin.php?page=wpml-translation-management%2Fmenu%2Ftranslations-queue.php');

        $I->see('Product translation:');

        $I->fillField('#wpml-translation-editor-wrapper #job_field_title input.translated_value','Δοκιμαστικό Προϊόν 2');

        $I->fillField('#wpml-translation-editor-wrapper #product_content_translated_editor textarea#product_content','Δοκιμαστικό Προϊόν 2');

        $I->checkOption('#wpml-translation-editor-wrapper input.js-translation-complete');

        $I->click('button.js-save-and-close');

        $I->wait(3);

        $I->see('WooCommerce Multilingual');


        // In progress

        $I->addNameProducts();

        $I->amGoingTo('Create an in progress Status');

        $I->click('.wpml-list-table tr td.wpml-col-languages[data-from-title="Test Product 3"] a');

        $I->seeInCurrentUrl('/wp-admin/admin.php?page=wpml-translation-management%2Fmenu%2Ftranslations-queue.php');

        $I->see('Product translation:');

        $I->fillField('#wpml-translation-editor-wrapper #job_field_title input.translated_value','Δοκιμαστικό Προϊόν 3');

        $I->fillField('#wpml-translation-editor-wrapper #product_content_translated_editor textarea#product_content','Δοκιμαστικό Προϊόν 3');

        $I->click('button.js-save-and-close');

        $I->wait(3);

        $I->see('WooCommerce Multilingual');

        // Not Translated

        $I->selectOption('.wcml_translation_status','Not translated or needs updating');

        $I->click('.wcml_search');

        $I->wait(1);

        $I->seeInCurrentUrl('page=wpml-wcml&tab=products&cat=0&trst=not&st=all&slang=all');

        $I->addNameProducts();

        //Test Product 4

        $I->seeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-title[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-languages[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.column-categories[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.column-product_type[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.column-date[data-from-title="Test Product 4"]');

        // Δοκιμαστικό Προϊον 5

        $I->seeElement('.wpml-list-table tr td.column-thumb[data-from-title="Δοκιμαστικό Προϊόν 5"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-title[data-from-title="Δοκιμαστικό Προϊόν 5"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-languages[data-from-title="Δοκιμαστικό Προϊόν 5"]');

        $I->seeElement('.wpml-list-table tr td.column-categories[data-from-title="Δοκιμαστικό Προϊόν 5"]');

        $I->seeElement('.wpml-list-table tr td.column-product_type[data-from-title="Δοκιμαστικό Προϊόν 5"]');

        $I->seeElement('.wpml-list-table tr td.column-date[data-from-title="Δοκιμαστικό Προϊόν 5"]');

        // Don't see Test Product 2 & 3

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 2"]');

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 3"]');

        // In progress

        $I->selectOption('.wcml_translation_status','Translation in progress');

        $I->click('.wcml_search');

        $I->wait(1);

        $I->seeInCurrentUrl('page=wpml-wcml&tab=products&cat=0&trst=in_progress&st=all&slang=all');

        $I->addNameProducts();

        //Test Product 3

        $I->seeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 3"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-title[data-from-title="Test Product 3"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-languages[data-from-title="Test Product 3"]');

        $I->seeElement('.wpml-list-table tr td.column-categories[data-from-title="Test Product 3"]');

        $I->seeElement('.wpml-list-table tr td.column-product_type[data-from-title="Test Product 3"]');

        $I->seeElement('.wpml-list-table tr td.column-date[data-from-title="Test Product 3"]');

        // Don't see Test Product 2 & 4 and Δοκιμαστικό Προϊόν 5

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 2"]');

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 4"]');

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Δοκιμαστικό Προϊόν 5"]');

        // Completed

        $I->selectOption('.wcml_translation_status','Translation complete');

        $I->click('.wcml_search');

        $I->wait(1);

        $I->seeInCurrentUrl('page=wpml-wcml&tab=products&cat=0&trst=complete&st=all&slang=all');

        $I->addNameProducts();

        //Test Product 2

        $I->seeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-title[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-languages[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.column-categories[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.column-product_type[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.column-date[data-from-title="Test Product 2"]');

        //Don't see Test Product 3 & 4 and Δοκιμαστικό Προϊόν 5

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 3"]');

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 4"]');

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Δοκιμαστικό Προϊόν 5"]');

        // Needs Updating

        $I->amGoingTo('Edit original product');

        $I->click('Test Product 2');

        $I->waitForElementVisible('#wp-content-editor-container',10);

        $I->click('#content-html');

        $I->fillField('#content', 'Test Product 2 test');

        $I->click('a.edit-post-status span[aria-hidden="true"]');

        $I->selectOption('#post_status','Draft');

        $I->click('.save-post-status');

        $I->click('#publish');

        $I->wait(2);

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml');

        // Need Update

        $I->selectOption('.wcml_translation_status','Needs updating');

        $I->click('.wcml_search');

        $I->wait(1);

        $I->seeInCurrentUrl('page=wpml-wcml&tab=products&cat=0&trst=need_update&st=all&slang=all');

        $I->addNameProducts();

        //Test Product 2

        $I->seeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-title[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-languages[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.column-categories[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.column-product_type[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.column-date[data-from-title="Test Product 2"]');

        //Don't see Test Product 3 & 4 and Δοκιμαστικό Προϊόν 5

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 3"]');

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 4"]');

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Δοκιμαστικό Προϊόν 5"]');

        // Check Status Filter - Draft

        $I->amGoingTo('Check status filter');

        $I->click('.wcml_reset_search');

        $I->selectOption('.wcml_product_status','Draft');

        $I->click('.wcml_search');

        $I->wait(1);

        $I->seeInCurrentUrl('page=wpml-wcml&tab=products&cat=0&trst=all&st=draft&slang=all');

        $I->addNameProducts();

        //Test Product 2

        $I->seeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-title[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-languages[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.column-categories[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.column-product_type[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.column-date[data-from-title="Test Product 2"]');

        //Don't see Test Product 3 & 4 and Δοκιμαστικό Προϊόν 5

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 3"]');

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 4"]');

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Δοκιμαστικό Προϊόν 5"]');

        // Change Product from Drat to Pending Review

        $I->amGoingTo('Edit original product and set it to Pending Review');

        $I->click('Test Product 2');

        $I->waitForElementVisible('#wp-content-editor-container',10);

        $I->click('#content-html');

        $I->fillField('#content', 'Test Product 2');

        $I->click('a.edit-post-status span[aria-hidden="true"]');

        $I->selectOption('#post_status','Pending Review');

        $I->click('.save-post-status');

        $I->click('#save-post');

        $I->wait(2);

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml');

        // Check Status Filter - Pending Review

        $I->amGoingTo('Check status filter');

        $I->selectOption('.wcml_product_status','Pending');

        $I->click('.wcml_search');

        $I->wait(1);

        $I->seeInCurrentUrl('page=wpml-wcml&tab=products&cat=0&trst=all&st=pending&slang=all');

        $I->addNameProducts();

        //Test Product 2

        $I->seeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-title[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-languages[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.column-categories[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.column-product_type[data-from-title="Test Product 2"]');

        $I->seeElement('.wpml-list-table tr td.column-date[data-from-title="Test Product 2"]');

        //Don't see Test Product 3 & 4 and Δοκιμαστικό Προϊόν 5

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 3"]');

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 4"]');

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Δοκιμαστικό Προϊόν 5"]');

        //Publish again Test Product 2

        $I->click('Test Product 2');

        $I->waitForElementVisible('#wp-content-editor-container',10);

        $I->click('#publish');

        // Test the Search Input

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml');

        $I->amGoingTo('Check Search Input');

        $I->fillField('input.wcml_product_name', 'Test Product 4');

        $I->click('.wcml_search_by_title');

        $I->seeInCurrentUrl('page=wpml-wcml&tab=products&s=Test+Product+4');

        $I->addNameProducts();

        //Test Product 4

        $I->seeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-title[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.wpml-col-languages[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.column-categories[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.column-product_type[data-from-title="Test Product 4"]');

        $I->seeElement('.wpml-list-table tr td.column-date[data-from-title="Test Product 4"]');

        //Don't see Test Product 3 & 4 and Δοκιμαστικό Προϊόν 5

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 2"]');

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Test Product 3"]');

        $I->dontSeeElement('.wpml-list-table tr td.column-thumb[data-from-title="Δοκιμαστικό Προϊόν 5"]');

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
