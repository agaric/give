<?php

namespace Drupal\give\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\give\Entity\GiveForm;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\RoleInterface;

/**
 * Tests site-wide give form functionality.
 *
 * @group give
 */
class GiveSitewideTest extends WebTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('text', 'give', 'field_ui', 'give_test', 'block');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests configuration options and the site-wide give form.
   */
  function testSiteWideGive() {
    // Create and log in administrative user.
    $admin_user = $this->drupalCreateUser(array(
      'access give forms',
      'administer give',
      'administer users',
      'administer account settings',
      'administer give_message fields',
    ));
    $this->drupalLogin($admin_user);

    // Check the presence of expected cache tags.
    $this->drupalGet('give');
    $this->assertCacheTag('config:give.settings');

    $flood_limit = 3;
    $this->config('give.settings')
      ->set('flood.limit', $flood_limit)
      ->set('flood.interval', 600)
      ->save();

    // Set settings.
    $edit = array();
    $edit['give_default_status'] = TRUE;
    $this->drupalPostForm('admin/config/people/accounts', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    $this->drupalGet('admin/structure/give');
    // Default form exists.
    $this->assertLinkByHref('admin/structure/give/manage/tzedakah/delete');

    // Delete old forms to ensure that new forms are used.
    $this->deleteGiveForms();
    $this->drupalGet('admin/structure/give');
    $this->assertNoLinkByHref('admin/structure/give/manage/tzedakah');

    // Ensure that the give form won't be shown without forms.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, array('access give forms'));
    $this->drupalLogout();
    $this->drupalGet('give');
    $this->assertResponse(404);

    $this->drupalLogin($admin_user);
    $this->drupalGet('give');
    $this->assertResponse(200);
    $this->assertText(t('The give form has not been configured.'));

    // Add forms.
    // Test invalid recipients.
    $invalid_recipients = array('invalid', 'invalid@', 'invalid@site.', '@site.', '@site.com');
    foreach ($invalid_recipients as $invalid_recipient) {
      $this->addGiveForm($this->randomMachineName(16), $this->randomMachineName(16), $invalid_recipient, '', FALSE);
      $this->assertRaw(t('%recipient is an invalid email address.', array('%recipient' => $invalid_recipient)));
    }

    // Test validation of empty form and recipients fields.
    $this->addGiveForm('', '', '', '', TRUE);
    $this->assertText(t('Label field is required.'));
    $this->assertText(t('Machine-readable name field is required.'));
    $this->assertText(t('Recipients field is required.'));

    // Test validation of max_length machine name.
    $recipients = array('simpletest&@example.com', 'simpletest2@example.com', 'simpletest3@example.com');
    $max_length = EntityTypeInterface::BUNDLE_MAX_LENGTH;
    $max_length_exceeded = $max_length + 1;
    $this->addGiveForm($id = Unicode::strtolower($this->randomMachineName($max_length_exceeded)), $label = $this->randomMachineName($max_length_exceeded), implode(',', array($recipients[0])), '', TRUE);
    $this->assertText(format_string('Machine-readable name cannot be longer than @max characters but is currently @exceeded characters long.', array('@max' => $max_length, '@exceeded' => $max_length_exceeded)));
    $this->addGiveForm($id = Unicode::strtolower($this->randomMachineName($max_length)), $label = $this->randomMachineName($max_length), implode(',', array($recipients[0])), '', TRUE);
    $this->assertRaw(t('Give form %label has been added.', array('%label' => $label)));

    // Create first valid form.
    $this->addGiveForm($id = Unicode::strtolower($this->randomMachineName(16)), $label = $this->randomMachineName(16), implode(',', array($recipients[0])), '', TRUE);
    $this->assertRaw(t('Give form %label has been added.', array('%label' => $label)));

    // Check that the form was created in site default language.
    $langcode = $this->config('give.form.' . $id)->get('langcode');
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $this->assertEqual($langcode, $default_langcode);

    // Make sure the newly created form is included in the list of forms.
    $this->assertNoUniqueText($label, 'New form included in forms list.');

    // Ensure that the recipient email is escaped on the listing.
    $this->drupalGet('admin/structure/give');
    $this->assertEscaped($recipients[0]);

    // Test update give form.
    $this->updateGiveForm($id, $label = $this->randomMachineName(16), $recipients_str = implode(',', array($recipients[0], $recipients[1])), $reply = $this->randomMachineName(30), FALSE);
    $config = $this->config('give.form.' . $id)->get();
    $this->assertEqual($config['label'], $label);
    $this->assertEqual($config['recipients'], array($recipients[0], $recipients[1]));
    $this->assertEqual($config['reply'], $reply);
    $this->assertNotEqual($id, $this->config('give.settings')->get('default_form'));
    $this->assertRaw(t('Give form %label has been updated.', array('%label' => $label)));
    // Ensure the label is displayed on the give page for this form.
    $this->drupalGet('give/' . $id);
    $this->assertText($label);

    // Reset the form back to be the default form.
    $this->config('give.settings')->set('default_form', $id)->save();

    // Ensure that the give form is shown without a form selection input.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, array('access site-wide give form'));
    $this->drupalLogout();
    $this->drupalGet('give');
    $this->assertText(t('Your email address'));
    $this->assertNoText(t('Form'));
    $this->drupalLogin($admin_user);

    // Add more forms.
    $this->addGiveForm(Unicode::strtolower($this->randomMachineName(16)), $label = $this->randomMachineName(16), implode(',', array($recipients[0], $recipients[1])), '', FALSE);
    $this->assertRaw(t('Give form %label has been added.', array('%label' => $label)));

    $this->addGiveForm($name = Unicode::strtolower($this->randomMachineName(16)), $label = $this->randomMachineName(16), implode(',', array($recipients[0], $recipients[1], $recipients[2])), '', FALSE);
    $this->assertRaw(t('Give form %label has been added.', array('%label' => $label)));

    // Try adding a form that already exists.
    $this->addGiveForm($name, $label, '', '', FALSE);
    $this->assertNoRaw(t('Give form %label has been added.', array('%label' => $label)));
    $this->assertRaw(t('The machine-readable name is already in use. It must be unique.'));

    $this->drupalLogout();

    // Check to see that anonymous user cannot see give page without permission.
    user_role_revoke_permissions(RoleInterface::ANONYMOUS_ID, array('access give forms'));
    $this->drupalGet('give');
    $this->assertResponse(403);

    // Give anonymous user permission and see that page is viewable.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, array('access give forms'));
    $this->drupalGet('give');
    $this->assertResponse(200);

    // Submit give form with invalid values.
    $this->submitGive('', $recipients[0], $id, $this->random, 50);
    $this->assertText(t('Your name field is required.'));

    $this->submitGive($this->randomMachineName(16), '', $id, 50);
    $this->assertText(t('Your email address field is required.'));

    $this->submitGive($this->randomMachineName(16), $invalid_recipients[0], $id, 50);
    $this->assertRaw(t('The email address %mail is not valid.', array('%mail' => 'invalid')));

    $this->submitGive($this->randomMachineName(16), $recipients[0], $this->randomMachineName(16), $id, '');
    $this->assertText(t('Message field is required.'));

    // Test give form with no default form selected.
    $this->config('give.settings')
      ->set('default_form', '')
      ->save();
    $this->drupalGet('give');
    $this->assertResponse(404);

    // Try to access give form with non-existing form IDs.
    $this->drupalGet('give/0');
    $this->assertResponse(404);
    $this->drupalGet('give/' . $this->randomMachineName());
    $this->assertResponse(404);

    // Submit give form with correct values and check flood interval.
    for ($i = 0; $i < $flood_limit; $i++) {
      $this->submitGive($this->randomMachineName(16), $recipients[0], $this->randomMachineName(16), $id, $this->randomMachineName(64));
      $this->assertText(t('Your message has been sent.'));
    }
    // Submit give form one over limit.
    $this->submitGive($this->randomMachineName(16), $recipients[0], $this->randomMachineName(16), $id, $this->randomMachineName(64));
    $this->assertRaw(t('You cannot send more than %number messages in 10 min. Try again later.', array('%number' => $this->config('give.settings')->get('flood.limit'))));

    // Test listing controller.
    $this->drupalLogin($admin_user);

    $this->deleteGiveForms();

    $label = $this->randomMachineName(16);
    $recipients = implode(',', array($recipients[0], $recipients[1], $recipients[2]));
    $give_form = Unicode::strtolower($this->randomMachineName(16));
    $this->addGiveForm($give_form, $label, $recipients, '', FALSE);
    $this->drupalGet('admin/structure/give');
    $this->clickLink(t('Edit'));
    $this->assertResponse(200);
    $this->assertFieldByName('label', $label);

    // Test field UI and field integration.
    $this->drupalGet('admin/structure/give');

    $view_link = $this->xpath('//table/tbody/tr/td/a[contains(@href, :href) and text()=:text]', [
      ':href' => \Drupal::url('entity.give_form.canonical', ['give_form' => $give_form]),
      ':text' => $label,
      ]
    );
    $this->assertTrue(!empty($view_link), 'Give listing links to give form.');

    // Find out in which row the form we want to add a field to is.
    $i = 0;
    foreach ($this->xpath('//table/tbody/tr') as $row) {
      if (((string) $row->td[0]->a) == $label) {
        break;
      }
      $i++;
    }

    $this->clickLink(t('Manage fields'), $i);
    $this->assertResponse(200);
    $this->clickLink(t('Add field'));
    $this->assertResponse(200);

    // Create a simple textfield.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_label = $this->randomMachineName();
    $this->fieldUIAddNewField(NULL, $field_name, $field_label, 'text');
    $field_name = 'field_' . $field_name;

    // Check that the field is displayed.
    $this->drupalGet('give/' . $give_form);
    $this->assertText($field_label);

    // Submit the give form and verify the content.
    $edit = array(
      'subject[0][value]' => $this->randomMachineName(),
      'message[0][value]' => $this->randomMachineName(),
      $field_name . '[0][value]' => $this->randomMachineName(),
    );
    $this->drupalPostForm(NULL, $edit, t('Send message'));
    $mails = $this->drupalGetMails();
    $mail = array_pop($mails);
    $this->assertEqual($mail['subject'], t('[@label] @subject', array('@label' => $label, '@subject' => $edit['subject[0][value]'])));
    $this->assertTrue(strpos($mail['body'], $field_label));
    $this->assertTrue(strpos($mail['body'], $edit[$field_name . '[0][value]']));
  }

  /**
   * Tests auto-reply on the site-wide give form.
   */
  function testAutoReply() {
    // Create and log in administrative user.
    $admin_user = $this->drupalCreateUser(array('access site-wide give form', 'administer give forms', 'administer permissions', 'administer users'));
    $this->drupalLogin($admin_user);

    // Set up three forms, 2 with an auto-reply and one without.
    $foo_autoreply = $this->randomMachineName(40);
    $bar_autoreply = $this->randomMachineName(40);
    $this->addGiveForm('foo', 'foo', 'foo@example.com', $foo_autoreply, FALSE);
    $this->addGiveForm('bar', 'bar', 'bar@example.com', $bar_autoreply, FALSE);
    $this->addGiveForm('no_autoreply', 'no_autoreply', 'bar@example.com', '', FALSE);

    // Log the current user out in order to test the name and email fields.
    $this->drupalLogout();
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, array('access site-wide give form'));

    // Test the auto-reply for form 'foo'.
    $email = $this->randomMachineName(32) . '@example.com';
    $subject = $this->randomMachineName(64);
    $this->submitGive($this->randomMachineName(16), $email, $subject, 'foo', $this->randomString(128));

    // We are testing the auto-reply, so there should be one email going to the sender.
    $captured_emails = $this->drupalGetMails(array('id' => 'give_page_autoreply', 'to' => $email));
    $this->assertEqual(count($captured_emails), 1);
    $this->assertEqual(trim($captured_emails[0]['body']), trim(MailFormatHelper::htmlToText($foo_autoreply)));

    // Test the auto-reply for form 'bar'.
    $email = $this->randomMachineName(32) . '@example.com';
    $this->submitGive($this->randomMachineName(16), $email, $this->randomString(64), 'bar', $this->randomString(128));

    // Auto-reply for form 'bar' should result in one auto-reply email to the sender.
    $captured_emails = $this->drupalGetMails(array('id' => 'give_page_autoreply', 'to' => $email));
    $this->assertEqual(count($captured_emails), 1);
    $this->assertEqual(trim($captured_emails[0]['body']), trim(MailFormatHelper::htmlToText($bar_autoreply)));

    // Verify that no auto-reply is sent when the auto-reply field is left blank.
    $email = $this->randomMachineName(32) . '@example.com';
    $this->submitGive($this->randomMachineName(16), $email, $this->randomString(64), 'no_autoreply', $this->randomString(128));
    $captured_emails = $this->drupalGetMails(array('id' => 'give_page_autoreply', 'to' => $email));
    $this->assertEqual(count($captured_emails), 0);
  }

  /**
   * Adds a form.
   *
   * @param string $id
   *   The form machine name.
   * @param string $label
   *   The form label.
   * @param string $recipients
   *   The list of recipient email addresses.
   * @param string $reply
   *   The auto-reply text that is sent to a user upon completing the give
   *   form.
   * @param bool $selected
   *   A Boolean indicating whether the form should be selected by default.
   * @param array $third_party_settings
   *   Array of third party settings to be added to the posted form data.
   */
  function addGiveForm($id, $label, $recipients, $reply, $selected, $third_party_settings = []) {
    $edit = array();
    $edit['label'] = $label;
    $edit['id'] = $id;
    $edit['recipients'] = $recipients;
    $edit['reply'] = $reply;
    $edit['selected'] = ($selected ? TRUE : FALSE);
    $edit += $third_party_settings;
    $this->drupalPostForm('admin/structure/give/add', $edit, t('Save'));
  }

  /**
   * Updates a form.
   *
   * @param string $id
   *   The form machine name.
   * @param string $label
   *   The form label.
   * @param string $recipients
   *   The list of recipient email addresses.
   * @param string $reply
   *   The auto-reply text that is sent to a user upon completing the give
   *   form.
   * @param bool $selected
   *   A Boolean indicating whether the form should be selected by default.
   */
  function updateGiveForm($id, $label, $recipients, $reply, $selected) {
    $edit = array();
    $edit['label'] = $label;
    $edit['recipients'] = $recipients;
    $edit['reply'] = $reply;
    $edit['selected'] = ($selected ? TRUE : FALSE);
    $this->drupalPostForm("admin/structure/give/manage/$id", $edit, t('Save'));
  }

  /**
   * Submits the give form.
   *
   * @param string $name
   *   The name of the sender.
   * @param string $mail
   *   The email address of the sender.
   * @param string $subject
   *   The subject of the message.
   * @param string $id
   *   The form ID of the message.
   * @param string $message
   *   The message body.
   */
  function submitGive($name, $mail, $subject, $id, $message) {
    $edit = array();
    $edit['name'] = $name;
    $edit['mail'] = $mail;
    $edit['subject[0][value]'] = $subject;
    $edit['message[0][value]'] = $message;
    if ($id == $this->config('give.settings')->get('default_form')) {
      $this->drupalPostForm('give', $edit, t('Give'));
    }
    else {
      $this->drupalPostForm('give/' . $id, $edit, t('Give'));
    }
  }

  /**
   * Deletes all forms.
   */
  function deleteGiveForms() {
    $give_forms = GiveForm::loadMultiple();;
    foreach ($give_forms as $id => $give_form) {
      $this->drupalPostForm("admin/structure/give/manage/$id/delete", array(), t('Delete'));
      $this->assertRaw(t('The give form %label has been deleted.', array('%label' => $give_form->label())));
      $this->assertFalse(GiveForm::load($id), format_string('Form %give_form not found', array('%give_form' => $give_form->label())));
    }
  }

}
