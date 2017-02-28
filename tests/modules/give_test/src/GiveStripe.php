<?php

namespace Drupal\give_test;

use Drupal\give\GiveStripeInterface;

class GiveStripe Implements GiveStripeInterface {

  /**
   * The Stripe Api Key.
   *
   * @param string $stripeSecretKey
   */
  public function setApiKey($stripeSecretKey) {

  }

  /**
   * Create a plan if it does not exists,
   *
   * @param array $plan_data
   *   The stripe plan.
   *
   * @throws \Exception The error returned by the Stripe API.
   *
   * @return \Stripe\Plan The Stripe Plan.
   */
  public function createPlan($plan_data) {
    $plan = new \stdClass();
    $plan->_values = ['id' => 'test-id'];
    return $plan;
  }

  /**
   * Charge the donation.
   *
   * @param array $donation_data
   *   The donation data.
   * @throws \Exception The error returned by the Stripe API.
   * @return bool
   */
  public function createCharge($donation_data) {
    return TRUE;
  }

  /**
   * Create a customer for this donation.
   *
   * @param $customer_data
   * @throws \Exception The error returned by the Stripe API.
   * @return bool
   */
  public function createCustomer($customer_data) {
    return TRUE;
  }

}