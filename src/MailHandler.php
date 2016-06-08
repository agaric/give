<?php

namespace Drupal\give;

use Drupal\Core\Entity\EntityManagerInterface;
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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   Entity manager service.
   */
  public function __construct(MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, LoggerInterface $logger, TranslationInterface $string_translation, EntityManagerInterface $entity_manager) {
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
    $this->logger = $logger;
    $this->stringTranslation = $string_translation;
    $this->userStorage = $entity_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public function sendMailMessages(MessageInterface $message, AccountInterface $sender) {
    // Clone the sender, as we make changes to mail and name properties.
    $sender_cloned = clone $this->userStorage->load($sender->id());
    $params = array();
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();
    $recipient_langcode = $this->languageManager->getDefaultLanguage()->getId();
    $give_form = $message->getGiveForm();

    if ($sender_cloned->isAnonymous()) {
      // At this point, $sender contains an anonymous user, so we need to take
      // over the submitted form values.
      $sender_cloned->name = $message->getSenderName();
      $sender_cloned->mail = $message->getSenderMail();

      // For the email message, clarify that the sender name is not verified; it
      // could potentially clash with a username on this site.
      $sender_cloned->name = $this->t('@name (not verified)', array('@name' => $message->getSenderName()));
    }

    // Build email parameters.
    $params['give_message'] = $message;
    $params['sender'] = $sender_cloned;

    // Send to the form recipient(s), using the site's default language.
    $params['give_form'] = $give_form;

    $to = implode(', ', $give_form->getRecipients());

    // Send email to the recipient(s).
    $this->mailManager->mail('give', 'form_mail', $to, $recipient_langcode, $params, $sender_cloned->getEmail());

    // If configured, send an auto-reply receipt, using the current language.
    if ($give_form->getReply()) {
      $this->mailManager->mail('give', 'donation_receipt', $sender_cloned->getEmail(), $current_langcode, $params);
    }

    $this->logger->notice('%sender-name (@sender-from) gave via %give_form.', array(
      '%sender-name' => $sender_cloned->getUsername(),
      '@sender-from' => $sender_cloned->getEmail(),
      '%give_form' => $give_form->label(),
    ));
  }

}
