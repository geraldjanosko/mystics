<?php

/**
 * @file
 * Contains mystics_product.page.inc.
 *
 * Page callback for Mystics product entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Mystics product templates.
 *
 * Default template: mystics_product.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_mystics_product(array &$variables) {
  // Fetch MysticsProduct Entity Object.
  $mystics_product = $variables['elements']['#mystics_product'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
