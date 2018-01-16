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
  public function setLabel() {
    // We always build the donation's label from the donor's name and e-mail,
    // the amount of the donation, and the subject field if present.
    $label = $this->getGiveForm()->get('label') . ' : ';
    if ($this->getDonorName()) {
      $label .= $this->getDonorName() . ' ';
    }
    if ($this->getDonorMail()) {
      $label .= '(' . $this->getDonorMail() . ') ';
    }

    $subject = '';
    if ($this->hasField('field_subject')) {
      // The subject may be in any format, so:
      // 1) Filter it into HTML
      // 2) Strip out all HTML tags
      // 3) Convert entities back to plain-text.
      $subject_text = $this->field_subject->processed;
      $subject = Unicode::truncate(trim(Html::decodeEntities(strip_tags($subject_text))), 29, TRUE, TRUE);
    }
    if ($subject) {
      $label .= ': ' . $subject;
    }
    $this->set('label', $label);
    return $this;

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
    return t("$@amount", ['@amount' => round($cents/100, 2)]);
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
    return ($this->get('recurring')->value != -1);
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
    $give_form = $this->getGiveForm();
    $frequencies = $give_form->getFrequencies();
    return $frequencies[$this->get('recurring')->value]['interval'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRecurrenceIntervalCount() {
    $give_form = $this->getGiveForm();
    $frequencies = $give_form->getFrequencies();
    return $frequencies[$this->get('recurring')->value]['interval_count'];
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
    $give_form = $this->getGiveForm();
    $frequencies = $give_form->getFrequencies();
    $recurrence = $frequencies[$this->get('recurring')->value]['description'];
    // As all of this function, below is a temporary cludge until values saved right.
    return ($recurrence) ? $recurrence : 'No';
  }

  /**
   * {@inheritdoc}
   */
  public function setMethod($method) {
    $this->set('method', $method);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMethod() {
    return $this->get('method');
  }

  /**
   * {@inheritdoc}
   */
  public function getMethodName() {
    $method = $this->getMethod();
    if ($method === NULL) {
      return "None";
    }
    $methods = give_methods();
    return $methods[$method];
  }

  /**
   * {@inheritdoc}
   */
  public function getMethodLongName() {
    $method = $this->getMethod();
    if ($method === NULL) {
      return "No method chosen";
    }
    $methods = give_methods(TRUE);
    return $methods[$method];
  }

  /**
   * {@inheritdoc}
   *
   * Note that currency is hard-coded to US Dollars as elsewhere in the
   * application, but it would not be difficult to change.
   */
  public function getPlanId() {
    $give_form = $this->getGiveForm();
    $frequencies = $give_form->getFrequencies();
    $interval = $frequencies[$this->get('recurring')->value]['interval'];
    $interval_count = $frequencies[$this->get('recurring')->value]['interval_count'];
    return 'usd' . $this->getAmount() . '_' . $interval . '_' . $interval_count;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlanName() {
    $give_form = $this->getGiveForm();
    $frequencies = $give_form->getFrequencies();
    return $this->getDollarAmount() . ' ' . $frequencies[$this->get('recurring')->value]['description'];
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
  public function getCardLast4() {
    return $this->get('card_last4')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCardLast4($last4) {
    $this->set('card_last4', $last4);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCardBrand() {
    return $this->get('card_brand')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCardBrand($brand) {
    $this->set('card_brand', $brand);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCardFunding() {
    return $this->get('card_funding')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCardFunding($funding) {
    $this->set('card_funding', $funding);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressLine1() {
    $this->get('address_line1');
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressLine1($line) {
    $this->set('address_line1', $line);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressLine2() {
    $this->get('address_line2');
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressLine2($line) {
    $this->set('address_line2', $line);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressCity() {
    $this->get('address_city');
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressCity($city) {
    $this->set('address_city', $city);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressState() {
    $this->get('address_state');
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressState($state) {
    $this->set('address_state', $state);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressZip() {
    $this->get('address_zip');
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressZip($zip) {
    $this->set('address_zip', $zip);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressCountry() {
    $this->get('address_country');
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressCountry($country) {
    $this->set('address_country', $country);
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
   * Set card info.
   *
   * Helper function to set card last four, brand, and funding source from a
   * GiveStripe entity.
   *
   * @param \Drupal\give\GiveStripeInterface $give_stripe
   */
  public function setCardInfo($give_stripe) {
    $charge = $give_stripe->charge;
    $funding = $charge->source->funding;
    $brand = $charge->source->brand;
    $last4 = $charge->source->last4;
    $this->setCardFunding($funding);
    $this->setCardBrand($brand);
    $return = $this->setCardLast4($last4);
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
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => 2,
      ]);

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

    $fields['amount'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Donation amount'))
      ->setDescription('The amount of the donation, in cents USD.')
      ->setRequired(TRUE);

    $fields['recurring'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Recurring'))
      ->setDescription(t('The interval counts (in number of months) at which the donation should recur, or negative one if not recurring.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created time'))
      ->setDescription(t('The time that the donation was created.'));

    $fields['method'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Method'))
      ->setDescription(t('The donation method (payment card, check pledge).'));

    $fields['telephone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Phone'))
      ->setDescription(t('The telephone number of the donor.'))
      ->setSetting('max_length', 20);

    $fields['check_or_other_information'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Further information'))
      ->setDescription(t('Any questions or explain anything needed to arrange for giving donation.'))
      ->setSetting('max_length', 2000);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the donation was last edited.'));

    $fields['address_line1'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Address line 1'))
      ->setDescription(t('The street address or PO Box of the donor; used in billing address.'));

    $fields['address_line2'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Address line 2'))
      ->setDescription(t('Optional apartment/suite/unit of the donor; used in billing address.'))
      ->setSetting('max_length', 100);

    $fields['address_city'] = BaseFieldDefinition::create('string')
      ->setLabel(t('City or district'))
      ->setDescription(t('The town of the donor; used in billing address.'))
      ->setSetting('max_length', 100);

    $fields['address_zip'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Postal code'))
      ->setDescription(t('ZIP or postal code of the donor; used in billing address.'))
      ->setSetting('max_length', 100);

    $fields['address_state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('State or province'))
      ->setDescription(t('The state/province/region of the donor; used in billing address.'))
      ->setSetting('max_length', 100);

    $fields['address_country'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Country'))
      ->setDescription(t('The country the donor; used in billing address.'))
      ->setSetting('max_length', 100);

    $fields['complete'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Completed donation'))
      ->setDefaultValue(FALSE);

    $fields['stripe_token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe token'))
      ->setDescription(t('The token returned by Stripe used to tell Stripe to process the donation.'))
      ->setSetting('max_length', 56);

    $fields['card_brand'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Card brand'))
      ->setDescription(t('The card brand (Visa, MasterCard, etc).'))
      ->setSetting('max_length', 30);

    $fields['card_funding'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Card funding'))
      ->setDescription(t('The card funding type (credit, debit).'))
      ->setSetting('max_length', 30);

    $fields['card_last4'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Last four'))
      ->setDescription(t('The last four digits of the credit/debit card, if applicable.'));

    // Set all fields to be configurable and to have explicit weights in order.
    $weight = -10;
    foreach ($fields as &$field) {
      $field->setDisplayConfigurable('form', TRUE);
      $field->setDisplayOptions('view', ['weight' => $weight]);
      $weight++;
    }

    return $fields;
  }

}
