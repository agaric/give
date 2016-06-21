/**
 * @file
 *   Javascript for the Give module for interacting with Stripe.js.
 */
(function (Drupal, settings) {
  if (settings.give.stripe_publishable_key) {
    Stripe.setPublishableKey(settings.give.stripe_publishable_key);
  }
  else {
    alert('This will not be able to take Stripe payments until the Stripe publishable key is set.');
  }

})(Drupal, drupalSettings);
