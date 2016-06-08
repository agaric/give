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
  public function getGiveForm() {
    return $this->get('give_form')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getDonorName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDonorName($donor_name) {
    $this->set('name', $donor_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getDonorMail() {
    return $this->get('mail')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDonorMail($donor_mail) {
    $this->set('mail', $donor_mail);
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
  public function recurring() {
    return (bool)$this->get('recurring')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecurring($recurring) {
    $this->set('recurring', (bool) $recurring);
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
      ->setLabel(t("The donor's name"))
      ->setDescription(t('The name of the person that is sending the give donation.'));

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t("The donor's email"))
      ->setDescription(t('The email of the person that is sending the give donation.'));

    // The label of the give donation (will be automatically created from other parts).
    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE);

    // The text of the give donation.
    $fields['donation'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Donation'))
      ->setDescription('The amount of the donation, in cents.')
      ->setRequired(TRUE);

    $fields['recurring'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Recurring'))
      ->setDescription(t('Whether the donation should recur monthly.'));

    $fields['recipient'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Recipient ID'))
      ->setDescription(t('The ID of the recipient user for personal give donations.'))
      ->setSetting('target_type', 'user');

    return $fields;
  }

}
