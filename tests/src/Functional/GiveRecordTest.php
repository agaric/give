<?php

/**
 * @file
 * Contains \Drupal\give_record\Tests\GiveRecordTest.
 */

namespace Drupal\give_record\Tests;

/**
 * Tests storing give donations and viewing them through UI.
 *
 * @group give_record
 */
class GiveRecordTest extends GiveRecordTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'text',
    'give',
    'field_ui',
    'give_record_test',
    'give_test',
    'give_record',
  );

  /**
   * Tests give donations submitted through give form.
   */
  public function testGiveRecord() {
    // Create and login administrative user.
    $admin_user = $this->drupalCreateUser(array(
      'access give forms',
      'administer give',
      'administer users',
      'administer account settings',
      'administer give_donation fields',
      'administer give_donation display',
    ));
    $this->drupalLogin($admin_user);
    // Create first valid give form.
    $mail = 'simpletest@example.com';
    $this->addGiveForm('test_id', 'test_label', $mail, '', TRUE);
    $this->assertText(t('Give form test_label has been added.'));

    // Ensure that anonymous users can submit give forms.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access give forms'));
    $this->drupalLogout();
    $this->drupalGet('give');
    $this->assertText(t('Your email address'));
    $this->assertNoText(t('Form'));
    $this->submitGive('Test_name', $mail, 'test_id', '22');
    $this->assertText(t('Your donation has been sent.'));

    // Login as admin.
    $this->drupalLogin($admin_user);

    $display_fields = array(
      "The donor's name",
      "The donor's email",
      "Amount"
    );

    // Check that name, amount, and mail are configurable on display.
    $this->drupalGet('admin/structure/give/manage/test_id/display');
    foreach ($display_fields as $label) {
      $this->assertText($label);
    }

    // Check the donation list overview.
    $this->drupalGet('admin/structure/give/donations');
    $rows = $this->xpath('//tbody/tr');
    // Make sure only 1 donation is available.
    $this->assertEqual(count($rows), 1);
    // Some fields should be present.
    $this->assertText('22');
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
    $this->assertFieldById('edit-amount-0-value', 22);
    // Submit should redirect back to listing.
    $this->drupalPostForm(NULL, array(), t('Save'));
    $this->assertUrl('admin/structure/give/donations');

    // Delete the donation.
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $this->assertRaw(t('The @entity-type %label has been deleted.', [
      // See \Drupal\Core\Entity\EntityDeleteFormTrait::getDeletionMessage().
      '@entity-type' => 'give donation',
      '%label'       => 'Test_name (Test_mail) 22 via test_id',
    ]));
    // Make sure no donations are available.
    $this->assertText('There are no recorded donations yet.');

    // Fill the redirect field and assert the page is successfully redirected.
    $edit = ['give_record_uri' => 'entity:user/' . $admin_user->id()];
    $this->drupalPostForm('admin/structure/give/manage/test_id', $edit, t('Save'));
    $edit = [
      'amount[0][value]' => 'Test amount',
    ];
    $this->drupalPostForm('give', $edit, t('Give'));
    $this->assertText('Your donation has been sent.');
    $this->assertEqual($this->url, $admin_user->urlInfo()->setAbsolute()->toString());

    // Fill the "Submit button text" field and assert the form can still be
    // submitted.
    $edit = [
      'give_record_submit_text' => 'Submit the form',
      'give_record_preview' => FALSE,
    ];
    $this->drupalPostForm('admin/structure/give/manage/test_id', $edit, t('Save'));
    $edit = [
      'amount[0][value]' => 'Test amount',
    ];
    $this->drupalGet('give');
    $element = $this->cssSelect('#edit-preview');
    // Preview button is hidden.
    $this->assertTrue(empty($element));
    $this->drupalPostForm(NULL, $edit, t('Submit the form'));
    $this->assertText('Your donation has been sent.');
  }

}
