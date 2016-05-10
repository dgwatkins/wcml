<?php
//Check Product Path Synchronization


class ThirteenCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('check if product path synchronization is working correct');

        // Login Procedure
        $I->wp_login('admin', '123456');

        ///////////////////////////////////////////////////////////////
        // Check if Path Sync  is working correct //
        ///////////////////////////////////////////////////////////////

        $I->amGoingTo('Check Translation Global File Sync');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=settings');

        $I->see('WooCommerce Multilingual');

        $I->see('Settings', '.nav-tab-active');

        $I->click('#wcml_file_path_sync_self');

        $I->click('Save changes');

        $I->wait(2);

        $I->amOnPage('/wp-admin/edit.php?post_type=product');

        $I->see('Products');

        $I->click('Test Product');

        $I->see('Edit Product');

        $I->checkOption('#_downloadable');

        $I->executeJS('window.scrollTo(0,500);');

        $I->click('.downloadable_files .insert');

        $I->fillField('.file_name input.input_text', 'Downloadable Product');

        $I->fillField('.file_url input.input_text', 'http://google.com');

        $I->click('#publish');

        $I->wait(2);

        $I->click('.icl_translations_table a');

        $I->seeInCurrentUrl('/wp-admin/admin.php?page=wpml-translation-management%2Fmenu%2Ftranslations-queue.php');

        $I->see('Product translation:');

        $I->see('Download Files');

        $I->fillField('[id^="job_field_file-name"] input.translated_value','Προϊόν Κατεβάσματος');

        $I->fillField('[id^="job_field_file-url"] input.translated_value','http://google.gr');

        $I->click('button.js-save-and-close');

        $I->wait(1);

        $I->amGoingTo('Check Disable Translation File Sync');

        $I->amOnPage('/wp-admin/admin.php?page=wpml-wcml&tab=settings');

        $I->see('WooCommerce Multilingual');

        $I->see('Settings', '.nav-tab-active');

        $I->click('#wcml_file_path_sync_auto');

        $I->click('Save changes');

        $I->wait(2);

        $I->amOnPage('/wp-admin/edit.php?post_type=product');

        $I->see('Products');

        $I->click('Test Product');

        $I->see('Edit Product');

        $I->click('.icl_translations_table a');

        $I->seeInCurrentUrl('/wp-admin/admin.php?page=wpml-translation-management%2Fmenu%2Ftranslations-queue.php');

        $I->see('Product translation:');

        $I->dontSee('Download Files');

        $I->amGoingTo('Check Translation Local File Sync');

        $I->amOnPage('/wp-admin/edit.php?post_type=product');

        $I->see('Products');

        $I->click('Test Product');

        $I->see('Edit Product');

        $I->executeJS('window.scrollTo(0,500);');

        $I->checkOption('.wcml-downloadable-options input#wcml_file_path_option');

        $I->click('.wcml-downloadable-options input#wcml_file_path_sync_self');

        $I->click('#publish');

        $I->wait(2);

        $I->click('.icl_translations_table a');

        $I->see('Download Files');

        $I->amOnPage('/wp-admin/edit.php?post_type=product');

        $I->see('Products');

        $I->click('Test Product');

        $I->see('Edit Product');

        $I->executeJS('window.scrollTo(0,500);');

        $I->uncheckOption('.wcml-downloadable-options input#wcml_file_path_option');

        $I->click('#publish');

        $I->wait(2);

        $I->click('.icl_translations_table a');

        $I->dontSee('Download Files');

        $I->amOnPage('/wp-admin/edit.php?post_type=product');

        $I->see('Products');

        $I->click('Test Product');

        $I->see('Edit Product');

        $I->uncheckOption('#_downloadable');

        $I->click('#publish');

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
