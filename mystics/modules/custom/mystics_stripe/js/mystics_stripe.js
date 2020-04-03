(function ($, Drupal) {
  Drupal.behaviors.stripeCheckout = {
    attach: function (context, settings) {
      $('body').once('stripeCheckout').each(function() {
        var stripe;
        var clientSecret = $("input[name=client_secret]").val();
        var stripeData = setupElements(clientSecret);
        document.getElementById("edit-submit").addEventListener('click', function(event) {
          event.preventDefault();
          pay(stripeData.stripe, stripeData.card, stripeData.clientSecret);
        })

        function setupElements(clientSecret) {
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
            card: card,
            clientSecret: clientSecret
          }
        }

        pay = function(stripe, card, clientSecret) {
          stripe.confirmCardPayment(clientSecret, {
            payment_method: {
              card: card,
              billing_details: {
                name: 'Gerald Janosko'
              }
            },
            setup_future_usage: 'off_session'
          }).then(function(result) {
            if (result.error) {
              // Show error to your customer
              console.log(result.error.message);
            } else {
              if (result.paymentIntent.status === 'succeeded') {
                $.ajax({
                  url: drupalSettings.path.baseUrl + 'checkout/post-checkout'
                }).done(function(data) {
                  console.log(data);
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
