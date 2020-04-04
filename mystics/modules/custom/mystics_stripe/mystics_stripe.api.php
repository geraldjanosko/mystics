<?php

/**
 * @file
 * Documentation for Mystics Stripe API.
 */

/**
 * Perform post checkout operations before the cart and Stripe data is cleared.
 *
 * @param array $variables
 *   An associative array of order information, with the following elements:
 *   - 'clientSecret': Stripe secret associated with the PaymentIntent.
 *   - 'intentId' : Stripe intentId that can be used retrieve the PaymentIntent.
 *   - 'shoppingCart' : The shopping cart contents for the order. 
 *   
 */
function hook_mystics_stripe_post_checkout($variables) {
}
