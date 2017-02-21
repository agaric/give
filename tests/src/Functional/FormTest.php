<?php

namespace Drupal\Tests\comment_notify\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Comment notify Base Test class.
 *
 * @group give
 */
class FormTest extends BrowserTestBase {

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
    $this->drupalLogout();
  }

}
