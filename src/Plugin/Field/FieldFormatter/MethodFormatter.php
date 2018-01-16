<?php

namespace Drupal\give\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'user_name' formatter.
 *
 * @TODO probably replace all of this with however 'list_integer' is supposed to work naturally.
 *
 * @FieldFormatter(
 *   id = "give_method",
 *   label = @Translation("Donation method"),
 *   description = @Translation("Display the method, if any, with which a donation is given."),
 *   field_types = {
 *     "list_integer"
 *   }
 * )
 */
class MethodFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();
    $options['long'] = FALSE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['long'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use long version of method name'),
      '#default_value' => $this->getSetting('long'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      /** @var $donation \Drupal\give\DonationInterface */
      if ($donation = $item->getEntity()) {
        if ($this->getSetting('long')) {
          $method_name = $donation->getMethodLongName();
        }
        else {
          $method_name = $donation->getMethodName();
        }
        $elements[$delta] = [
          '#markup' => $method_name,
          '#cache' => [
            'tags' => $donation->getCacheTags(),
          ],
        ];
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getTargetEntityTypeId() === 'give_donation' && $field_definition->getName() === 'method';
  }

}
