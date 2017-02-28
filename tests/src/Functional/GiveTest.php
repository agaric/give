<?php

/**
 * @file
 * Contains \Drupal\give\Tests\GiveTest.
 */

namespace Drupal\Tests\give\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Test\AssertMailTrait;

/**
 * Tests storing give donations and viewing them through UI.
 *
 * @group give
 */
class GiveTest extends GiveTestBase {

  use AssertMailTrait;

  /**
   * Tests give donations submitted through give form.
   */
  public function testGiveRecord() {
    // Login administrative user.
    $this->adminUser = $this->drupalCreateUser(array(
      'access give forms',
      'administer give',
      'create and edit give forms',
      'administer users',
      'administer account settings',
    ));

    $flood_limit = 1;
    $this->config('give.settings')
      ->set('flood.limit', $flood_limit)
      ->set('flood.interval', 600)
      ->save();

    $this->drupalLogin($this->adminUser);
    // Create first valid give form.
    $mail = 'simpletest@example.com';
    $this->addGiveForm('test_id', 'test_label', $mail, "Autoreply message", TRUE);
    $this->assertTrue($this->getSession()->getPage()->hasContent('Give form test_label has been added.'));

    // Ensure that anonymous users can submit give forms.
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['access give forms']);
    $this->drupalLogout();
    $this->drupalGet('give/test_id');
    $this->assertSession()->pageTextContains(t('Your email address'));
    $this->submitGive('Test_name', $mail, '22', 'test_id');

    // Check to that the user was sent to the step 2.
    $this->assertSession()->addressEquals('/give/test_id/1');
    $this->submitByCheck('12345678', 'Test');
    $this->assertSession()->pageTextContains('Your donation has been received. Thank you!');

    // Test the flood interval, the second donation is not work because the user
    // should wait until the flood time has passed.
    $this->drupalGet('give/test_id');
    $this->assertSession()->pageTextContains(t('Your email address'));
    $this->submitGive('Test_name', $mail, '22', 'test_id');
    $this->assertSession()->pageTextContains('You cannot send more than 1 donations in 10 min. Try again later.');

    // Test to the autoreply email was sent.
    // @todo test that the autoreply mail is not send when the message is empty.
    $captured_emails = $this->getMails(array('id' => 'give_donation_receipt'));
    $this->assertEquals(1, count($captured_emails));
    $this->assertEquals(trim('Autoreply message'), trim($captured_emails[0]['body']));

    // Login as admin.
    $this->drupalLogin($this->adminUser);

    // Check the donation list overview.
    $this->drupalGet('admin/structure/give/donations');
    $rows = $this->xpath('//tbody/tr');
    // Make sure only 1 donation is available.
    $this->assertEquals(1, count($rows));
    // Some fields should be present.
    $this->assertTrue($this->getSession()->getPage()->hasContent('$22.00'));
    $this->assertTrue($this->getSession()->getPage()->hasContent('Test_name'));
    $this->assertTrue($this->getSession()->getPage()->hasContent('test_label'));

    // Click the view link and make sure name, amount, and email are displayed
    // by default.
    $this->clickLink(t('Edit'));

    $display_fields = [
      "Donor name",
      "Donor email address",
      "Amount (USD)",
    ];
    foreach ($display_fields as $label) {
      $this->assertSession()->pageTextContains($label);
    }

    // Make sure the stored donation is correct.
    $this->drupalGet('admin/structure/give/donations');
    $this->clickLink(t('Edit'));
    $this->assertSession()->fieldValueEquals('edit-name', 'Test_name');
    $this->assertSession()->fieldValueEquals('edit-mail', $mail);
    $this->assertSession()->fieldValueEquals('edit-amount', 22);


    // Submit should redirect back to listing.
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertSession()->addressEquals('admin/structure/give/donations');

    // Delete the donation.
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, [], t('Delete'));
    // Make sure no donations are available.
    $this->assertSession()->pageTextContains('There are no recorded donations yet.');

    // Fill the redirect field and assert the page is successfully redirected.
    //@todo Test that the redirect url option is working.
//    $edit = ['give_uri' => 'entity:user/' . $admin_user->id()];
//    $this->drupalPostForm('admin/structure/give/manage/test_id', $edit, t('Save'));
//    $edit = [
//      'amount[0][value]' => 'Test amount',
//    ];
//    $this->drupalPostForm('give', $edit, t('Give'));
//    $this->assertText('Your donation has been sent.');
//    $this->assertEqual($this->url, $admin_user->urlInfo()->setAbsolute()->toString());
//
//    // Fill the "Submit button text" field and assert the form can still be
//    // submitted.
//    $edit = [
//      'give_submit_text' => 'Submit the form',
//      'give_preview' => FALSE,
//    ];
//    $this->drupalPostForm('admin/structure/give/manage/test_id', $edit, t('Save'));
//    $edit = [
//      'amount[0][value]' => 'Test amount',
//    ];
//    $this->drupalGet('give');
//    $element = $this->cssSelect('#edit-preview');
//    // Preview button is hidden.
//    $this->assertTrue(empty($element));
//    $this->drupalPostForm(NULL, $edit, t('Submit the form'));
//    $this->assertText('Your donation has been sent.');
  }

}
