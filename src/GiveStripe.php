<?php

namespace Drupal\give;

use \Stripe\Stripe;
use \Stripe\Plan;
use \Stripe\Error;
use \Stripe\Customer;
use \Stripe\Charge;


class GiveStripe Implements GiveStripeInterface {

  /**
   * {@inheritdoc}
   */
  public function setApiKey($stripeSecretKey) {
    Stripe::setApiKey($stripeSecretKey);
  }

  /**
   * {@inheritdoc}
   */
  public function createPlan($plan_data) {
    try {
      // Try to create the plan.
      $plan = Plan::create($plan_data);
    } catch(Error\ApiConnection $e) {
      throw new \Exception(t('Could not connect to payment processer. More information: %e', ['%e' => $e->getMessage()]));
    } catch(Error\InvalidRequest $e) {
      if ($e->getMessage() === 'Plan already exists.') {
        // If the plan already exists, lets retrieve it.
        $plan = Plan::retrieve($plan_data['id']);
      }
      else {
        throw new \Exception(t('Invalid request: %e', ['%e' => $e->getMessage()]));
      }
    } catch(Error\Base $e) {
      throw new \Exception(t('Error: %e', ['%e' => $e->getMessage()]));
    }
    // Check if the plan was created or retrieved correctly.
    if (!($plan instanceof Plan)) {
      throw new \Exception(t("Unable to create subscription plan for recurring donation. Could not complete donation."));
    }
    return $plan;
  }

  /**
   * {@inheritdoc}
   */
  public function createCharge($donation_data) {
    try {
      $charge = Charge::create($donation_data);
    } catch(Error\Card $e) {
      throw new \Exception("Could not process card: " . $e->getMessage());
    } catch(Error\ApiConnection $e) {
      throw new \Exception("Could not connect to payment processer. More information: " . $e->getMessage());
    } catch(Error\Base $e) {
      throw new \Exception('Error: ' . $e->getMessage());
    }

    if (!($charge instanceof Charge)) {
      throw new \Exception("Could not complete donation.");
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createCustomer($customer_data) {
    try {
      $customer = Customer::create($customer_data);
    } catch(Error\ApiConnection $e) {
      throw new \Exception('Could not connect to payment processer. More information: ' . $e->getMessage());
    } catch(Error\Card $e) {
      throw new \Exception("Could not process card: " . $e->getMessage());
    } catch(Error\Base $e) {
      throw new \Exception('Error: ' . $e->getMessage());
    }

    if (!($customer instanceof Customer)) {
      throw new \Exception("Could not complete donation.");
    }
    return TRUE;
  }

}
