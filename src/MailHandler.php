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
    if ($donation->getGiveForm()->get('autoreply')) {
      $this->sendDonationReceipt($donation, $donor_cloned);
    }

    // Probably doesn't belong here but we have a logger so away we go.
    $this->logger->notice('%donor-name (@donor-from) gave via %give_form.', [
      '%donor-name' => $donor_cloned->getDisplayName(),
      '@donor-from' => $donor_cloned->getEmail(),
      '%give_form' => $donation->getGiveForm()->label(),
    ]);
  }

  /**
   * Send donation notice to the form recipient(s), using the site's default language.
   */
  private function sendDonationNotice(DonationInterface $donation, AccountInterface $donor_cloned) {
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
  private function sendDonationReceipt(DonationInterface $donation, AccountInterface $donor_cloned) {
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();
    $give_form = $donation->getGiveForm();
    // Build email parameters.
    $params = [];
    $params['give_donation'] = $donation;
    $params['donor'] = $donor_cloned;

    $params['give_form'] = $give_form;

    $to = implode(', ', $give_form->getRecipients());

    $this->mailManager->mail('give', 'donation_receipt', $donor_cloned->getEmail(), $current_langcode, $params);

    drupal_set_message($this->t("We have e-mailed a receipt to <em>:mail</em>.", [':mail' => $donation->getDonorMail()]));
  }

  /**
   * Make previews for the donation notice and donation receipts.
   */
  public function makeDonationReceiptPreviews($give_form, $entity_type_manager) {
    $previews = [];

    // DonationInterface
    $donation = $entity_type_manager
      ->getStorage('give_donation')
      ->create([
        'give_form' => $give_form->id(),
      ]);
    $donation->setAmount(12300);
    $donation->set('recurring', -1);
    $donation->setMethod(GIVE_WITH_STRIPE);
    $donation->setDonorName('Bud Philanthropist');
    $donation->setDonorMail('bud@example.com');
    $donation->setAddressLine1('1980 Nebraska Ave');
    $donation->setAddressCity('Los Angeles');
    $donation->setAddressState('CA');
    $donation->setAddressCountry('United States');
    $donation->setCardFunding('credit');
    $donation->setCardBrand('Visa');
    $donation->setCardLast4(9876);
    $donation->setLabel();
    $donation->setCompleted();

    // Clone the donor, as we make changes to mail and name properties.
    $donor_cloned = clone $this->userStorage->load(0);
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

    // Preview auto-reply receipts to donor, using current language.
    $previews['receipt_card'] = $this->mailManager->doMail('give', 'donation_receipt', $donor_cloned->getEmail(), $current_langcode, $params, NULL, FALSE);

    $params['give_donation']->set('recurring', 1);
    $previews['receipt_card_recurring'] = $this->mailManager->doMail('give', 'donation_receipt', $donor_cloned->getEmail(), $current_langcode, $params, NULL, FALSE);

    $donation->setMethod(GIVE_WITH_CHECK);
    $params['give_donation']->set('recurring', -1);
    // Unset completed which isn't set for checks.
    $params['give_donation']->set('complete', FALSE);
    $previews['receipt_check'] = $this->mailManager->doMail('give', 'donation_receipt', $donor_cloned->getEmail(), $current_langcode, $params, NULL, FALSE);

    // Preview email to the configured recipient(s) (usually admin users).
    $previews['admin_notice'] = $this->mailManager->doMail('give', 'donation_notice', $to, $default_langcode, $params, $donor_cloned->getEmail(), FALSE);


    return $previews;

  }

}
