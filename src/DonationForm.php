<?php

namespace Drupal\give;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
  public function __construct(EntityManagerInterface $entity_manager, FloodInterface $flood, LanguageManagerInterface $language_manager, MailHandlerInterface $mail_handler, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_manager);
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
      $form['preview'] = array(
        '#theme_wrappers' => array('container__preview'),
        '#attributes' => array('class' => array('preview')),
      );
      $form['preview']['donation'] = $this->entityManager->getViewBuilder('give_donation')->view($donation, 'full');
    }

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Your name'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['mail'] = array(
      '#type' => 'email',
      '#title' => $this->t('Your email address'),
      '#required' => TRUE,
    );
    if ($user->isAnonymous()) {
      $form['#attached']['library'][] = 'core/drupal.form';
      $form['#attributes']['data-user-info-from-browser'] = TRUE;
    }
    // Do not allow authenticated users to alter the name or email values to
    // prevent the impersonation of other users.
    else {
      $form['name']['#type'] = 'item';
      $form['name']['#value'] = $user->getUsername();
      $form['name']['#required'] = FALSE;
      $form['name']['#plain_text'] = $user->getUsername();

      $form['mail']['#type'] = 'item';
      $form['mail']['#value'] = $user->getEmail();
      $form['mail']['#required'] = FALSE;
      $form['mail']['#plain_text'] = $user->getEmail();
    }

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
    if (!$form_state->isValueEmpty('date') && $form_state->getValue('date') instanceof DrupalDateTime) {
      $donation->setCreatedTime($form_state->getValue('date')->getTimestamp());
    }
    else {
      $donation->setCreatedTime(REQUEST_TIME);
    }

    // We always build the donation's label from the donor's name and e-mail,
    // the amount of the donation, and the subject field if present.
    $label = $donation->getGiveForm->get('label') . ' : ';
    if ($donation->getDonorName()) {
      $label .= $donation->getDonorName() . ' ';
    }
    if ($donation->getDonorMail()) {
      $label .= '(' . $donation->getDonorMail() . ') ';
    }

    $subject = '';
    if ($donation->hasField('field_subject')) {
      // The subject may be in any format, so:
      // 1) Filter it into HTML
      // 2) Strip out all HTML tags
      // 3) Convert entities back to plain-text.
      $subject_text = $donation->field_subject->processed;
      $subject = Unicode::truncate(trim(Html::decodeEntities(strip_tags($subject_text))), 29, TRUE, TRUE);
    }
    if ($subject) {
      $label .= ': ' . $subject;
    }

    $donation->setLabel($label);

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

    // Check if flood control has been activated for sending emails.
    if (!$this->currentUser()->hasPermission('administer give forms') && (!$donation->isPersonal() || !$this->currentUser()->hasPermission('administer users'))) {
      $limit = $this->config('give.settings')->get('flood.limit');
      $interval = $this->config('give.settings')->get('flood.interval');

      if (!$this->flood->isAllowed('give', $limit, $interval)) {
        $form_state->setErrorByName('', $this->t('You cannot send more than %limit donations in @interval. Try again later.', array(
          '%limit' => $limit,
          '@interval' => $this->dateFormatter->formatInterval($interval),
        )));
      }
    }

    return $donation;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $donation = $this->entity;
    $user = $this->currentUser();
    $this->mailHandler->sendDonationNotice($donation, $user);

    $this->flood->register('give', $this->config('give.settings')->get('flood.interval'));
    drupal_set_donation($this->t('Your donation has been sent.'));

    // To avoid false error donations caused by flood control, redirect away from
    // the give form to the front page.
    $form_state->setRedirect('<front>');

    // Save the donation. In core this is a no-op but should contrib wish to
    // implement donation storage, this will make the task of swapping in a real
    // storage controller straight-forward.
    $donation->save();
  }

}
