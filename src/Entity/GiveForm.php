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
 *     "list_builder" = "Drupal\give\GiveFormListBuilder",
 *     "form" = {
 *       "add" = "Drupal\give\GiveFormEditForm",
 *       "edit" = "Drupal\give\GiveFormEditForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "form",
 *   admin_permission = "administer give forms",
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
  protected $recipients = array();

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
   * An automatic reply with a receipt for the donation.
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
    return $subject->subject;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubject($reply) {
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

}
