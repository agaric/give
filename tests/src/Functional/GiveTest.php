<?php

/**
 * @file
 * Contains \Drupal\give\Tests\GiveRecordTest.
 */

namespace Drupal\Tests\give\Functional;

/**
 * Tests storing give donations and viewing them through UI.
 *
 * @group give
 */
class GiveRecordTest extends GiveTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'text',
    'give',
    'field_ui',
    'give_test',
  );

  /**
   * Tests give donations submitted through give form.
   */
  public function testGiveRecord() {
    // Create and login administrative user.
    $admin_user = $this->drupalCreateUser(array(
      'access give forms',
      'administer give',
      'create and edit give forms',
      'access give forms',
      'administer users',
      'administer account settings',
    ));
    $this->drupalLogin($admin_user);
    // Create first valid give form.
    $mail = 'simpletest@example.com';
    $this->addGiveForm('test_id', 'test_label', $mail, '', TRUE);
    $this->assertTrue($this->getSession()->getPage()->hasContent('Give form test_label has been added.'));

    // Ensure that anonymous users can submit give forms.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access give forms'));
    $this->drupalLogout();
    $this->drupalGet('give/test_id');
    $this->assertText(t('Your email address'));
    $this->assertNoText(t('Form'));
    $this->submitGive('Test_name', $mail, '22', 'test_id');

    // Check to that the user was sent to the step 2.
    $this->assertTrue(preg_match('/\/give\/test_id\/1/', $this->getSession()->getCurrentUrl()));

    // Login as admin.
    $this->drupalLogin($admin_user);

    $display_fields = array(
      "Donor name",
      "Donor email address",
      "Amount (USD)"
    );

    // Check the donation list overview.
    $this->drupalGet('admin/structure/give/donations');
    $rows = $this->xpath('//tbody/tr');
    // Make sure only 1 donation is available.
    $this->assertEqual(count($rows), 1);
    // Some fields should be present.
    $this->assertText('22.00');
    $this->assertText('Test_name');
    $this->assertText('test_label');

    // Click the view link and make sure name, amount, and email are displayed
    // by default.
    $this->clickLink(t('View'));
    foreach ($display_fields as $label) {
      $this->assertText($label);
    }

    // Make sure the stored donation is correct.
    $this->drupalGet('admin/structure/give/donations');
    $this->clickLink(t('Edit'));
    $this->assertFieldById('edit-name', 'Test_name');
    $this->assertFieldById('edit-mail', $mail);
    $this->assertFieldById('edit-amount', 22);
    // Submit should redirect back to listing.
    $this->drupalPostForm(NULL, array(), t('Give '));
    $this->assertUrl('admin/structure/give/donations');

    // Delete the donation.
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    // Make sure no donations are available.
    $this->assertText('There are no recorded donations yet.');

    // Fill the redirect field and assert the page is successfully redirected.
    //@todo these tests assume to the donation is made in only one step, update.
//    $edit = ['give_record_uri' => 'entity:user/' . $admin_user->id()];
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
//      'give_record_submit_text' => 'Submit the form',
//      'give_record_preview' => FALSE,
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
