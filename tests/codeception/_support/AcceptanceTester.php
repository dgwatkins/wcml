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

    /** Create WC Simple Product Action **
     *  Please when you run this function :
     *  Select Name of Product and How many Number do you wish to create
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
    
}
