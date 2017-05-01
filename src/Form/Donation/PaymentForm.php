<?php

namespace Drupal\give\Form\Donation;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
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
   * Constructs a DonationForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
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
  public function __construct(EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, MailHandlerInterface $mail_handler, DateFormatterInterface $date_formatter, GiveStripeInterface $give_stripe) {
    parent::__construct($entity_manager);
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
    $donation = $this->entity;
    $form = parent::form($form, $form_state, $donation);
    $form['#prefix'] = '<div class="flow">';
    $form['#suffix'] = '</div>';
    $form['#attributes']['class'][] = 'give-form give-form-payment flow-middle';

    $form['thanks'] = array(
      '#markup' => $this->t(
        "<h3>Thank you for supporting :sitename, :name!</h3>",
        [':name' => $donation->getDonorName(), ':sitename' => \Drupal::config('system.site')->get('name')]
      ),
      '#weight' => -50,
    );
    $form['show_amount'] = array(
      '#type' => 'item',
      '#value' => $donation->getAmount(),
      '#title' => $this->t('Amount you pledged'),
      '#plain_text' => $donation->recurring() ? $this->t(':amount :recurrence',
        [':amount' => $donation->getDollarAmount(), ':recurrence' => $donation->getRecurrence()]) : $donation->getDollarAmount(),
      '#weight' => -40,
    );

    $form['method'] = array(
      '#type' => 'radios',
      '#title' => t('Choose donation method'),
      '#options' => array(
        GIVE_WITH_STRIPE => $this->t('By credit/debit card'),
        // GIVE_WITH_DWOLLA => $this->t('By bank transfer')
        GIVE_WITH_CHECK => $this->t('By check or other'),
      ),
      '#weight' => 0,
    );

    $form['#attached'] = [
      'library' => ['give/give-stripe-helper'],
      'drupalSettings' => [
        'give' => [
          'stripe_publishable_key' => \Drupal::config('give.settings')->get('stripe_publishable_key'),
        ],
        'http_header' => [
          ['Content-Security-Policy' => "connect-src 'https://api.stripe.com'"],
          ['Content-Security-Policy' => "child-src 'https://js.stripe.com'"],
          ['Content-Security-Policy' => "script-src 'https://js.stripe.com'"],
        ],
      ],
    ];

    $form['stripe_errors'] = array(
      '#markup' => '<span class="payment-errors"></span>',
      '#weight' => 10,
    );

    $form['stripe_token'] = array(
      '#type' => 'hidden',
      '#default_value' => '',
    );

    // Custom radar rules can't use name but Stripe's risk assessment does.
    // Therefore this should default to the entered name but be editable.
    // TODO see https://www.drupal.org/node/2872223
    $form['donor_name_for_stripe'] = array(
      '#type' => 'hidden',
      '#default_value' => $donation->getDonorName(),
    );

    $form['card'] = array(
      '#type' => 'item',
      '#title' => t('Credit or debit card'),
      '#required' => TRUE,
      '#value' => TRUE, // For items, required is supposed to only show the asterisk, but Drupal is broken.
      '#markup' => '<div id="stripe-card-element"></div><div class="form--inline-feedback form--inline-feedback--success" id="stripe-card-errors"></div><div class="form--inline-feedback form--inline-feedback--error" id="stripe-card-success"></div>',
      '#allowed_tags' => ['div'],
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => GIVE_WITH_STRIPE],
        ],
      ],
    );

    $form['check_or_other_text'] = array(
      '#type' => 'item',
      '#description' => $donation->getGiveForm()->getCheckOrOtherText(),
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => GIVE_WITH_CHECK],
        ],
      ],
    );

    $form['telephone'] = array(
      '#type' => 'tel',
      '#title' => t('Telephone number'),
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => GIVE_WITH_CHECK],
        ],
      ],
    );

    $form['check_or_other_information'] = array(
      '#type' => 'textarea',
      '#title' => t('Further information'),
      '#description' => t('Please ask any questions or explain anything needed to arrange for giving your donation.'),
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => GIVE_WITH_CHECK],
        ],
      ],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $elements = parent::actions($form, $form_state);
    $elements['submit']['#value'] = $this->t('Give');
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
      $form_state->setErrorByName('stripe_errors', $this->t("You have already completed this donation. Thank you! Please initiate a new donation if you wish to donate more."));
    }

    if ($form_state->getValue('method') != GIVE_WITH_STRIPE) {
      return;
    }
    // Get the token for use in processing the donation; throw error if missing.
    if (!$token = $donation->getStripeToken()) {
      $form_state->setErrorByName('stripe_errors', $this->t("Could not retrieve token from Stripe."));
    }

    $this->giveStripe->setApiKey(\Drupal::config('give.settings')->get('stripe_secret_key'));

    // If the donation is recurring, we create a plan and a customer.
    if ($donation->recurring()) {
      $plan_data = [
        "id" => $donation->getPlanId(),
        "amount" => $donation->getAmount(),
        "currency" => "usd",
        "interval" => "month",
        "interval_count" => $donation->recurring(),
        "name" => $donation->getPlanName(),
      ];
      try {
        $plan = $this->giveStripe->createPlan($plan_data);
      } catch (\Exception $e) {
        $form_state->setErrorByName('stripe_errors', $this->t($e->getMessage()));
        return;
      }

      // Create the customer with subscription plan on Stripe's servers - this will charge the user's card
      $customer_data = [
        "plan" => $plan->_values['id'],
        "source" => $token,
        "email" => $donation->getDonorMail(),
        "metadata" => [
          "give_form_id" => $donation->getGiveForm()->id(),
          "give_form_label" => $donation->getGiveForm()->label(),
          "email" => $donation->getDonorMail(),
        ],
      ];

      try {
        if ($this->giveStripe->createCustomer($customer_data)) {
          $this->entity->setCompleted();
        }
      } catch (\Exception $e) {
        $form_state->setErrorByName('stripe_errors', $e->getMessage());
      }
    } else {
      // If the donation is *not* recurring, only in this case do we create a charge ourselves.
      // Create the charge on Stripe's servers - this will charge the user's card.
      $donation_data = [
        "amount" => $donation->getAmount(), // amount in cents, again
        "currency" => "usd",
        "source" => $token,
        "description" => $donation->getLabel(),
        "metadata" => array(
          "give_form_id" => $donation->getGiveForm()->id(),
          "give_form_label" => $donation->getGiveForm()->label(),
          "email" => $donation->getDonorMail(),
        ),
      ];

      try {
        if ($this->giveStripe->createCharge($donation_data)) {
          $this->entity->setCompleted();
        }
      } catch (\Exception $e) {
        $form_state->setErrorByName('stripe_errors', $e->getMessage());
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

    $user = $this->currentUser();
    $this->mailHandler->sendDonationNotice($donation, $user);
    drupal_set_message(
      $this->t("Thank you, :donor_name, for your donation of :amount",
      array(
        ':donor_name' => $donation->getDonorName(),
        ':amount' => $donation->recurring() ? $this->t(':amount monthly', [':amount' => $donation->getDollarAmount()]) : $donation->getDollarAmount()))
    );
    drupal_set_message($this->t("We have e-mailed a receipt to <em>:mail</em>.", [':mail' => $donation->getDonorMail()]));

    drupal_set_message($this->t('Your donation has been received.  Thank you!'));

    $donation->save();
  }

}
