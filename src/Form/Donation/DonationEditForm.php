<?php

/**
 * @file
 * Contains \Drupal\give_record\DonationEditForm.
 */

namespace Drupal\give\Form\Donation;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_manager);
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
    /** @var \Drupal\contact\DonationInterface $donation */
    $donation = $this->entity;
    $form = parent::form($form, $form_state, $donation);

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Donor name'),
      '#maxlength' => 255,
      '#default_value' => $donation->getDonorName(),
    );
    $form['mail'] = array(
      '#type' => 'email',
      '#title' => $this->t('Donor email address'),
      '#default_value' => $donation->getDonorMail(),
    );
    $form['amount'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Amount (USD)'),
      '#default_value' => $donation->getAmount(),
      '#disabled' => TRUE,
    );
    $form['recurring'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Recurring'),
      '#default_value' => $donation->recurring(),
      '#disabled' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->logger('give')->notice('The donation %label has been updated.', array(
      '%label' => $this->entity->getLabel(),
      'link' => $this->getEntity()->link($this->t('Edit'), 'edit-form'),
    ));
  }

}
