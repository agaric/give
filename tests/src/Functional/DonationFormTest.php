<?php

namespace Drupal\Tests\comment_notify\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Comment notify Base Test class.
 *
 * @group give
 */
class DonationFormTest extends BrowserTestBase {

  /**
   * Admin User.
   *
   * @var \Drupal\user\Entity\User $adminUser
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'give',
    'give_record',
    'field',
    'user',
    'give_test',
  ];

  /**
   * Test that the config page is working.
   */
  protected function setUp() {
    parent::setUp();

    // Create and login administrative user.
    $this->adminUser = $this->drupalCreateUser(
      [
        'administer give',
        'create and edit give forms',
        'access give forms',
      ]
    );
  }

  /**
   * Test the settings page.
   */
  public function testGivePageAsAuthenticatedUser() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/give/tzedakah');
    $this->assertTrue($this->getSession()->getPage()->hasContent("Your email address"));
    $this->assertTrue($this->getSession()->getPage()->hasContent($this->adminUser->getEmail()));
    $this->assertTrue($this->getSession()->getPage()->hasContent("Your name"));
    $this->assertTrue($this->getSession()->getPage()->hasContent($this->adminUser->getUsername()));
    $this->assertTrue($this->getSession()->getPage()->findField('Amount to give'));
    $this->assertTrue($this->getSession()->getPage()->findField('Give this same donation every month'));
    $this->getSession()->getPage()->fillField('amount', 10);
    $this->submitForm([], 'edit-submit', 'give-donation-tzedakah-form');

    // Check that all the fields are present in the second step.
    $this->assertTrue($this->getSession()->getPage()->findField('method'));
    // The stripe_token field is hidden so we cannot use findField to check if
    // it exists.
    $this->getSession()->getPage()->hasContent('name="stripe_token"');
    $this->assertTrue($this->getSession()->getPage()->findField('stripe_number'));
    $this->assertTrue($this->getSession()->getPage()->findField('stripe_exp_month'));
    $this->assertTrue($this->getSession()->getPage()->findField('stripe_exp_year'));
    $this->assertTrue($this->getSession()->getPage()->findField('stripe_cvc'));
    $this->assertTrue($this->getSession()->getPage()->findField('Telephone number'));
    $this->assertTrue($this->getSession()->getPage()->findField('Further information'));

    $this->drupalLogout();
  }

}
