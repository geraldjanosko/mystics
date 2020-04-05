(function ($, Drupal) {
  Drupal.behaviors.stripeCheckout = {
    attach: function (context, settings) {
      $('body').once('stripeCheckout').each(function() {
        var stripe;
        var userName = $("input[name=user_name]").val();
        var clientSecret = $("input[name=client_secret]").val();
        var stripeData = setupElements();
        document.getElementById("edit-submit").addEventListener('click', function(event) {
          event.preventDefault();
          pay(stripeData.stripe, stripeData.card, clientSecret);
        })

        function setupElements() {
          // For dev purposes only change this live.
          stripe = Stripe('pk_test_XFMBP7zsLsapzcaFE3PkiZKE003YrX4HuE');
          var elements = stripe.elements();

          // Set up Stripe.js and Elements to use in checkout form
          var style = {
            base: {
              color: "#32325d",
              fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
              fontSmoothing: "antialiased",
              fontSize: "16px",
              "::placeholder": {
                color: "#aab7c4"
              }
            },
            invalid: {
              color: "#fa755a",
              iconColor: "#fa755a"
            }
          };
          var card = elements.create("card", { style: style });
          card.mount("#card-element");

          card.addEventListener('change', function(event) {
            var displayError = document.getElementById('card-errors');
            if (event.error) {
              displayError.textContent = event.error.message;
            } else {
              displayError.textContent = '';
            }
          });

          return {
            stripe: stripe,
            card: card
          }
        }

        pay = function(stripe, card, clientSecret) {
          stripe.confirmCardPayment(clientSecret, {
            payment_method: {
              card: card,
              billing_details: {
                name: userName
              }
            },
            setup_future_usage: 'off_session'
          }).then(function(result) {
            if (result.error) {
            } else {
              if (result.paymentIntent.status === 'requires_capture' || result.paymentIntent.status === 'succeeded') {
                $.ajax({
                  method: "POST",
                  url: drupalSettings.path.baseUrl + 'checkout/post-checkout',
                  data: { clientSecret: clientSecret, orderStatus: result.paymentIntent.status}
                }).done(function(data) {
                  if(data.status === 'success') {
                    window.location.replace(drupalSettings.path.baseUrl + 'checkout/success');
                  }
                });
              }
            }
          });
        }

      });
    }
  };
})(jQuery, Drupal);
