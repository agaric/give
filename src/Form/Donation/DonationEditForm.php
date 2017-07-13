<?php

namespace Drupal\give\Form\Donation;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for give donation edit forms.
 */
class DonationEditForm extends ContentEntityForm {

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a DonationEditForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_type_manager);
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\give\DonationInterface $donation */
    $donation = $this->entity;
    $form = parent::form($form, $form_state, $donation);

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Donor name'),
      '#maxlength' => 255,
      '#default_value' => $donation->getDonorName(),
    ];
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Donor email address'),
      '#default_value' => $donation->getDonorMail(),
    ];
    $form['amount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amount (USD)'),
      '#default_value' => round($donation->getAmount()/100, 2),
      '#disabled' => TRUE,
    ];
    $form['recurring'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Recurring'),
      '#default_value' => $donation->getRecurrenceIntervalCount(),
      '#disabled' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->logger('give')->notice('The donation %label has been updated.', [
      '%label' => $this->entity->getLabel(),
      'link' => $this->getEntity()->link($this->t('Edit'), 'edit-form'),
    ]);
  }

}
