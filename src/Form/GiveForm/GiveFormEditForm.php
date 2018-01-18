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
    $form['#tree'] = TRUE;

    /** @var \Drupal\give\Entity\GiveForm $give_form */
    $give_form = $this->entity;
    $default_form = $this->config('give.settings')->get('default_form');
    $frequencies = ($give_form->isNew()) ? give_get_default_frequencies() : $give_form->getFrequencies();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $give_form->label(),
      '#description' => $this->t("Example: 'General donations', 'Renovation fund drive', or 'Annual appeal'."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $give_form->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => '\Drupal\give\Entity\GiveForm::load',
      ],
      '#disabled' => !$give_form->isNew(),
    ];
    $form['recipients'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Recipients'),
      '#default_value' => implode(', ', $give_form->getRecipients()),
      '#description' => $this->t("Provide who should be notified when a donation is received. Example: 'donations@example.org' or 'fund@example.org,staff@example.org' . To specify multiple recipients, separate each email address with a comma."),
      '#required' => TRUE,
    ];
    $form['autoreply'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send automatic replies'),
      '#default_value' => $give_form->get('autoreply'),
      '#description' => $this->t('As soon as a donation is complete, send a receipt with configurable messages below.'),
    ];
    $form['_available_tokens'] = [
      '#type' => 'item',
      '#description' => $this->t('The following tokens are available for all automatic replies and subjects: @tokens.', ['@tokens' => implode(give_donation_tokens(), ', ')]),
      '#states' => ['visible' => [':input[name="autoreply"]' => ['checked' => TRUE],],],
    ];
    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Acknowledgement e-mail subject'),
      '#default_value' => $give_form->getSubject(),
      '#description' => $this->t('Subject used for e-mail response to donor (if Auto-reply with receipt is set below).  Tokens available: @tokens.', ['@tokens' => implode(give_donation_tokens(), ', ')]),
      '#required' => TRUE,
      '#states' => ['visible' => [':input[name="autoreply"]' => ['checked' => TRUE],],],
    ];
    $form['reply'] = [
      '#type' => 'text_format',
      '#format' => 'minimalhtml',
      '#allowed_formats' => ['minimalhtml'],
      '#title' => $this->t('Auto-reply with receipt'),
      '#default_value' => $give_form->getReply(),
      '#description' => $this->t('Optionally send a receipt confirming the donation (including amount) with this text, which should include your organization name and any relevant tax information. Leave empty if you do not want to send the donor an auto-reply message and receipt.  Tokens available: @tokens.', ['@tokens' => implode(give_donation_tokens(), ', ')]),
      '#states' => ['visible' => [':input[name="autoreply"]' => ['value' => TRUE],],],
    ];
    $form['reply_recurring'] = [
      '#type' => 'text_format',
      '#format' => 'minimalhtml',
      '#allowed_formats' => ['minimalhtml'],
      '#title' => $this->t('Auto-reply to recurring donation with receipt'),
      '#default_value' => $give_form->get('reply_recurring'),
      '#description' => $this->t('Optionally send a receipt confirming the donation (including amount) with this text, which should include your organization name and any relevant tax information. Leave empty if you do not want to send the donor an auto-reply message and receipt.  Tokens available: @tokens.', ['@tokens' => implode(give_donation_tokens(), ', ')]),
      '#states' => ['visible' => [':input[name="autoreply"]' => ['value' => TRUE],],],
    ];
    $form['reply_pledge'] = [
      '#type' => 'text_format',
      '#format' => 'minimalhtml',
      '#allowed_formats' => ['minimalhtml'],
      '#title' => $this->t('Auto-reply with receipt'),
      '#default_value' => $give_form->get('reply_pledge'),
      '#description' => $this->t('Optionally send a receipt confirming the donation (including amount) with this text, which should include your organization name and any relevant tax information. Leave empty if you do not want to send the donor an auto-reply message and receipt.  Tokens available: @tokens.', ['@tokens' => implode(give_donation_tokens(), ', ')]),
      '#states' => ['visible' => [':input[name="autoreply"]' => ['value' => TRUE],],],
    ];
    $form['collect_address'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collect address'),
      '#default_value' => $give_form->getCollectAddress(),
      '#description' => $this->t('Require the donor to provide their address information.  This is not needed for credit card or other processing.'),
    ];
    $form['check_or_other_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text to show for check or other'),
      '#default_value' => $give_form->getCheckOrOtherText(),
      '#description' => $this->t('Optional message to show potential givers who select the "Check or other" donation method.'),
    ];
    $form['credit_card_extra_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Extra text to show above credit card form'),
      '#default_value' => $give_form->getCreditCardExtraText(),
      '#description' => $this->t('Optional message to show above credit card form for potential givers who select the "Credit card" donation method.'),
    ];
    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $give_form->getWeight(),
      '#description' => $this->t('When listing forms, those with lighter (smaller) weights get listed before forms with heavier (larger) weights. Forms with equal weights are sorted alphabetically.'),
    ];
    $form['selected'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make this the default form'),
      '#default_value' => ($default_form && $default_form === $give_form->id()),
    ];

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
    $form['payment_submit_text'] = [
      '#type' => 'textfield',
      '#title' => t('Submit payment button text'),
      '#description' => t("Override the payment page submit button's default <em>Give</em> text."),
      '#default_value' => $give_form->getPaymentSubmitText(),
    ];
    $name_field = $form_state->get('num_intervals');
    $form['frequency'] = [
      '#type' => 'fieldset',
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
      $name_field = count($frequencies) ?: 1;
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
        '#default_value' => (isset($frequencies[$i])) ? $frequencies[$i]['interval'] : 'month',
      ];
      $form['frequency']['frequency_intervals_table'][$i]['interval_count'] = [
        '#type' => 'number',
        '#title' => '',
        '#default_value' => (isset($frequencies[$i])) ? $frequencies[$i]['interval_count'] : 1,
      ];
      $form['frequency']['frequency_intervals_table'][$i]['description'] = [
        '#type' => 'textfield',
        '#title' => '',
        '#default_value' => (isset($frequencies[$i])) ? $frequencies[$i]['description'] : '',
      ];
    }
    $form['frequency']['frequency_intervals_table']['actions'] = [
      '#type' => 'actions',
    ];
    $form['frequency']['frequency_intervals_table']['actions']['add_frequency'] = [
      '#type' => 'submit',
      '#value' => t('Add'),
      '#submit' => ['::addOne'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'frequency-intervals-wrapper',
      ],
    ];

    if ($name_field > 1) {
      $form['frequency']['frequency_intervals_table']['actions']['remove_frequency'] = [
        '#type' => 'submit',
        '#value' => t('Remove'),
        '#submit' => ['::removeCallback'],
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

    // We hard-code the format to just one option, so throw it out.
    $reply_array = $form_state->getValue('reply');
    $form_state->setValue('reply', $reply_array['value']);

    // Validate and each email recipient.
    $recipients = explode(',', $form_state->getValue('recipients'));

    foreach ($recipients as &$recipient) {
      $recipient = trim($recipient);
      if (!$this->emailValidator->isValid($recipient)) {
        $form_state->setErrorByName('recipients', $this->t('%recipient is an invalid email address.', ['%recipient' => $recipient]));
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
      drupal_set_message($this->t('Give form %label has been updated.', ['%label' => $give_form->label()]));
      $this->logger('give')->notice('Give form %label has been updated.', ['%label' => $give_form->label(), 'link' => $edit_link]);
    }
    else {
      drupal_set_message($this->t('Give form %label has been added.', ['%label' => $give_form->label()]));
      $this->logger('give')->notice('Give form %label has been added.', ['%label' => $give_form->label(), 'link' => $edit_link]);
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

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\give\GiveFormInterface $entity */
    $entity = parent::buildEntity($form, $form_state);
    $frequency = $form_state->getValue('frequency');
    unset($frequency['frequency_intervals_table']['actions']);
    $entity->set('frequencies', $frequency['frequency_intervals_table']);

    return $entity;
  }

}
