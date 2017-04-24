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
  public function setLabel($label);

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
   * @param string $amount
   *   The donation amount in USD cents.
   */
  public function setAmount($amount);

  /**
   * Convert the user-facing amount unit (dollars) into stored unit (cents).
   *
   * @param string $dollar_amount
   *   THe donation amount with the format "$20.00"
   */
  public function setDollarAmount($dollar_amount);

  /**
   * Display the stored amount (in cents) as user-facing dollars.
   */
  public function getDollarAmount();

  /**
   * Returns TRUE if the donation should recur monthly.
   *
   * @return bool
   *   TRUE if the donation should recur monthly, FALSE if not.
   */
  public function recurring();

  /**
   * Sets the number of intervals to elapse between donations.
   *
   * @param integer $count
   *   The number of intervals which should elapse between donations. Currently
   *   the interval is hard-coded to month, so a value of 1 is monthly, 3 is
   *   quarterly, and so on.
   */
  public function setRecurrenceIntervalCount($count);

  /**
   * Returns the number of intervals to elapse between donations.
   *
   * @return integer $count
   *   The number of intervals which should elapse between donations. Currently
   *   the interval is hard-coded to month, so a value of 1 is monthly, 3 is
   *   quarterly, and so on.
   */
  public function getRecurrenceIntervalCount();

  /**
   * Returns a plan ID based on currency, amount, interval, and interval count.
   *
   * Note that interval count is the number of intervals between donations, not
   * the number of times the payment should be made.  Recurring payments go on
   * forever.
   */
  public function getPlanId();

  /**
   * Gets the donation creation timestamp.
   *
   * @return int
   *   Creation timestamp of the donation.
   */
  public function getCreatedTime();

  /**
   * Sets the donation creation timestamp.
   *
   * @param int $timestamp
   *   The donation creation timestamp.
   *
   * @return \Drupal\give\DonationInterface
   *   The called donation entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the donation updated timestamp.
   *
   * @return int
   *   Update timestamp of the donation.
   */
  public function getUpdatedTime();

  /**
   * Sets the donation updated timestamp.
   *
   * @param int $timestamp
   *   The donation update timestamp.
   *
   * @return \Drupal\give\DonationInterface
   *   The called donation entity.
   */
  public function setUpdatedTime($timestamp);

  /**
   * Returns the Stripe token for the donation.
   *
   * @return string
   *   The token returned by Stripe used to tell Stripe to process the donation.
   */
  public function getStripeToken();

  /**
   * Sets the Stripe token for the donation.
   *
   * @param string $token
   *   The token returned by Stripe used to tell Stripe to process the donation.
   */
  public function setStripeToken($token);

  /**
   * Returns the donation completed status.
   *
   * @return bool
   *   TRUE if the donation is completed.
   */
  public function isCompleted();

  /**
   * Sets the node promoted status.
   *
   * @param bool $completed (optional)
   *   TRUE (default) to set this donation to completed, FALSE to set it to not completed.
   *
   * @return \Drupal\give\DonationInterface
   *   The called donation entity.
   */
  public function setCompleted($completed = TRUE);

}
