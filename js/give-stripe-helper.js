/**
 * @file
 *   Javascript for the Give module for interacting with Stripe.js.
 */
(function (Drupal, settings, $) {
  if (settings.give.stripe_publishable_key) {
    Stripe.setPublishableKey(settings.give.stripe_publishable_key);
  }
  else {
    alert('This will not be able to take credit/debit card payments until the Stripe publishable key is set.');
  }

  var $form = $('#give-donation-donate-payment-form');
  $form.submit(function(event) {
    // Only try to process the card if card method ('1') is selected.
    if ($('input[name=method').val() == 1) {
      // Disable the submit button to prevent repeated clicks:
      $form.find('.submit').prop('disabled', true);

      // Request a token from Stripe:
      Stripe.card.createToken($form, stripeResponseHandler);
      // testStripeCardCreateToken($form, stripeResponseHandler);

      // Prevent the form from being submitted:
      return false;
    }
  });

function stripeResponseHandler(status, response) {

  var $form = $('#give-donation-donate-payment-form');

  if (response.error) {

    // Show the errors on the form:
    $form.find('.payment-errors').text(response.error.message);
    $form.find('.submit').prop('disabled', false); // Re-enable submission

  } else { // Token was created!

    // Get the token ID:
    var token = response.id;
    // Insert the token ID into the form so it gets submitted to the server:
    $form.find('input[name=stripe_token]').val(token);

    // Submit the form:
    $form.get(0).submit();
  }
  }

  function testStripeCardCreateToken($form, $stripeResponseHandler) {
    alert('hi stranger');
    var status = 200;
    var response = {
      id: "tok_u5dg20Gra", // Token identifier
      card: {}, // Dictionary of the card used to create the token
      created: 1465923241, // Timestamp of when token was created
      currency: "usd", // Currency that the token was created in
      livemode: false, // Whether this token was created with a live API key
      object: "token", // Type of object, always "token"
      used: false // Whether this token has been used
    };
    $stripeResponseHandler(status, response);
  }

})(Drupal, drupalSettings, jQuery);
