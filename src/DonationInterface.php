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
   * Set automatically.
   */
  public function setLabel();

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
   * Returns TRUE if the donation should recur.
   *
   * @return bool
   *   TRUE if the donation should recur monthly, FALSE if not.
   */
  public function recurring();

  /**
   * Recurrence is the time between donations made up of the interval count and the interval unit.
   */
  public function getRecurrence();

  /**
   * Sets the intervals to elapse between donations.
   *
   * @param string interval
   *   The interval unit used to describe how much time should elapse between
   *   donations. Currently the interval is hard-coded to month.
   *
   * @deprecated the recurrence unit only can be set in the give_form entity.
   */
  public function setRecurrenceIntervalUnit($interval);

  /**
   * Returns the interval used to define time to elapse between donations.
   *
   * @return integer $count
   *   The interval unit used to describe how much time should elapse between
   *   donations. Currently the interval is hard-coded to month.
   */
  public function getRecurrenceIntervalUnit();

  /**
   * Sets the number of intervals to elapse between donations.
   *
   * @param integer $count
   *   The number of intervals which should elapse between donations. Currently
   *   the interval is hard-coded to month, so a value of 1 is monthly, 3 is
   *   quarterly, and so on.
   *
   * @deprecated The recurrence only can be set in the give_form not in the
   *  donation.
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
   * Returns a product name based on currency, amount, interval, and interval count.
   *
   * Formerly this was a plan name, before Stripe changed their API:
   * https://stripe.com/docs/upgrades#2018-02-05
   *
   * Note that interval count is the number of intervals between donations, not
   * the number of times the payment should be made.  Recurring payments go on
   * forever.
   *
   * @return string
   */
  public function getProductName();

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
   * Sets the method the donor chose to use to make a donation.
   *
   * @param int $method
   *   The constant defining the donation method.
   *
   * @return \Drupal\give\DonationInterface
   *   The called donation entity.
   */
  public function setMethod($method);

  /**
   * Gets the method the donor chose to use to make a donation.
   *
   * @return int
   *   The constant defining the donation method.
   */
  public function getMethod();

  /**
   * Gets the short human-readable text name version of the donation method.
   *
   * @return string
   *   The text string corresponding to donation's integer (constant) method.
   */
  public function getMethodName();

  /**
   * Gets the long human-readable text name version of the donation method.
   *
   * @return string
   *   The text string corresponding to donation's integer (constant) method.
   */
  public function getMethodLongName();

  /**
   * Gets a string indication of the type of reply that should be sent.
   *
   * Corresponds to:
   *
   *     "subject", (onetime)
   *     "reply",
   *     "subject_recurring",
   *     "reply_recurring",
   *     "subject_pledge",
   *     "reply_pledge",
   *
   * @return string
   *   A text string corresponding to the reply/subject pair.
   */
  public function getReplyType();

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
   * Returns the payment card last four digits for the donation (if paid by card).
   *
   * @return integer
   *   The payment card last four digits for the donation (if paid by card).
   */
  public function getCardLast4();

  /**
   * Sets the payment card last four digits for the donation (if paid by card).
   *
   * @param integer $last4
   *   The payment card last four digits for the donation (if paid by card).
   */
  public function setCardLast4($last4);

  /**
   * Returns the payment card brand for the donation (if paid by card).
   *
   * For example Visa, MasterCard, etc.
   *
   * @return string
   *   The payment card brand used for the donation (if paid by card).
   */
  public function getCardBrand();

  /**
   * Sets the payment card brand used for the donation (if paid by card).
   *
   * For example Visa, MasterCard, etc.
   *
   * @param string $brand
   *   The payment card brand used for the donation (if paid by card).
   */
  public function setCardBrand($brand);

  /**
   * Returns the payment card funding type (credit, debit) (if paid by card).
   *
   * @return string
   *   The payment card funding type (credit, debit) used (if paid by card).
   */
  public function getCardFunding();

  /**
   * Sets the payment card funding type (credit, debit) used (if paid by card).
   *
   * @param string $funding
   *   The payment card funding type used for the donation (if paid by card).
   */
  public function setCardFunding($funding);

  /**
   * Gets line one of the donor address.
   */
  public function getAddressLine1();

  /**
   * Sets line one of the donor address.
   */
  public function setAddressLine1($line);

  /**
   * Gets line two of the donor address.
   */
  public function getAddressLine2();

  /**
   * Sets line two of the donor address.
   */
  public function setAddressLine2($line);

  /**
   * Gets the city, town, or locality of the donor address.
   */
  public function getAddressCity();

  /**
   * Sets the city, town, or locality of the donor address.
   */
  public function setAddressCity($city);

  /**
   * Gets the state or province of the donor address.
   */
  public function getAddressState();

  /**
   * Sets the state or province of the donor address.
   */
  public function setAddressState($state);

  /**
   * Gets the ZIP or postal code of the donor address.
   */
  public function getAddressZip();

  /**
   * Sets the ZIP or postal code of the donor address.
   */
  public function setAddressZip($zip);

  /**
   * Gets the country of the donor address.
   */
  public function getAddressCountry();

  /**
   * Sets the country of the donor address.
   */
  public function setAddressCountry($country);

  /**
   *
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
