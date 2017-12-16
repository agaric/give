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
 *   id = "give_recurrence",
 *   label = @Translation("Recurrence"),
 *   description = @Translation("Display the automatic frequency, if any, with which a donation is given."),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class RecurrenceFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
  public static function defaultSettings() {
    $options = parent::defaultSettings();

    $options['link_to_entity'] = TRUE;
    return $options;
  }
   */

  /**
   * {@inheritdoc}
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['link_to_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to the user'),
      '#default_value' => $this->getSetting('link_to_entity'),
    ];

    return $form;
  }
   */

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      /** @var $donation \Drupal\give\DonationInterface */
      if ($donation = $item->getEntity()) {
        $elements[$delta] = [
          '#markup' => $donation->getRecurrence(),
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
    return $field_definition->getTargetEntityTypeId() === 'give_donation' && $field_definition->getName() === 'recurring';
  }

}
