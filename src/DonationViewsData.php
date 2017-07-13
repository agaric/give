<?php

namespace Drupal\give;

use Drupal\views\EntityViewsData;

/**
 * Provides data to integrate donations with Views.
 */
class DonationViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['give_donation']['give_form_label'] = [
      'title' => $this->t('Form'),
      'help' => $this->t('The label of the associated form.'),
      'real field' => 'give_form',
      'field' => [
        'id' => 'give_form',
      ],
    ];

    return $data;
  }

}
