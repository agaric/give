<?php

namespace Drupal\give\Form\GiveForm;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of give form entities.
 *
 * @see \Drupal\give\Entity\GiveForm
 */
class GiveFormListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['form'] = t('Form');
    $header['recipients'] = t('Recipients');
    $header['selected'] = t('Default');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['form'] = $entity->link(NULL, 'canonical');
    $row['recipients']['data'] = [
      '#theme' => 'item_list',
      '#items' => $entity->getRecipients(),
      '#context' => ['list_style' => 'comma-list'],
    ];
    $default_form = \Drupal::config('give.settings')->get('default_form');
    $row['selected'] = ($default_form == $entity->id() ? t('Yes') : t('No'));
    return $row + parent::buildRow($entity);
  }

}
