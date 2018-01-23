<?php

namespace Drupal\give\Form\Donation;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\give\MailHandlerInterface;
use Drupal\give\GiveStripeInterface;
use Drupal\Core\Url;

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
   * The Stripe Service.
   *
   * @var \Drupal\give\GiveStripeInterface
   */
  protected $giveStripe;

  /**
   * Constructs a PaymentForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\give\MailHandlerInterface $mail_handler
   *   The give mail handler service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date service.
   * @param \Drupal\give\GiveStripeInterface $give_stripe
   *   The GiveStripe service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, MailHandlerInterface $mail_handler, DateFormatterInterface $date_formatter, GiveStripeInterface $give_stripe) {
    parent::__construct($entity_type_manager);
    $this->languageManager = $language_manager;
    $this->mailHandler = $mail_handler;
    $this->dateFormatter = $date_formatter;
    $this->giveStripe = $give_stripe;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('give.mail_handler'),
      $container->get('date.formatter'),
      $container->get('give.stripe')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $publishable_key = \Drupal::config('give.settings')->get('stripe_publishable_key');

    $donation = $this->entity;
    $form = parent::form($form, $form_state, $donation);
    $form['#prefix'] = '<div class="flow">';
    $form['#suffix'] = '</div>';
    $form['#attributes']['class'][] = 'give-form give-form-payment flow-middle';

    $form['thanks'] = [
      '#markup' => $this->t(
        "<h3>Thank you for supporting :sitename, :name!</h3>",
        [':name' => $donation->getDonorName(), ':sitename' => \Drupal::config('system.site')->get('name')]
      ),
      '#weight' => -50,
    ];
    $form['show_amount'] = [
      '#type' => 'item',
      '#title' => $this->t('Amount you pledged'),
      '#value' => $donation->getAmount(),
      '#plain_text' => $donation->recurring() ? $this->t(':plan',
        [':plan' => $donation->getPlanName()]) : $donation->getDollarAmount(),
      '#weight' => -40,
    ];

    $form['method'] = [
      '#type' => 'radios',
      '#title' => t('Choose donation method'),
      '#options' => [
        // GIVE_WITH_DWOLLA => $this->t('By bank transfer')
        // Stripe is added, below, only if the API key is present.
        GIVE_WITH_CHECK => $this->t('By check or other'),
      ],
      // Default is unset, below, if Stripe available and so there are options.
      '#default_value' => GIVE_WITH_CHECK,
      '#required' => TRUE,
      '#weight' => 0,
    ];

    // Only display the Credit Card payment method if the stripe credentials
    // has been provided.
    if ($publishable_key) {
      // Add the credit card payment option (via stripe)
      $form['method']['#options'] = [GIVE_WITH_STRIPE => $this->t('By credit/debit card')] + $form['method']['#options'];
      // Allow the user select her payment method.
      unset($form['method']['#default_value']);

      $form['#attached'] = [
        'library' => ['give/give-stripe-helper'],
        'drupalSettings' => [
          'give' => [
            'stripe_publishable_key' => $publishable_key,
          ],
          'http_header' => [
            ['Content-Security-Policy' => "connect-src 'https://api.stripe.com'"],
            ['Content-Security-Policy' => "child-src 'https://js.stripe.com'"],
            ['Content-Security-Policy' => "script-src 'https://js.stripe.com'"],
          ],
        ],
      ];
      if (\Drupal::config('give.settings')->get('log_problems')) {
        $form['#attached']['drupalSettings']['give']['problem_log'] = [
          'donation_uuid' => $donation->uuid(),
          'url' => Url::fromUri('base:' . drupal_get_path('module', 'give') . '/give_problem_log.php')->toString()];
      }
    }

    $form['stripe_errors'] = [
      '#markup' => '<span class="payment-errors"></span>',
      '#weight' => 10,
    ];

    $form['stripe_token'] = [
      '#type' => 'hidden',
      '#default_value' => '',
    ];

    // Custom radar rules can't use name but Stripe's risk assessment does.
    // Therefore this should default to the entered name but be editable.
    $form['donor_name_for_stripe'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $donation->getDonorName(),
      '#description' => $this->t('The donation will be billed using this name'),
    ];

    if ($donation->getGiveForm()->getCollectAddress()) {
      $form['address_line1'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Billing address'),
        '#required' => TRUE,
      ];
      $form['address_line2'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Apt or unit #'),
      ];
      $form['address_city'] = [
        '#type' => 'textfield',
        '#title' => $this->t('City or district'),
        // TODO add '#default_value' (for everything) so form repopulates after errors
        '#required' => TRUE,
      ];
      $form['address_zip'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Postal code / ZIP'),
        '#required' => TRUE,
      ];
      $form['address_state'] = [
        '#type' => 'textfield',
        '#title' => $this->t('State or province'),
        '#required' => TRUE,
      ];
      $form['address_country'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Country'),
        '#default_value' => 'United States',
        '#required' => TRUE,
      ];
    }


    $form['credit_card_extra_text'] = [
      '#type' => 'item',
      '#description' => $donation->getGiveForm()->getCreditCardExtraText(),
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => GIVE_WITH_STRIPE],
        ],
      ],
    ];

    $form['card'] = [
      '#type' => 'item',
      '#title' => $this->t('Credit or debit card'),
      '#required' => TRUE,
      '#value' => TRUE, // For items, required is supposed to only show the asterisk, but Drupal is broken.
      '#markup' => '<div id="stripe-card-element" class="give-card-element"></div><div class="form--inline-feedback form--inline-feedback--success" id="stripe-card-errors"></div><div class="form--inline-feedback form--inline-feedback--error" id="stripe-card-success"></div>',
      '#allowed_tags' => ['div'],
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => GIVE_WITH_STRIPE],
        ],
      ],
    ];

    $form['check_or_other_text'] = [
      '#type' => 'item',
      '#description' => $donation->getGiveForm()->getCheckOrOtherText(),
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => GIVE_WITH_CHECK],
        ],
      ],
    ];

    $form['telephone'] = [
      '#type' => 'tel',
      '#title' => t('Telephone number'),
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => GIVE_WITH_CHECK],
        ],
      ],
    ];

    $form['check_or_other_information'] = [
      '#type' => 'textarea',
      '#title' => t('Further information'),
      '#description' => t('Please ask any questions or explain anything needed to arrange for giving your donation.'),
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => GIVE_WITH_CHECK],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $donation = $this->entity;
    $elements = parent::actions($form, $form_state);
    $elements['submit']['#value'] = $this->t($donation->getGiveForm()->getPaymentSubmitText());
    $elements['delete']['#title'] = $this->t('Cancel');
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
    /** @var \Drupal\give\Entity\Donation $donation */
    $donation = parent::validateForm($form, $form_state);
    if ($donation->isCompleted()) {
      $donate_path = Url::fromRoute('entity.give_form.canonical', ['give_form' => $donation->getGiveForm()->id()])->toString();
      $form_state->setErrorByName('stripe_errors', $this->t('You have already completed this donation. Thank you! Please <a href=":donate_path">donate again</a> if you wish to give more.', [':donate_path' => $donate_path]));
    }

    if ($form_state->getValue('method') != GIVE_WITH_STRIPE) {
      return;
    }
    // Get the token for use in processing the donation; throw error if missing.
    if (!$token = $donation->getStripeToken()) {
      $form_state->setErrorByName('stripe_errors', $this->t("Could not retrieve token from Stripe."));
      \Drupal::logger('give')->error('Stripe error: %msg.', ['%msg' => "Could not retrieve token from Stripe."]);
    }

    $this->giveStripe->setApiKey(\Drupal::config('give.settings')->get('stripe_secret_key'));

    // If the donation is recurring, we create a plan and a customer.
    if ($donation->recurring()) {
      $plan_data = [
        "id" => $donation->getPlanId(),
        "amount" => $donation->getAmount(),
        "currency" => "usd",
        "interval" => $donation->getRecurrenceIntervalUnit(),
        "interval_count" => $donation->getRecurrenceIntervalCount(),
        "name" => $donation->getPlanName(),
      ];
      try {
        $plan = $this->giveStripe->createPlan($plan_data);
      } catch (\Exception $e) {
        $form_state->setErrorByName('stripe_errors', $this->t($e->getMessage()));
        \Drupal::logger('give')->error('Stripe errors %msg.', ['%msg' => $e->getMessage()]);
        return;
      }

      // Create the customer with subscription plan on Stripe's servers - this will charge the user's card
      $customer_data = [
        "plan" => $donation->getPlanId(),
        "source" => $token,
        "email" => $donation->getDonorMail(),
        "metadata" => [
          "give_form_id" => $donation->getGiveForm()->id(),
          "give_form_label" => $donation->getGiveForm()->label(),
          "email" => $donation->getDonorMail(),
          "name" => $donation->getDonorName(),
        ],
      ];

      try {
        if ($this->giveStripe->createCustomer($customer_data)) {
          $this->entity->setCompleted();
        }
      } catch (\Exception $e) {
        $form_state->setErrorByName('stripe_errors', $e->getMessage());
        \Drupal::logger('give')->error('Stripe error: %msg.', ['%msg' => $e->getMessage()]);
      }
    } else {
      // If the donation is *not* recurring, only in this case do we create a charge ourselves.
      // Create the charge on Stripe's servers - this will charge the user's card.
      $donation_data = [
        "amount" => $donation->getAmount(), // amount in cents, again
        "currency" => "usd",
        "source" => $token,
        "description" => $donation->getLabel(),
        "metadata" => [
          "give_form_id" => $donation->getGiveForm()->id(),
          "give_form_label" => $donation->getGiveForm()->label(),
          "email" => $donation->getDonorMail(),
          "name" => $donation->getDonorName(),
        ],
      ];

      try {
        if ($this->giveStripe->createCharge($donation_data)) {
          $this->entity->setCardInfo($this->giveStripe);
          $this->entity->setCompleted();
        }
      } catch (\Exception $e) {
        $form_state->setErrorByName('stripe_errors', $e->getMessage());
        \Drupal::logger('give')->error('Stripe error: %msg.', ['%msg' => $e->getMessage()]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $donation = $this->entity;

    // Redirect the user.
    $give_form = $donation->getGiveForm();
    $url = Url::fromUserInput($give_form->getRedirectUri());
    $form_state->setRedirectUrl($url);

    drupal_set_message(
      $this->t("Thank you, :donor_name, for your donation of :amount",
      [
        ':donor_name' => $donation->getDonorName(),
        ':amount' => $donation->recurring() ? $this->t(':plan', [':plan' => $donation->getPlanName()]) : $donation->getDollarAmount()])
    );

    $user = $this->currentUser();
    $this->mailHandler->sendDonationNotice($donation, $user);

    drupal_set_message($this->t('Your donation has been received.  Thank you!'));

    $donation->save();
  }

}
