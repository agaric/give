/**
 * @file
 *   Javascript for the Give module for interacting with Stripe.js.
 */
(function (Drupal, settings, $) {
  loadStripe();

  function loadStripe() {
    if (!settings.give.stripe_publishable_key) {
      // "1" is the value of the constant GIVE_WITH_STRIPE.
      $('#edit-method-1').attr('disabled', true);
      $('label[for="edit-method-1"]').append('<div class="form--inline-feedback form--inline-feedback--error visible">This form cannot take credit/debit card payments until the Stripe publishable key is set by the site administrator.</div>');
      logProblem('Misconfiguration', 'Administrator must set a Stripe publishable key to use Stripe.');
      return;
    }
    if (typeof(Stripe) === 'undefined') {
      // "1" is the value of the constant GIVE_WITH_STRIPE.
      $('#edit-method-1').attr('disabled', true);
      $('label[for="edit-method-1"]').append('<div class="form--inline-feedback form--inline-feedback--error visible">Your browser appears to be blocking Stripe.com, which must be enabled for us to process debit or credit card donations.  Please check any tracker blockers such as Privacy Badger or uBlock Origin and be sure to allow js.stripe.com.  Then reload this page and be sure to allow all additional stripe.com domains which request connection.</div>');
      logProblem('Stripe blocked', 'Stripe did not load; showed user error message with mention of tracker blockers.');
      return;
    }
    var stripe = Stripe(settings.give.stripe_publishable_key);
    var elements = stripe.elements();
    var card = elements.create('card', {
      hidePostalCode: true,
      style: {
        base: {
          iconColor: 'red',
          color: 'green',
          lineHeight: '2em',
          fontWeight: 400,
          fontFamily: '"Helvetica Neue", "Helvetica", sans-serif',
          fontSize: '15px',
          '::placeholder': {
            color: '#ccc',
          }
        },
      }
    });
    card.mount('#stripe-card-element');
    card.on('change', function(event) {
      handleResponse(event);
    });
  }

  function handleResponse(result) {
    var successElement = document.querySelector('#stripe-card-success');
    var errorElement = document.querySelector('#stripe-card-errors');
    successElement.classList.remove('visible');
    errorElement.classList.remove('visible');

    if (result.token) {
      // Prevent duplicate submissions by double-clickers.
      document.getElementById('edit-submit').disabled = true;
      // Insert the token ID into the form so it gets submitted to the server.
      var form = document.querySelector('.give-donation-form');
      form.querySelector('input[name=stripe_token]').value = result.token.id;
      // Submit the form, completing the action the user expected after pressing submit.
      form.submit();
    } else if (result.error) {
      errorElement.textContent = result.error.message;
      errorElement.classList.add('visible');
    }
  }

  function logProblem(type, detail) {
    $.ajax({
      type: 'POST',
      cache: false,
      url: drupalSettings.give.problem_log.url,
      data: {
        'donation_uuid': drupalSettings.give.problem_log.donation_uuid,
        'type': type,
        'detail': detail
      }
    });
  }

  document.querySelector('.give-donation-form').addEventListener('submit', function(e) {
    // Only try to process the card if card method ('1') is selected.
    if ($('input[name=method]:checked').val() == 1) {
      e.preventDefault();
      var form = document.querySelector('.give-donation-form');
      var extraDetails = {
        name: form.querySelector('input[name=donor_name_for_stripe]').value
      };
      var address_zip = form.querySelector('input[name=address-zip]');
      if (address_zip) {
        extraDetails.address_zip = address_zip.value;
      }
      stripe.createToken(card, extraDetails).then(handleResponse);
    }
  });

})(Drupal, drupalSettings, jQuery);
