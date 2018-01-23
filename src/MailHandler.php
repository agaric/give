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
  public function sendDonationNotices(DonationInterface $donation, AccountInterface $donor) {
    // Clone the donor, as we make changes to mail and name properties.
    $donor_cloned = clone $this->userStorage->load($donor->id());

    if ($donor_cloned->isAnonymous()) {
      // At this point, $donor contains an anonymous user, so we need to take
      // over the submitted form values.
      $donor_cloned->name = $donation->getDonorName();
      $donor_cloned->mail = $donation->getDonorMail();

      // For the email message, clarify that the donor name is not verified; it
      // could potentially clash with a username on this site.
      $donor_cloned->name = $this->t('@name (not verified)', ['@name' => $donation->getDonorName()]);
    }

    $this->sendDonationNotice($donation, $donor_cloned);
    // If configured, send auto-reply receipt to donor, using current language.
    if ($give_form->get('autoreply')) {
      $this->sendDonationReceipt($donation, $donor_cloned);
    }

    // Probably doesn't belong here but we have a logger so away we go.
    $this->logger->notice('%donor-name (@donor-from) gave via %give_form.', [
      '%donor-name' => $donor_cloned->getUsername(),
      '@donor-from' => $donor_cloned->getEmail(),
      '%give_form' => $give_form->label(),
    ]);
  }

  /**
   * Send donation notice to the form recipient(s), using the site's default language.
   */
  private function sendDonationNotice(DonationInterface $donation, AccountInterface $donor) {
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    $give_form = $donation->getGiveForm();
    // Build email parameters.
    $params = [];
    $params['give_donation'] = $donation;
    $params['donor'] = $donor_cloned;

    $params['give_form'] = $give_form;

    $to = implode(', ', $give_form->getRecipients());

    // Send email to the configured recipient(s) (usually admin users).
    $this->mailManager->mail('give', 'donation_notice', $to, $default_langcode, $params, $donor_cloned->getEmail());
  }

  /**
   * Send appropriate donation receipt to donor, using the current language.
   */
  private function sendDonationReceipt(DonationInterface $donation, AccountInterface $donor) {
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();
    $give_form = $donation->getGiveForm();
    // Build email parameters.
    $params = [];
    $params['give_donation'] = $donation;
    $params['donor'] = $donor_cloned;

    $params['give_form'] = $give_form;

    $to = implode(', ', $give_form->getRecipients());

    switch ($donation->getReplyType()) {
      case 'onetime':
        $params['reply'] = $give_form->get('reply');
        $params['subject'] = $give_form->get('subject');
        break;
      case 'recurring':
        $params['reply'] = $give_form->get('reply_recurring');
        $params['subject'] = $give_form->get('subject_recurring');
        break;
      case 'pledge':
        $params['reply'] = $give_form->get('reply_pledge');
        $params['subject'] = $give_form->get('subject_pledge');
        break;
      default:
        $this->logger->notice('Unknown reply type %type triggered for %donor-name (@donor-from) via %give_form; no message sent.', [
          '%donor-name' => $donor_cloned->getUsername(),
          '@donor-from' => $donor_cloned->getEmail(),
          '%give_form' => $give_form->label(),
          '%type' => $donation->getReplyType(),
        ]);
        return;
    }
    $this->mailManager->mail('give', 'donation_receipt', $donor_cloned->getEmail(), $current_langcode, $params);
    drupal_set_message($this->t("We have e-mailed a receipt to <em>:mail</em>.", [':mail' => $donation->getDonorMail()]));

  }

  /**
   * {@inheritdoc}
   */
  public function makeDonationReceiptPreview(DonationInterface $donation, AccountInterface $donor) {
    // Clone the donor, as we make changes to mail and name properties.
    $donor_cloned = clone $this->userStorage->load($donor->id());
    $params = [];
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    $give_form = $donation->getGiveForm();

    $donor_cloned->name = $donation->getDonorName();
    $donor_cloned->mail = $donation->getDonorMail();

    // For the email message, clarify that the donor name is not verified; it
    // could potentially clash with a username on this site.
    $donor_cloned->name = $this->t('@name (not verified)', ['@name' => $donation->getDonorName()]);

    // Build email parameters.
    $params['give_donation'] = $donation;
    $params['donor'] = $donor_cloned;

    // Send to the form recipient(s), using the site's default language.
    $params['give_form'] = $give_form;

    $to = implode(', ', $give_form->getRecipients());

    // Send email to the configured recipient(s) (usually admin users).
    $admin_notice = $this->mailManager->doMail('give', 'donation_notice', $to, $default_langcode, $params, $donor_cloned->getEmail(), FALSE);

    // If configured, send auto-reply receipt to donor, using current language.
    if ($give_form->get('autoreply')) {
      $receipt_card = $this->mailManager->doMail('give', 'donation_receipt', $donor_cloned->getEmail(), $current_langcode, $params, NULL, FALSE);
    }

    return [
      'receipt_card' => $receipt_card,
      'admin_notice' => $admin_notice,
    ];

  }

}
