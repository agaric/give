<?php

namespace Drupal\give\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\give\GiveFormInterface;
use Drupal\give\DonationInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for give routes.
 */
class GiveController extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a GiveController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Presents the site-wide give form.
   *
   * @param \Drupal\give\GiveFormInterface $give_form
   *   The give form to use.
   *
   * @return array
   *   The form as render array as expected by drupal_render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Exception is thrown when user tries to access non existing default
   *   give form.
   */
  public function giveSitePage(GiveFormInterface $give_form = NULL) {
    $config = $this->config('give.settings');

    // Use the default form if no form has been passed.
    if (empty($give_form)) {
      $give_form = $this->entityManager()
        ->getStorage('give_form')
        ->load($config->get('default_form'));
      // If there are no forms, do not display the form.
      if (empty($give_form)) {
        if ($this->currentUser()->hasPermission('administer give')) {
          drupal_set_message($this->t('The give form has not been configured. <a href=":add">Add one or more forms</a> .', array(
            ':add' => $this->url('give.form_add'))), 'error');
          return array();
        }
        else {
          throw new NotFoundHttpException();
        }
      }
    }

    $donation = $this->entityManager()
      ->getStorage('give_donation')
      ->create(array(
        'give_form' => $give_form->id(),
      ));

    $form = $this->entityFormBuilder()->getForm($donation);
    $form['#title'] = $give_form->label();
    $form['#cache']['contexts'][] = 'user.permissions';
    $this->renderer->addCacheableDependency($form, $config);
    return $form;
  }

  /**
   * Presents the site-wide give form.
   *
   * @param \Drupal\give\GiveFormInterface $give_form
   *   The give form to use.
   *
   * @param \Drupal\give\DonationInterface  $donation
   *   The donation for which payment is to be processed.
   *
   * @return array
   *   The form as render array as expected by drupal_render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Exception is thrown when user tries to access non existing donation or
   *   give form.
   */
  public function takeDonation(GiveFormInterface $give_form, DonationInterface $give_donation) {
    $config = $this->config('give.settings');

    $form = $this->entityFormBuilder()->getForm($give_donation, 'payment');
    $form['#title'] = $give_form->label();
    $form['#cache']['contexts'][] = 'user.permissions';
    $this->renderer->addCacheableDependency($form, $config);
    return $form;
  }

}
