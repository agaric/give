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
      '#plain_text' => $donation->recurring() ? $this->t(':amount monthly', [':amount' => $donation->getDollarAmount()]) : $donation->getDollarAmount(),
      '#weight' => -40,
    );

    $form['method'] = array(
      '#type' => 'radios',
      '#title' => t('Choose donation method'),
      '#options' => array(
        GIVE_WITH_STRIPE => $this->t('By credit/debit card'),
        GIVE_WITH_DWOLLA => $this->t('By bank transfer'),
        GIVE_WITH_CHECK => $this->t('By check or other'),
      ),
      '#weight' => 0,
    );

    $form['#attached'] = [
      'library' => ['give/give-stripe-helper'],
      'drupalSettings' => [
        'give' => [
          'stripe_publishable_key' => \Drupal::config('give.settings')->get('stripe_publishable_key'),
        ]
      ]
    ];

    $form['stripe_errors'] = array(
      '#markup' => '<span class="payment-errors"></span>',
      '#weight' => 10,
    );

    $form['stripe_token'] = array(
      '#type' => 'hidden',
      '#default_value' => '',
    );

    $form['number'] = array(
      '#type' => 'item',
      '#title' => t('Card number'),
      '#required' => TRUE,
      '#value' => TRUE, // For items, required is supposed to only show the asterisk, but Drupal is broken.
      '#markup' => '<input size="20" maxlength="20" class="form-text" type="text" data-stripe="number">',
      '#allowed_tags' => ['input'],
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => GIVE_WITH_STRIPE],
        ],
      ],
    );

    $form['expiration'] = array(
      '#type' => 'item',
      '#title' => t('Expiration (MM YY)'),
      '#required' => TRUE,
      '#value' => TRUE, // For items, required is supposed to only show the asterisk, but Drupal is broken.
      '#markup' => '<input size="2" maxlength="2" type="text" data-stripe="exp_month" class="inline"> <input size="2" maxlength="2" type="text" data-stripe="exp_year" class="inline">',
      '#allowed_tags' => ['input'],
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => GIVE_WITH_STRIPE],
        ],
      ],
    );

    $form['cvc'] = array(
      '#type' => 'item',
      '#title' => t('CVC'),
      '#required' => TRUE,
      '#value' => TRUE, // For items, required is supposed to only show the asterisk, but Drupal is broken.
      '#markup' => '<input size="4" maxlength="4" type="text" data-stripe="cvc" class="inline">',
      '#allowed_tags' => ['input'],
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => GIVE_WITH_STRIPE],
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
    $donation = parent::validateForm($form, $form_state);

    if ($form_state['values']['method'] != GIVE_WITH_STRIPE) {
      return;
    }
    // Get the token for use in processing the donation; throw error if missing.
    if (!$token = $donation->getStripeToken()) {
      $form_state->setErrorByName('stripe_errors', $this->t("Could not retrieve token from Stripe."));
      drupal_set_message("Would not retrieve token from Stripe.", 'error');
    }

    \Stripe\Stripe::setApiKey(\Drupal::config('give.settings')->get('stripe_secret_key'));

    // If the donation is recurring, we create a plan and a customer.
    if ($donation->recurring()) {
      try {
        $plan = \Stripe\Plan::create(array(
          "id" => $donation->uuid(),
          "amount" => $donation->getAmount(),
          "currency" => "usd",
          "interval" => "month",
          "name" => $donation->getLabel(),
        ));
      } catch(\Stripe\Error\ApiConnection $e) {
        $form_state->setErrorByName('stripe_errors', $this->t('Could not connect to payment processer. More information: %e', ['%e' => $e->getMessage()]));
      } catch(\Stripe\Error\Base $e) {
        $form_state->setErrorByName('stripe_errors', $this->t('Unknown error: %e', ['%e' => $e->getMessage()]));
      }

      // Create the customer with subscription plan on Stripe's servers - this will charge the user's card
      try {
        $plan_id = $plan->_values['id'];
        $alt_plan_id = $plan->value('id');
        $customer = \Stripe\Plan::create(array(
          "plan" => $plan_id,
          "source" => $token,
          "metadata" => array(
            "give_form_id" => $donation->getGiveForm()->id(),
            "give_form_label" => $donation->getGiveForm()->label(),
            "email" => $donation->getDonorMail(),
          ),
        ));
      } catch(\Stripe\Error\ApiConnection $e) {
        $form_state->setErrorByName('stripe_errors', $this->t('Could not connect to payment processer. More information: %e', ['%e' => $e->getMessage()]));
      } catch(\Stripe\Error\Base $e) {
        $form_state->setErrorByName('stripe_errors', $this->t('Unknown error: %e', ['%e' => $e->getMessage()]));
      }

      if ($customer) {
        $donation->setCompleted();
      }

      return $donation;
    }

    // If the donation is *not* recurring, only in this case do we create a charge ourselves.
    // Create the charge on Stripe's servers - this will charge the user's card
    try {
      $charge = \Stripe\Charge::create(array(
        "amount" => $donation->getAmount(), // amount in cents, again
        "currency" => "usd",
        "source" => $token,
        "description" => $donation->getLabel(),
        "metadata" => array(
          "give_form_id" => $donation->getGiveForm()->id(),
          "give_form_label" => $donation->getGiveForm()->label(),
          "email" => $donation->getDonorMail(),
        ),
      ));
    } catch(\Stripe\Error\Card $e) {
      $form_state->setErrorByName('number', $this->t("The card has been declined. More information: %e", ['%e' => $e->getMessage()]));
    } catch(\Stripe\Error\ApiConnection $e) {
      $form_state->setErrorByName('stripe_errors', $this->t('Could not connect to payment processer. More information: %e', ['%e' => $e->getMessage()]));
    } catch(\Stripe\Error\Base $e) {
      $form_state->setErrorByName('stripe_errors', $this->t('Unknown error: %e', ['%e' => $e->getMessage()]));
    }

    if ($charge) {
      $donation->setCompleted();
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
    drupal_set_message(
      $this->t("Thank you, :donor_name, for your donation of :amount",
      array(
        ':donor_name' => $donation->getDonorName(),
        ':amount' => $donation->recurring() ? $this->t(':amount monthly', [':amount' => $donation->getDollarAmount()]) : $donation->getDollarAmount()))
    );
    drupal_set_message("We have e-mailed a receipt to <em>:mail</em>.", [':mail' => $donation->getDonorMail()]);

    drupal_set_message($this->t('Your donation has been received.  Thank you!'));

    // Save the donation. In core this is a no-op but should contrib wish to
    // implement donation storage, this will make the task of swapping in a real
    // storage controller straight-forward.
    $donation->save();
  }

}
