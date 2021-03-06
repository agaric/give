<?php

namespace Drupal\give\Form\Donation;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\give\MailHandlerInterface;
use Drupal\Core\Url;

/**
 * Form controller for give donation forms.
 */
class DonationForm extends ContentEntityForm {

  /**
   * The donation being used by this form.
   *
   * @var \Drupal\give\DonationInterface
   */
  protected $entity;

  /**
   * The flood control mechanism.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
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
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FloodInterface $flood, LanguageManagerInterface $language_manager, MailHandlerInterface $mail_handler, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type_manager);
    $this->flood = $flood;
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
      $container->get('flood'),
      $container->get('language_manager'),
      $container->get('give.mail_handler'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $user = $this->currentUser();
    $donation = $this->entity;
    $form = parent::form($form, $form_state, $donation);
    $form['#attributes']['class'][] = 'give-form';

    if (!empty($donation->preview)) {
      $form['preview'] = [
        '#theme_wrappers' => ['container__preview'],
        '#attributes' => ['class' => ['preview']],
      ];
      $form['preview']['donation'] = $this->entityTypeManager->getViewBuilder('give_donation')->view($donation, 'full');
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your name'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email address'),
      '#required' => TRUE,
    ];
    if ($user->isAnonymous()) {
      $form['#attached']['library'][] = 'core/drupal.form';
      $form['#attributes']['data-user-info-from-browser'] = TRUE;
    }
    // Do not allow authenticated users to alter the name or email values to
    // prevent the impersonation of other users.
    else {
      $form['name']['#type'] = 'item';
      $form['name']['#value'] = $user->getDisplayName();
      $form['name']['#required'] = FALSE;
      $form['name']['#plain_text'] = $user->getDisplayName();

      $form['mail']['#type'] = 'item';
      $form['mail']['#value'] = $user->getEmail();
      $form['mail']['#required'] = FALSE;
      $form['mail']['#plain_text'] = $user->getEmail();
    }

    $form['amount'] = [
      '#type' => 'number',
      '#step' => .01,
      '#min' => 2.5,
      '#max' => 1000000,
      '#title' => $this->t('Amount to give'),
      '#field_prefix' => $this->t('$'),
      '#required' => TRUE,
    ];

    $give_form = $this->getBundleEntity();
    $options = [-1 => 'No, just once'];
    foreach ($give_form->getFrequencies() as $key => $option) {
      $options[$key] = $option['description'];
    }
    $form['recurring'] = [
      '#title' => $this->t('Make this a recurring donation:'),
      '#type' => 'radios',
      '#options' => $options,
      '#default_value' => '-1',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\give\Entity\Donation $donation */
    $donation = $this->entity;
    /** @var \Drupal\give\Entity\GiveForm $giveForm */
    $giveForm = $donation->referencedEntities()[0];
    $elements = parent::actions($form, $form_state);
    $elements['submit']['#value'] = $this->t($giveForm->getSubmitText());
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\give\DonationInterface $donation */
    $donation = parent::buildEntity($form, $form_state);
    if (!$form_state->isValueEmpty('date') && $form_state->getValue('date') instanceof DrupalDateTime) {
      $donation->setCreatedTime($form_state->getValue('date')->getTimestamp());
    }
    else {
      $donation->setCreatedTime(REQUEST_TIME);
    }

    // Convert the form unit (dollars) into stored unit (cents) for amount.
    $donation->setDollarAmount($form_state->getValue('amount'));

    $donation->setLabel();

    return $donation;
  }

  /**
   * Form submission handler for the 'preview' action.
   */
  public function preview(array $form, FormStateInterface $form_state) {
    $donation = $this->entity;
    $donation->preview = TRUE;
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $donation = parent::validateForm($form, $form_state);

    // Check if flood control has been activated for sending donations.
    // If flood isn't configured at all, fall back on defaults.
    if (!$this->currentUser()->hasPermission('administer give forms')) {
      $limit = $this->config('give.settings')->get('flood.limit') ?: 50;
      $interval = $this->config('give.settings')->get('flood.interval') ?: 3600;

      if (!$this->flood->isAllowed('give', $limit, $interval)) {
        $form_state->setErrorByName('', $this->t('You cannot send more than %limit donations in @interval. Try again later.', [
          '%limit' => $limit,
          '@interval' => $this->dateFormatter->formatInterval($interval),
        ]));
      }
    }

    return $donation;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $donation = $this->entity;

    $this->flood->register('give', $this->config('give.settings')->get('flood.interval'));

    if ($donation->save() == SAVED_NEW) {
      // Redirect to the second step.
      $form_state->setRedirectUrl(Url::fromRoute('entity.give_form.donate', ['give_form' => $donation->get('give_form')->target_id, 'give_donation' => $donation->id()]));
    }
  }

}
