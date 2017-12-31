<?php

namespace Drupal\give\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'user_name' formatter.
 *
 * @FieldFormatter(
 *   id = "give_cents_to_dollars",
 *   label = @Translation("Cents to dollars"),
 *   description = @Translation("Display the amount given in cents as dollars."),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class CentsToDollarsFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => '$' . $item / 100,
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getTargetEntityTypeId() === 'give_donation' && $field_definition->getName() === 'amount';
  }

}
