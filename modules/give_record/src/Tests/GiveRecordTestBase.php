<?php
/**
 * @file
 * Contains \Drupal\give_record\Tests\GiveRecordTestBase.
 */

namespace Drupal\give_record\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Defines a base-class for contact-storage tests.
 */
abstract class GiveRecordTestBase extends WebTestBase {

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
   *   The auto-reply text that is sent to a user upon completing the donation
   *   form.
   * @param bool $selected
   *   A Boolean indicating whether the form should be selected by default.
   * @param array $third_party_settings
   *   Array of third party settings to be added to the posted form data.
   */
  public function addGiveForm($id, $label, $recipients, $reply, $selected, $third_party_settings = []) {
    $edit = [];
    $edit['label'] = $label;
    $edit['id'] = $id;
    $edit['recipients'] = $recipients;
    $edit['reply'] = $reply;
    $edit['selected'] = ($selected ? TRUE : FALSE);
    $edit += $third_party_settings;
    $this->drupalPostForm('admin/structure/give/add', $edit, t('Save'));
  }

  /**
   * Submits the contact form.
   *
   * @param string $name
   *   The name of the donor.
   * @param string $mail
   *   The email address of the donor.
   * @param string $amount
   *   The amount of the donation.
   * @param string $id
   *   The form ID of the message.
   */
  public function submitGive($name, $mail, $amount, $id) {
    $edit = [];
    $edit['name'] = $name;
    $edit['mail'] = $mail;
    $edit['amount[0][value]'] = $amount;
    if ($id == $this->config('give.settings')->get('default_form')) {
      $this->drupalPostForm('give', $edit, t('Give'));
    }
    else {
      $this->drupalPostForm('give/' . $id, $edit, t('Give'));
    }
  }

}
