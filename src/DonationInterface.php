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
   * Returns the name of the sender.
   *
   * @return string
   *   The name of the donation sender.
   */
  public function getSenderName();

  /**
   * Sets the name of the donation sender.
   *
   * @param string $sender_name
   *   The name of the donation sender.
   */
  public function setSenderName($sender_name);

  /**
   * Returns the email address of the sender.
   *
   * @return string
   *   The email address of the donation sender.
   */
  public function getSenderMail();

  /**
   * Sets the email address of the sender.
   *
   * @param string $sender_mail
   *   The email address of the donation sender.
   */
  public function setSenderMail($sender_mail);

  /**
   * Returns the donation subject.
   *
   * @return string
   *   The donation subject.
   */
  public function getSubject();

  /**
   * Sets the subject for the email.
   *
   * @param string $subject
   *   The donation subject.
   */
  public function setSubject($subject);

  /**
   * Returns the donation body.
   *
   * @return string
   *   The donation body.
   */
  public function getDonation();

  /**
   * Sets the email donation to send.
   *
   * @param string $donation
   *   The donation body.
   */
  public function setDonation($donation);

  /**
   * Returns TRUE if a copy should be sent to the sender.
   *
   * @return bool
   *   TRUE if a copy should be sent, FALSE if not.
   */
  public function copySender();

  /**
   * Sets if the sender should receive a copy of this email or not.
   *
   * @param bool $inform
   *   TRUE if a copy should be sent, FALSE if not.
   */
  public function setCopySender($inform);

  /**
   * Returns TRUE if this is the personal give form.
   *
   * @return bool
   *   TRUE if the donation bundle is personal.
   */
  public function isPersonal();

  /**
   * Returns the user this donation is being sent to.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity of the recipient, NULL if this is not a personal donation.
   */
  public function getPersonalRecipient();

}
