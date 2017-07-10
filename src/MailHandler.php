<?php

namespace Drupal\give;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides a class for handling assembly and dispatch of give form notices.
 */
class MailHandler implements MailHandlerInterface {

  use StringTranslationTrait;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The user entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a new \Drupal\give\MailHandler object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   String translation service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity manager service.
   */
  public function __construct(MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, LoggerInterface $logger, TranslationInterface $string_translation, EntityTypeManagerInterface $entity_type_manager) {
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
    $this->logger = $logger;
    $this->stringTranslation = $string_translation;
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public function sendDonationNotice(DonationInterface $donation, AccountInterface $donor) {
    // Clone the donor, as we make changes to mail and name properties.
    $donor_cloned = clone $this->userStorage->load($donor->id());
    $params = array();
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();
    $recipient_langcode = $this->languageManager->getDefaultLanguage()->getId();
    $give_form = $donation->getGiveForm();

    if ($donor_cloned->isAnonymous()) {
      // At this point, $donor contains an anonymous user, so we need to take
      // over the submitted form values.
      $donor_cloned->name = $donation->getDonorName();
      $donor_cloned->mail = $donation->getDonorMail();

      // For the email message, clarify that the donor name is not verified; it
      // could potentially clash with a username on this site.
      $donor_cloned->name = $this->t('@name (not verified)', array('@name' => $donation->getDonorName()));
    }

    // Build email parameters.
    $params['give_donation'] = $donation;
    $params['donor'] = $donor_cloned;

    // Send to the form recipient(s), using the site's default language.
    $params['give_form'] = $give_form;

    $to = implode(', ', $give_form->getRecipients());

    // Send email to the configured recipient(s) (usually admin users).
    $this->mailManager->mail('give', 'donation_notice', $to, $recipient_langcode, $params, $donor_cloned->getEmail());

    // If configured, send auto-reply receipt to donor, using current language.
    if ($give_form->getReply()) {
      $this->mailManager->mail('give', 'donation_receipt', $donor_cloned->getEmail(), $current_langcode, $params);
    }

    $this->logger->notice('%donor-name (@donor-from) gave via %give_form.', array(
      '%donor-name' => $donor_cloned->getUsername(),
      '@donor-from' => $donor_cloned->getEmail(),
      '%give_form' => $give_form->label(),
    ));
  }

}
