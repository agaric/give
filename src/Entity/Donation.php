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
 *     "view_builder" = "Drupal\give\DonationViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\give\DonationViewsData",
 *     "form" = {
 *       "default" = "Drupal\give\Form\Donation\DonationForm",
 *       "payment" = "Drupal\give\Form\Donation\PaymentForm",
 *       "edit" = "Drupal\give\Form\Donation\DonationEditForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     }
 *   },
 *   base_table = "give_donation",
 *   admin_permission = "administer give",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "bundle" = "give_form",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/give/donations/{give_donation}",
 *     "edit-form" = "/admin/structure/give/donations/{give_donation}/edit",
 *     "delete-form" = "/admin/structure/give/donations/{give_donation}/delete",
 *     "collection" = "/admin/structure/give/donations"
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
  public function getLabel() {
    return $this->get('label')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->set('label', $label);
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
  public function getAmount() {
    return $this->get('amount')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAmount($amount) {
    $this->set('amount', $amount);
  }

  /**
   * {@inheritdoc}
   */
  public function getDollarAmount() {
    $cents = $this->getAmount();
    return t("$@amount", array('@amount' => round($cents/100, 2)));
  }

  /**
   * {@inheritdoc}
   */
  public function setDollarAmount($dollar_amount) {
    $this->setAmount($dollar_amount * 100);
  }

  /**
   * {@inheritdoc}
   */
  public function recurring() {
    return (bool) $this->getRecurrenceIntervalCount() && $this->getRecurrenceIntervalUnit();
  }

  /**
   * {@inheritdoc}
   */
  public function setRecurrenceIntervalUnit($interval) {
    if ($interval != 'month') {
      throw new \Exception(t("Unsupported interval %interval. Interval periods other than month-based are not currently supported.", ['%interval' => $interval]));
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecurrenceIntervalUnit() {
    return 'month';
  }


  /**
   * {@inheritdoc}
   */
  public function getRecurrenceIntervalCount() {
    return $this->get('recurring')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecurrenceIntervalCount($count) {
    $this->set('recurring', $count);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecurrence() {
    return give_recurrance_label($this->getRecurrenceIntervalCount(), $this->getRecurrenceIntervalUnit());
  }

  /**
   * {@inheritdoc}
   *
   * Note that currency is hard-coded to US Dollars and interval to monthly,
   * as they are elsewhere in the application, but it would be trivial to
   * change.
   */
  public function getPlanId() {
    return 'usd' . $this->getAmount() . '_month' . $this->recurring();
  }

  /**
   * {@inheritdoc}
   */
  public function getPlanName() {
    return $this->getDollarAmount() . ' ' . $this->getRecurrence();
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdatedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setUpdatedTime($timestamp) {
    $this->set('changed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStripeToken() {
    return $this->get('stripe_token')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStripeToken($token) {
    $this->set('stripe_token', $token);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isCompleted() {
    return (bool) $this->get('complete')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCompleted($completed = TRUE) {
    $this->set('complete', $completed ? DONATION_COMPLETED : DONATION_NOT_COMPLETED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Donation ID'))
      ->setDescription(t('The donation ID.'))
      ->setReadOnly(TRUE)
      // Explicitly set this to 'give' so that
      // ContentEntityDatabaseStorage::usesDedicatedTable() doesn't attempt to
      // put the ID in a dedicated table.
      // @todo Remove when https://www.drupal.org/node/1498720 is in.
      ->setProvider('give')
      ->setSetting('unsigned', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the donation was created.'))
      ->setTranslatable(TRUE)
      ->setReadOnly(TRUE);

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

    // The label of the give donation (will be automatically created from other
    // parts; see DonationForm::buildEntity()).
    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE);

    // The text of the give donation.
    $fields['amount'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Donation amount'))
      ->setDescription('The amount of the donation, in cents USD.')
      ->setRequired(TRUE);

    $fields['recurring'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Recurring'))
      ->setDescription(t('The interval counts (in number of months) at which the donation should recur, or zero if not recurring.'))
      ->setDisplayOptions('form', [
        'type' => 'list_integer',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created time'))
      ->setDescription(t('The time that the node was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the node was last edited.'));

    $fields['stripe_token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe token'))
      ->setDescription(t('The token returned by Stripe used to tell Stripe to process the donation.'));

    $fields['complete'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Completed donation'))
      ->setDefaultValue(FALSE);

    return $fields;
  }

}
