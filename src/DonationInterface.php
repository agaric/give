<?php

namespace Drupal\give;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a give donation entity.
 */
interface DonationInterface extends ContentEntityInterface {

  /**
   * Returns the form this give donation belongs to.
   *
   * @return \Drupal\give\GiveFormInterface
   *   The give form entity.
   */
  public function getGiveForm();

  /**
   * Returns the name of the donor.
   *
   * @return string
   *   The name of the donation donor.
   */
  public function getDonorName();

  /**
   * Sets the name of the donation donor.
   *
   * @param string $donor_name
   *   The name of the donation donor.
   */
  public function setDonorName($donor_name);

  /**
   * Returns the email address of the donor.
   *
   * @return string
   *   The email address of the donation donor.
   */
  public function getDonorMail();

  /**
   * Sets the email address of the donor.
   *
   * @param string $donor_mail
   *   The email address of the donation donor.
   */
  public function setDonorMail($donor_mail);

  /**
   * Returns the donation label.
   *
   * @return string
   *   The donation label.
   */
  public function getLabel();

  /**
   * Sets the label for the donation.
   *
   * Set automatically.  See DonationForm::buildEntity().
   *
   * @param string $label
   *   The donation label.
   */
  public function setLabel($subject);

  /**
   * Returns the donation amount.
   *
   * @return integer
   *   The donation body.
   */
  public function getAmount();

  /**
   * Sets the email donation amount.
   *
   * @param string $donation
   *   The donation amount in USD cents.
   */
  public function setAmount($amount);

  /**
   * Returns TRUE if the donation should recur monthly.
   *
   * @return bool
   *   TRUE if the donation should recur monthly, FALSE if not.
   */
  public function recurring();

  /**
   * Sets if the donor should receive a copy of this email or not.
   *
   * @param bool $recur
   *   TRUE if a copy should be sent, FALSE if not.
   */
  public function setRecurring($recur);

}

  /**
   * Returns the user this donation is being sent to.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity of the recipient, NULL if this is not a personal donation.
   */
  public function getPersonalRecipient();