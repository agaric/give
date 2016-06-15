<?php

namespace Drupal\give;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for give donation forms.
 */
class PaymentForm extends ContentEntityForm {

  /**
   * The donation being used by this form.
   *
   * @var \Drupal\give\DonationInterface
   */
  protected $entity;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The give mail handler service.
   *
   * @var \Drupal\give\MailHandlerInterface
   */
  protected $mailHandler;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a DonationForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood control mechanism.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\give\MailHandlerInterface $mail_handler
   *   The give mail handler service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date service.
   */
  public function __construct(EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, MailHandlerInterface $mail_handler, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_manager);
    $this->languageManager = $language_manager;
    $this->mailHandler = $mail_handler;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('give.mail_handler'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $donation = $this->entity;
    $form = parent::form($form, $form_state, $donation);
    $form['#attributes']['class'][] = 'give-form';

    $form['method'] = array(
      '#type' => 'radios',
      '#title' => t('Choose donation method'),
      '#options' => array(
        GIVE_WITH_STRIPE => $this->t('By credit/debit card'),
        GIVE_WITH_DWOLLA => $this->t('By bank transfer'),
      ),
      '#weight' => 0,
    );

    $form['stripe_errors'] = array(
      '#markup' => '<span class="payment-errors"></span>',
      '#weight' => 10,
    );

    $form['number'] = array(
      '#type' => 'item',
      '#title' => t('Card number'),
      '#required' => TRUE,
      '#markup' => '<input data-drupal-selector="edit-number" id="edit-number" value="" size="20" maxlength="20" class="form-text" type="text" data-stripe="number">',
      '#allowed_tags' => ['input'],
    );

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Your name'),
      '#value' => $donation->getDonorName(),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#disabled' => TRUE,
    );
    $form['mail'] = array(
      '#type' => 'item',
      '#value' => $donation->getDonorMail(),
      '#required' => FALSE,
      '#plain_text' => $donation->getDonorMail(),
    );
    $form['amount'] = array(
      '#type' => 'item',
      '#value' => $donation->getAmount(),
      '#title' => $this->t('Amount you pledged'),
      '#plain_text' => $donation->getDollarAmount(),
    );
    $form['recurring'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Give this same donation every month'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $elements = parent::actions($form, $form_state);
    $elements['submit']['#value'] = $this->t('Give');
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\give\DonationInterface $donation */
    $donation = parent::buildEntity($form, $form_state);
    $donation->setUpdatedTime(REQUEST_TIME);

    return $donation;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $donation = parent::validateForm($form, $form_state);

    return $donation;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $donation = $this->entity;

    $user = $this->currentUser();
    $this->mailHandler->sendDonationNotice($donation, $user);
    drupal_set_message($this->t('Your donation has been sent.'));

    // Save the donation. In core this is a no-op but should contrib wish to
    // implement donation storage, this will make the task of swapping in a real
    // storage controller straight-forward.
    $donation->save();
  }

}
