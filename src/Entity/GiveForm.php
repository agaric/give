<?php

namespace Drupal\give\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\give\GiveFormInterface;

/**
 * Defines the give form entity.
 *
 * @ConfigEntityType(
 *   id = "give_form",
 *   label = @Translation("Give form"),
 *   handlers = {
 *     "access" = "Drupal\give\GiveFormAccessControlHandler",
 *     "list_builder" = "Drupal\give\Form\GiveForm\GiveFormListBuilder",
 *     "view_builder" = "\Drupal\give\GiveFormViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\give\Form\GiveForm\GiveFormEditForm",
 *       "edit" = "Drupal\give\Form\GiveForm\GiveFormEditForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "form",
 *   admin_permission = "administer give",
 *   bundle_of = "give_donation",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "delete-form" = "/admin/structure/give/manage/{give_form}/delete",
 *     "edit-form" = "/admin/structure/give/manage/{give_form}",
 *     "collection" = "/admin/structure/give",
 *     "canonical" = "/give/{give_form}",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "recipients",
 *     "subject",
 *     "reply",
 *     "check_or_other_text",
 *     "weight",
 *     "redirect_uri",
 *     "submit_text",
 *     "frequencies"
 *   }
 * )
 */
class GiveForm extends ConfigEntityBundleBase implements GiveFormInterface {

  /**
   * The form ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable label of the category.
   *
   * @var string
   */
  protected $label;

  /**
   * List of recipient email addresses.
   *
   * @var array
   */
  protected $recipients = [];

  /**
   * An automatic subject with a receipt for the donation.
   *
   * @var string
   */
  protected $subject = '';

  /**
   * An automatic reply with a receipt for the donation.
   *
   * @var string
   */
  protected $reply = '';

  /**
   * Optional message to show potential givers who select the "Check or other"
   * donation method.
   *
   * @var string
   */
  protected $check_or_other_text = '';

  /**
   * The weight of the category.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The uri where the user will go after to donate.
   *
   * @var string
   */
  protected $redirect_uri = '/';

  /**
   * The text displayed in the Submit Button.
   *
   * @var string
   */
  protected $submit_text = 'Give';

  /**
   * Frequency intervals (Stripe Plans).
   *
   * @var array
   */
  protected $frequencies = [];

  /**
   * {@inheritdoc}
   */
  public function getFrequencies() {
    return $this->frequencies;
  }

  /**
   * {@inheritdoc}
   */
  public function setFrequencies(array $frequencies) {
    $this->frequencies = $frequencies;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients() {
    return $this->recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecipients($recipients) {
    $this->recipients = $recipients;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject() {
    return $this->subject;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubject($subject) {
    $this->subject = $subject;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getReply() {
    return $this->reply;
  }

  /**
   * {@inheritdoc}
   */
  public function setReply($reply) {
    $this->reply = $reply;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCheckOrOtherText() {
    return $this->check_or_other_text;
  }

  /**
   * {@inheritdoc}
   */
  public function setCheckOrOtherText($text) {
    $this->check_or_other_text = $text;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUri() {
    return $this->redirect_uri;
  }

  /**
   * {@inheritdoc}
   */
  public function setRedirectUri($redirect_uri) {
    $this->redirect_uri = $redirect_uri;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmitText() {
    return $this->submit_text;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubmitText($submit_text) {
    $this->submit_text = $submit_text;
    return $this;
  }

}
