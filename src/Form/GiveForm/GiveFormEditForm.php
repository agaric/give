<?php

namespace Drupal\give\Form\GiveForm;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Form\FormStateInterface;
use Egulias\EmailValidator\EmailValidator;

/**
 * Base form for give form edit forms.
 */
class GiveFormEditForm extends EntityForm implements ContainerInjectionInterface {
  use ConfigFormBaseTrait;

  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * Constructs a new GiveFormEditForm.
   *
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   */
  public function __construct(EmailValidator $email_validator) {
   $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('email.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['give.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\give\Entity\GiveForm $give_form */
    $give_form = $this->entity;
    $default_form = $this->config('give.settings')->get('default_form');

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $give_form->label(),
      '#description' => $this->t("Example: 'General donations', 'Renovation fund drive', or 'Annual appeal'."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $give_form->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => array(
        'exists' => '\Drupal\give\Entity\GiveForm::load',
      ),
      '#disabled' => !$give_form->isNew(),
    );
    $form['recipients'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Recipients'),
      '#default_value' => implode(', ', $give_form->getRecipients()),
      '#description' => $this->t("Provide who should be notified when a donation is received. Example: 'donations@example.org' or 'fund@example.org,staff@example.org' . To specify multiple recipients, separate each email address with a comma."),
      '#required' => TRUE,
    );
    $form['subject'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Subject'),
      '#default_value' => $give_form->getSubject(),
      '#description' => $this->t('Subject used for e-mail reply (if Auto-reply with receipt is set below).'),
      '#required' => TRUE,
    );
    $form['reply'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Auto-reply with receipt'),
      '#default_value' => $give_form->getReply(),
      '#description' => $this->t('Optionally send a receipt confirming the donation (including amount) with this text, which should include your organization name and any relevant tax information. Leave empty if you do not want to send the donor an auto-reply message and receipt.'),
    );
    $form['check_or_other_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Text to show for check or other'),
      '#default_value' => $give_form->getCheckOrOtherText(),
      '#description' => $this->t('Optional message to show potential givers who select the "Check or other" donation method.'),
    );
    $form['weight'] = array(
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $give_form->getWeight(),
      '#description' => $this->t('When listing forms, those with lighter (smaller) weights get listed before forms with heavier (larger) weights. Forms with equal weights are sorted alphabetically.'),
    );
    $form['selected'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Make this the default form'),
      '#default_value' => ($default_form && $default_form === $give_form->id()),
    );

    $form['redirect_uri'] = [
      '#type' => 'textfield',
      '#title' => t('Redirect Page'),
      '#description' => t('The path to redirect the form after Submit.'),
      '#default_value' => $give_form->getRedirectUri(),
    ];
    $form['submit_text'] = [
      '#type' => 'textfield',
      '#title' => t('Submit button text'),
      '#description' => t("Override the submit button's default <em>Give</em> text."),
      '#default_value' => $give_form->getSubmitText(),
    ];

    $name_field = $form_state->get('num_intervals');
    $form['#tree'] = TRUE;
    $form['frequency'] = [
      '#title' => $this->t('Frequency Intervals (Plans)'),
    ];
    $form['frequency']['frequency_intervals_table'] = [
      '#type' => 'table',
      '#title' => $this->t('Frequency'),
      '#header' => [
        $this->t('Interval'),
        $this->t('Interval count'),
        $this->t('Description'),
      ],
      '#prefix' => '<div id="frequency-intervals-wrapper">',
      '#suffix' => '</div>',
    ];

    if (empty($name_field)) {
      $name_field = 1;
      $form_state->set('num_intervals', $name_field);
    }
    for ($i = 0; $i < $name_field; $i++) {
      $form['frequency']['frequency_intervals_table'][$i]['interval'] = [
        '#type' => 'select',
        '#title' => '',
        '#options' => [
          'day' => 'day',
          'week' => 'week',
          'month' => 'month',
          'year' => 'year',
        ],
      ];

      $form['frequency']['frequency_intervals_table'][$i]['interval_count'] = [
        '#type' => 'number',
        '#title' => '',
        '#default_value' => 1,
      ];

      $form['frequency']['frequency_intervals_table'][$i]['description'] = [
        '#type' => 'textfield',
        '#title' => '',
        '#default_value' => '',
      ];
    }
    $form['frequency']['frequency_intervals_table']['actions'] = [
      '#type' => 'actions',
    ];
    $form['frequency']['frequency_intervals_table']['actions']['add_frequency'] = [
      '#type' => 'submit',
      '#value' => t('Add one more'),
      '#submit' => array('::addOne'),
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'frequency-intervals-wrapper',
      ],
    ];
    if ($name_field > 1) {
      $form['frequency']['frequency_intervals_table']['actions']['remove_frequency'] = [
        '#type' => 'submit',
        '#value' => t('Remove one'),
        '#submit' => array('::removeCallback'),
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'frequency-intervals-wrapper',
        ],
      ];
    }
    $form_state->setCached(FALSE);

    return $form;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['frequency']['frequency_intervals_table'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_intervals');
    $add_button = $name_field + 1;
    $form_state->set('num_intervals', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_intervals');
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('num_intervals', $remove_button);
    }
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate and each email recipient.
    $recipients = explode(',', $form_state->getValue('recipients'));

    foreach ($recipients as &$recipient) {
      $recipient = trim($recipient);
      if (!$this->emailValidator->isValid($recipient)) {
        $form_state->setErrorByName('recipients', $this->t('%recipient is an invalid email address.', array('%recipient' => $recipient)));
      }
    }
    $form_state->setValue('recipients', $recipients);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $give_form = $this->entity;
    $status = $give_form->save();
    $give_settings = $this->config('give.settings');

    $edit_link = $this->entity->link($this->t('Edit'));
    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('Give form %label has been updated.', array('%label' => $give_form->label())));
      $this->logger('give')->notice('Give form %label has been updated.', array('%label' => $give_form->label(), 'link' => $edit_link));
    }
    else {
      drupal_set_message($this->t('Give form %label has been added.', array('%label' => $give_form->label())));
      $this->logger('give')->notice('Give form %label has been added.', array('%label' => $give_form->label(), 'link' => $edit_link));
    }

    // Update the default form.
    if ($form_state->getValue('selected')) {
      $give_settings
        ->set('default_form', $give_form->id())
        ->save();
    }
    // If it was the default form, empty out the setting.
    elseif ($give_settings->get('default_form') == $give_form->id()) {
      $give_settings
        ->set('default_form', NULL)
        ->save();
    }

    $form_state->setRedirectUrl($give_form->toUrl('collection'));
  }

}
