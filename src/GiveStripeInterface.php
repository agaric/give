<?php

namespace Drupal\give;

interface GiveStripeInterface {
  /**
   * The Stripe Api Key.
   *
   * @param string $stripeSecretKey
   */
  public function setApiKey($stripeSecretKey);

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
  public function createPlan($plan_data);

  /**
   * Charge the donation.
   *
   * @param array $donation_data
   *   The donation data.
   * @throws \Exception The error returned by the Stripe API.
   * @return bool
   */
  public function createCharge($donation_data);

  /**
   * Create a customer for this donation.
   *
   * @param $customer_data
   * @throws \Exception The error returned by the Stripe API.
   * @return bool
   */
  public function createCustomer($customer_data);
}