<?php

namespace Drupal\give;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\give\ProblemLog;

/**
 * Render controller for give donations.
 */
class DonationViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);

    $result = ProblemLog::load($entity->uuid());

    if (!$result) {
      $build['extra'] = [
        '#type' => 'markup',
        '#markup' => '<p>No problems recorded.</p>',
        '#weight' => 199,
      ];
      return $build;
    }

    $result = ProblemLog::load($entity->uuid());
    $rows = [];
    foreach ($result as $row) {
      $rows[] = [
        $row->type,
        $row->detail,
        \Drupal::service('date.formatter')->format($row->timestamp, 'short'),
      ];
    }
    $build['errors'] = [
      '#type' => 'table',
      '#caption' => $this->t('Problem log'),
      '#header' => [
        $this->t('Problem type'),
        $this->t('Detail'),
        $this->t('Time'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No problems recorded.'),
    ];

    return $build;
  }

}
