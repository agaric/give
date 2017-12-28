<?php

namespace Drupal\give\Form\GiveForm;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the Give configuration form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class GiveSettings extends ConfigFormBase {

  /**
   * Build the Give settings form.
   *
   * @param array $form
   *   Default form array structure.
   * @param FormStateInterface $form_state
   *   Object containing current form state.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('give.settings');
    $form['stripe_publishable_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stripe publishable API key'),
      '#default_value' => $config->get('stripe_publishable_key'),
      '#description' => $this->t('This is required to take donations via credit or debit card with Stripe.'),
    ];
    $form['stripe_secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stripe secret API key'),
      '#default_value' => $config->get('stripe_secret_key'),
      '#description' => $this->t('This is required to take donations via credit or debit card with Stripe.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Getter method for Form ID.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'give_settings';
  }

  /**
   * Return the editable config names.
   *
   * @return array
   *   The config names.
   */
  protected function getEditableConfigNames() {
    return [
      'give.settings',
    ];
  }

  /**
   * Implements a form submit handler.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Config $config */
    $config = \Drupal::service('config.factory')->getEditable('give.settings');
    $config->set('stripe_publishable_key', $form_state->getValue('stripe_publishable_key'));
    $config->set('stripe_secret_key', $form_state->getValue('stripe_secret_key'));
    $config->save();
  }

}
