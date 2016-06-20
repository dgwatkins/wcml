<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * Define custom actions here
     */

    /** WP Login Function */
    public function wp_login($name, $password)
    {
        $I = $this;

        // Login Procedure
        $I->amGoingTo('Login to the Backend');

        $I->amOnPage('/wp-admin/');

        $I->fillField('#user_login', $name);
        $I->fillField('#user_pass', $password);

        $I->click('Log In');

        $I->see('Dashboard');
    }

    /** Activate WP Plugins Action */
    public function activatePlugin( $pluginSlug ) {
        //$this->click( "#{$pluginSlug} span.activate > a:first-of-type" );
        $this->click( "table.plugins tr[data-slug='{$pluginSlug}'] span.activate > a:first-of-type" );
    }

    /** Deactivate WP Plugins Action */
    public function deactivatePlugin( $pluginSlug ) {
        //$this->click( "#{$pluginSlug} span.deactivate > a:first-of-type" );
        $this->click( "table.plugins tr[data-slug='{$pluginSlug}'] span.deactivate > a:first-of-type" );
    }

    /** Check if Activate WP Plugin Action */
    public function seeActivatePlugin( $pluginSlug ) {
        $this->waitForElementVisible( "table.plugins tr[data-slug='{$pluginSlug}'] span.activate",10);
        $this->seeElement( "table.plugins tr[data-slug='{$pluginSlug}'] span.activate" );
    }

    /** Check if Deactivate WP Plugin Action */
    public function seeDeactivatePlugin( $pluginSlug ) {
        $this->waitForElementVisible( "table.plugins tr[data-slug='{$pluginSlug}'] span.deactivate",10);
        $this->seeElement( "table.plugins tr[data-slug='{$pluginSlug}'] span.deactivate" );
    }

    /** Add Data Name in Product List WCML Action */
    public function addNameProducts () {
        $this->executeJS('return jQuery(".wpml-list-table td.wpml-col-title").each( function() {
        var title = jQuery.trim( jQuery(this).find("a").html() );
        var tr = jQuery(this).parent();
        tr.find("td").each( function(){
            jQuery(this).attr("data-from-title", title)
        })

    });
        '
        );

    }

    /**
	 * Saves permalinks
	 */
    public function savePermalinks() {
        $this->amOnPage( '/wp-admin/options-permalink.php' );
        $this->click( '#submit' );
    }

    /** Create WC Simple Product Action **
     *  Please when you run this function :
     *  Select Name of Product and How many Number you wish to create
     */
    public function simpleProducts ($name, $number) {
        for ($x = 1; $x <= $number; $x++) {

            $this->amOnPage('/wp-admin/post-new.php?post_type=product');

            $this->see('Add New Product');

            $this->fillField('Product name', $name.$x);

            $this->click('#content-html');

            $this->fillField('#content', $name.$x);

            $this->fillField('_regular_price', '10');

            $this->wait(2);

            $this->click('#publish');

            $this->waitForElement('div#message.updated.notice.notice-success.is-dismissible', '10');

            $this->wait(2);
        }
    }

    /** Create WC Product Category Action **
     *  Please when you run this function :
     *  Select Name of Category you wish to create
     */
    public function createCategory ($name) {

        $this->amGoingTo('Create a Product Category');

        $this->amOnPage('/wp-admin/edit-tags.php?taxonomy=product_cat&post_type=product');

        $this->see('Product Categories');

        $this->fillField('tag-name', $name);

        $this->click('Add New Product Category');

        $this->wait(2);
    }

    /** Create WC Product Attribute Action **
     *  Please when you run this function :
     *  Select Name of Attribute you wish to create
     *  Selecte 1,2 or 3 Term Names for the Attribute
     */
    public function createAttribute ($name,$attr1,$attr2,$attr3) {

        $this->amGoingTo('Creat a Global Attribute');

        $this->amOnPage('/wp-admin/edit.php?post_type=product&page=product_attributes');

        $this->see('Attributes');

        $this->fillField('attribute_label', $name);

        $this->click('Add Attribute');

        $this->wait(2);

        // Add Terms into Attribute

        $slug = strtolower($name);

        $this->amOnPage('/wp-admin/edit-tags.php?taxonomy=pa_'.$slug.'&post_type=product');

        $this->see($name);

        //Attribute 1

        $this->fillField('tag-name', $attr1);

        $this->click('Add New '.$name);

        $this->wait(2);

        //Attribute 2

        $this->fillField('tag-name', $attr2);

        $this->click('Add New '.$name);

        $this->wait(2);

        //Attribute 3

        $this->fillField('tag-name', $attr3);

        $this->click('Add New '.$name);

        $this->wait(2);

    }

    /** Create WC Variable Product with Global Attribute Action **
     *  Please when you run this function :
     *  Select Name of Attribute you wish to create
     *  Selecte 1,2 or 3 Term Names for the Attribute
     */
    public function createVarProduct($name,$attribute){
        $this->amOnPage('/wp-admin/post-new.php?post_type=product');

        $this->see('Add New Product');

        $this->fillField('Product name', $name);

        $this->click('#content-html');

        $this->fillField('#content', $name);

        $this->selectOption('#product-type','Variable product');

        $this->executeJS('window.scrollTo(0,500);');

        $this->click('.attribute_tab');

        $this->selectOption('.attribute_taxonomy', $attribute);

        $this->click('.add_attribute');

        $this->waitForElementVisible('.woocommerce_attribute',10);

        $this->wait(3);

        $this->click('.select_all_attributes');

        $this->checkOption('input.checkbox[name="attribute_variation[0]"]');

        $this->click('Save attributes');

        $this->waitForElementNotVisible('.blockUI');

        $this->waitForElementVisible('.variations_tab',15);

        $this->wait(10);

        $this->click('.variations_tab');

        $this->wait(10);

        $this->selectOption('.variation_actions','Create variations from all attributes');

        $this->click('.do_variation_action');

        $this->acceptPopup();

        // Not Finish Should Add price

        $this->wait(2);
    }
    
}
