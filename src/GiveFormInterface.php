<?php

namespace Drupal\give;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a give form entity.
 */
interface GiveFormInterface extends ConfigEntityInterface {

  /**
   * Get the plans supported by this donation.
   *
   * @return array
   *   The Plans available for this donation form.
   */
  public function getFrequency();

  /**
   * Set the plans supported by this donation.
   *
   * @param array $frequency
   *   The plans supported by this donation.
   *
   * @return $this
   *   Return the entity.
   */
  public function setFrequency(array $frequency);

  /**
   * Returns list of recipient email addresses.
   *
   * @return array
   *   List of recipient email addresses.
   */
  public function getRecipients();

  /**
   * Returns an automatic subject for the donation receipt e-mail.
   *
   * @return string
   *   Subject for the donation receipt e-mail.
   */
  public function getSubject();

  /**
   * Returns an automatic reply to be sent with donation receipt to the donor.
   *
   * @return string
   *   Text to be sent with the donation receipt.
   */
  public function getReply();

  /**
   * Returns an optional message to show potential givers who select the "Check
   * or other" donation method.
   *
   * @return string
   *  Text to show for check or other.
   */
  public function getCheckOrOtherText();

  /**
   * Returns the weight of this category (used for sorting).
   *
   * @return int
   *   The weight of this category.
   */
  public function getWeight();

  /**
   * Sets list of recipient email addresses.
   *
   * @param array $recipients
   *   The desired list of email addresses of this category.
   *
   * @return $this
   */
  public function setRecipients($recipients);

  /**
   * Sets an optional message to show potential givers who select the "Check
   * or other" donation method.
   *
   * @return string
   *  Text to show for check or other.
   */
  public function setCheckOrOtherText($text);

  /**
   * Sets an automatic subject for the donation receipt e-mail to the donor.
   *
   * @param string $subject
   *   The desired subject for the donation receipt e-mail.
   *
   * @return $this
   */
  public function setSubject($subject);

  /**
   * Sets an automatic reply to be sent with donation receipt to the donor.
   *
   * @param string $reply
   *   The desired reply to be sent with the donation receipt.
   *
   * @return $this
   */
  public function setReply($reply);

  /**
   * Sets the weight.
   *
   * @param int $weight
   *   The desired weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Gets the uri where the user will be redirected after the donate.
   */
  public function getRedirectUri();

  /**
   * Sets the uri where the user will be redirected after to donate.
   *
   * @param string $uri
   *   The uri.
   *
   * @return $this
   */
  public function setRedirectUri($uri);

  /**
   * The text displayed in the submit button in the donation form.
   */
  public function getSubmitText();

  /**
   * Set the text displayed in the submit button in the donation form.
   *
   * @param string $text
   *   The text displayed in the submit button.
   *
   * @return $this
   */
  public function setSubmitText($text);

}
