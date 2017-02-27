<?php

namespace Drupal\give;

use \Stripe\Stripe;
use \Stripe\Plan;
use \Stripe\Error;


class GiveStripe {

  /**
   * The Stripe Api Key.
   *
   * @param string $stripeSecretKey
   */
  public function setApiKey($stripeSecretKey) {
    Stripe::setApiKey($stripeSecretKey);
  }

  /**
   * Create a plan if it does not exists,
   *
   * @param array $plan
   *   The stripe plan.
   *
   * @throws \Exception The error returned by the Stripe API.
   *
   * @return \Stripe\Plan The Stripe Plan.
   */
  public function createPlan($plan) {
    try {
      // Try to create the plan.
      $plan = Plan::create($plan);
    } catch(Error\ApiConnection $e) {
      throw new \Exception(t('Could not connect to payment processer. More information: %e', ['%e' => $e->getMessage()]));
    } catch(Error\InvalidRequest $e) {
      if ($e->getMessage() === 'Plan already exists.') {
        // If the plan already exists, lets retrieve it.
        $plan = Plan::retrieve($plan['id']);
      }
      else {
        throw new \Exception(t('Invalid request: %e', ['%e' => $e->getMessage()]));
      }
    } catch(Error\Base $e) {
      throw new \Exception(t('Error: %e', ['%e' => $e->getMessage()]));
    }

    // Check if the plan was created or retrieved correctly.
    if (!$plan instanceof Plan) {
      throw new \Exception(t("Unable to create subscription plan for recurring donation. Could not complete donation."));
    }
    return $plan;
  }

  public function createCharge() {

  }

  public function createCustomer() {

  }

}