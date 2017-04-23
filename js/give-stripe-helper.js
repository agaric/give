/**
 * @file
 *   Javascript for the Give module for interacting with Stripe.js.
 */
(function (Drupal, settings, $) {
  if (settings.give.stripe_publishable_key) {
    var stripe = Stripe('pk_test_bUh7EpEkPzKqFOptiK9x7TKi'); // settings.give.stripe_publishable_key
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
  }
  else {
    alert('This form cannot take credit/debit card payments until the Stripe publishable key is set.');
  }

    function handleResponse(result) {
      console.log('handling!');
      var successElement = document.querySelector('#stripe-card-success');
      var errorElement = document.querySelector('#stripe-card-errors');
      successElement.classList.remove('visible');
      errorElement.classList.remove('visible');

      if (result.token) {
        // Get the token ID:
        var token = result.token.id;
        // Insert the token ID into the form so it gets submitted to the server:
        var form = document.querySelector('.give-donation-form');
        form.querySelector('input[name=stripe_token]').value = token;

        // Submit the form:
        form.submit();
        // Use the token to create a charge or a customer
        // https://stripe.com/docs/charges
        successElement.querySelector('.token').textContent = result.token.id;
        successElement.classList.add('visible');
      } else if (result.error) {
        errorElement.textContent = result.error.message;
        errorElement.classList.add('visible');
/*
        // Show the errors on the form:
        var pre_span = '<div role="contentinfo" aria-label="Error message" class="messages messages--error"><div role="alert">';
        var post_span = '</div></div>';
        $form.find('.payment-errors').html(pre_span + response.error.message + post_span);
        $form.find('.submit').prop('disabled', false); // Re-enable submission
*/
      }
    }

    card.on('change', function(event) {
        console.log('telling to handle');
        handleResponse(event);
    });

    document.querySelector('.give-donation-form').addEventListener('submit', function(e) {
        // Only try to process the card if card method ('1') is selected.
        if ($('input[name=method]:checked').val() == 1) {
          e.preventDefault();
          var form = document.querySelector('.give-donation-form');
          var extraDetails = {
            // name: form.querySelector('input[name=cardholder-name]').value,
            // address_zip: form.querySelector('input[name=address-zip]').value
          };
          stripe.createToken(card, extraDetails).then(handleResponse);
        }
    });

/*
  $form.submit(function(event) {
      // Disable the submit button to prevent repeated clicks:
      $form.find('.submit').prop('disabled', true);

      // Prevent the form from being submitted:
      return false;
  });
*/

})(Drupal, drupalSettings, jQuery);
