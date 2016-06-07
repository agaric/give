<?php

namespace Drupal\give\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\give\DonationInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the give donation entity.
 *
 * @ContentEntityType(
 *   id = "give_donation",
 *   label = @Translation("Give donation"),
 *   handlers = {
 *     "access" = "Drupal\give\GiveDonationAccessControlHandler",
 *     "storage" = "Drupal\Core\Entity\ContentEntityNullStorage",
 *     "view_builder" = "Drupal\give\DonationViewBuilder",
 *     "form" = {
 *       "default" = "Drupal\give\DonationForm"
 *     }
 *   },
 *   admin_permission = "administer give forms",
 *   entity_keys = {
 *     "bundle" = "give_form",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode"
 *   },
 *   bundle_entity_type = "give_form",
 *   field_ui_base_route = "entity.give_form.edit_form",
 * )
 */
class Donation extends ContentEntityBase implements DonationInterface {

  /**
   * {@inheritdoc}
   */
  public function isPersonal() {
    return $this->bundle() == 'personal';
  }

  /**
   * {@inheritdoc}
   */
  public function getGiveForm() {
    return $this->get('give_form')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getSenderName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSenderName($sender_name) {
    $this->set('name', $sender_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getSenderMail() {
    return $this->get('mail')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSenderMail($sender_mail) {
    $this->set('mail', $sender_mail);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject() {
    return $this->get('subject')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubject($subject) {
    $this->set('subject', $subject);
  }

  /**
   * {@inheritdoc}
   */
  public function getDonation() {
    return $this->get('donation')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDonation($donation) {
    $this->set('donation', $donation);
  }

  /**
   * {@inheritdoc}
   */
  public function copySender() {
    return (bool)$this->get('copy')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCopySender($inform) {
    $this->set('copy', (bool) $inform);
  }

  /**
   * {@inheritdoc}
   */
  public function getPersonalRecipient() {
    if ($this->isPersonal()) {
      return $this->get('recipient')->entity;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['give_form'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Form ID'))
      ->setDescription(t('The ID of the associated form.'))
      ->setSetting('target_type', 'give_form')
      ->setRequired(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The donation UUID.'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The donation language code.'))
      ->setDisplayOptions('form', array(
        'type' => 'language_select',
        'weight' => 2,
      ));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t("The sender's name"))
      ->setDescription(t('The name of the person that is sending the give donation.'));

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t("The sender's email"))
      ->setDescription(t('The email of the person that is sending the give donation.'));

    // The subject of the give donation.
    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -10,
      ))
      ->setDisplayConfigurable('form', TRUE);

    // The text of the give donation.
    $fields['donation'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Donation'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => 0,
        'settings' => array(
          'rows' => 12,
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'weight' => 0,
        'label' => 'above',
      ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['copy'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Copy'))
      ->setDescription(t('Whether to send a copy of the donation to the sender.'));

    $fields['recipient'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Recipient ID'))
      ->setDescription(t('The ID of the recipient user for personal give donations.'))
      ->setSetting('target_type', 'user');

    return $fields;
  }

}
