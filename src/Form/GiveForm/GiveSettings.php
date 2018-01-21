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
      '#description' => $this->t('Enter the value for the "Publishable key" token from your <a href="https://dashboard.stripe.com/account/apikeys">Stripe dashboard</a>.  This is required to take donations via credit or debit card with Stripe.'),
    ];
    $form['stripe_secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stripe secret API key'),
      '#default_value' => $config->get('stripe_secret_key'),
      '#description' => $this->t('Enter the value for the "Secret key" token from your <a href="https://dashboard.stripe.com/account/apikeys">Stripe dashboard</a>.  This is required to take donations via credit or debit card with Stripe.'),
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => t('Advanced settings'),
    ];
    $form['advanced']['log_problems'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable problem logging'),
      '#default_value' => $config->get('log_problems'),
      '#description' => $this->t('Some issues which people may run into trying to donate, such as their browser blocking the external stripe.com scripts, can be spotted and added to the information stored with donation attempts.'),
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
    $config->set('log_problems', $form_state->getValue('log_problems'));
    $config->save();

    drupal_set_message('Updated Give settings.');
  }

}
