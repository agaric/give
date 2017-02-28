<?php

namespace Drupal\Tests\give\Functional;

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
    'views',
    'give',
    'give_test',
    'field',
    'user',
  ];

  /**
   * Test that the config page is working.
   */
  protected function setUp() {
    parent::setUp();

    $config = \Drupal::service('config.factory')->getEditable('give.settings');
    $config->set('stripe_publishable_key', $this->randomString())
      ->set('stripe_secret_key', $this->randomString())
      ->save();

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
  public function testCheckPaymentMethodAsAuthenticatedUser() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/give/tzedakah');
    $this->assertTrue($this->getSession()->getPage()->hasContent("Your email address"));
    $this->assertTrue($this->getSession()->getPage()->hasContent($this->adminUser->getEmail()));
    $this->assertTrue($this->getSession()->getPage()->hasContent("Your name"));
    $this->assertTrue($this->getSession()->getPage()->hasContent($this->adminUser->getUsername()));
    $this->assertTrue($this->getSession()->getPage()->findField('Amount to give'));
    $this->assertTrue($this->getSession()->getPage()->findField('edit-recurring-1'));
    $this->getSession()->getPage()->fillField('amount', 10);
    $this->submitForm([], 'edit-submit');

    // Check that all the fields are present in the second step.
    $this->assertTrue($this->getSession()->getPage()->findField('method'));
    $this->assertTrue($this->getSession()->getPage()->findField('Telephone number'));
    $this->assertTrue($this->getSession()->getPage()->findField('Further information'));

    // Test the "By check or other" donation method.
    $this->getSession()->getPage()->fillField('method', "3");
    $this->getSession()->getPage()->fillField('Telephone number', '123456789');
    $this->getSession()->getPage()->fillField('Further information', '123456789');
    $this->submitForm([], 'Give');
    $this->assertTrue($this>$this->getSession()->getPage()->hasContent('Your donation has been received.  Thank you!'));
    $this->drupalLogout();
  }

  public function testCreditCardPaymentMethodAsAuthenticatedUser() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/give/tzedakah');
    $this->assertTrue($this->getSession()->getPage()->hasContent("Your email address"));
    $this->assertTrue($this->getSession()->getPage()->hasContent($this->adminUser->getEmail()));
    $this->assertTrue($this->getSession()->getPage()->hasContent("Your name"));
    $this->assertTrue($this->getSession()->getPage()->hasContent($this->adminUser->getUsername()));
    $this->assertTrue($this->getSession()->getPage()->findField('Amount to give'));
    $this->assertTrue($this->getSession()->getPage()->findField('edit-recurring-1'));
    $this->getSession()->getPage()->fillField('amount', 10);
    $this->submitForm([], 'edit-submit');

    // Check that all the fields are present in the second step.
    $this->assertTrue($this->getSession()->getPage()->findField('method'));
    // The stripe_token field is hidden so we cannot use findField to check if
    // it exists.
    $this->getSession()->getPage()->hasContent('name="stripe_token"');
    $this->assertSession()->fieldExists('stripe_number');
    $this->assertSession()->fieldExists('stripe_exp_month');
    $this->assertSession()->fieldExists('stripe_exp_year');
    $this->assertSession()->fieldExists('stripe_cvc');

    // Test the "By credit/debit card" donation method.
    $this->getSession()->getPage()->fillField('method', "1");
    $this->getSession()->getPage()->fillField("stripe_number", "1234123412341234");
    $this->getSession()->getPage()->fillField('stripe_exp_month', "01");
    $this->getSession()->getPage()->fillField('stripe_exp_year', "19");
    $this->getSession()->getPage()->fillField('stripe_cvc', "123");
    // We haven't a real stripe token so we are going to fake one.
    $this->getSession()->getPage()->find('css', 'input[name="stripe_token"]')->setValue($this->randomString());
    $this->submitForm([], 'Give');
    $this->assertTrue($this->getSession()->getPage()->hasContent('Your donation has been received. Thank you!'));
    $this->drupalLogout();
  }

}
