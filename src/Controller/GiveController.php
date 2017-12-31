<?php

namespace Drupal\give\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\give\GiveFormInterface;
use Drupal\give\DonationInterface;
use Drupal\Core\Render\RendererInterface;
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
   * Presents the give form.
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
      $give_form = $this->entityTypeManager()
        ->getStorage('give_form')
        ->load($config->get('default_form'));
      // If there are no forms, do not display the form.
      if (empty($give_form)) {
        if ($this->currentUser()->hasPermission('administer give')) {
          drupal_set_message($this->t('The give form has not been configured. <a href=":add">Add one or more forms</a> .', [
            ':add' => $this->url('give.form_add')]), 'error');
          return [];
        }
        else {
          throw new NotFoundHttpException();
        }
      }
    }

    $donation = $this->entityTypeManager()
      ->getStorage('give_donation')
      ->create([
        'give_form' => $give_form->id(),
      ]);

    $form = $this->entityFormBuilder()->getForm($donation);
    $form['#title'] = $give_form->label();
    $form['#cache']['contexts'][] = 'user.permissions';
    $this->renderer->addCacheableDependency($form, $config);
    return $form;
  }

  /**
   * Presents the second page of the give form which takes donations.
   *
   * @param \Drupal\give\GiveFormInterface $give_form
   *   The give form to use.
   *
   * @param \Drupal\give\DonationInterface $give_donation
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

  /**
   * Presents a preview of the acknowledgement e-mail.
   *
   * @param \Drupal\give\GiveFormInterface $give_form
   *   The give form to use.
   *
   * @return array
   *   The preview as render array as expected by drupal_render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Exception is thrown when user tries to access non existing give form.
   */
  public function givePreviewReply(GiveFormInterface $give_form) {
    $render = [];

    $render['title']['#markup'] = '<h2>' . $this->t('Preview of automatic donation confirmation e-mail for @label forms', ['@label' => $give_form->label()]) . '</h2>';

    $donation = $this->entityTypeManager()
      ->getStorage('give_donation')
      ->create([
        'give_form' => $give_form->id(),
      ]);
    $donation->setAmount(12300);
    $donation->setDonorName('Bud Philanthropist');
    $donation->setDonorMail('bud@example.com');
    $donation->setAddressLine1('1980 Nebraska Ave');
    $donation->setAddressCity('Los Angeles');
    $donation->setAddressState('CA');
    $donation->setAddressCountry('United States');
    $donation->setCardFunding('credit');
    $donation->setCardBrand('Visa');
    $donation->setCardLast4(9876);
    $donation->setCompleted();
    $mail_handler = \Drupal::service('give.mail_handler');
    $user = $this->currentUser();
    $email = $mail_handler->makeDonationReceiptPreview($donation, $user);

    if ($email['receipt']) {
      $rndr = [];
      $rndr['subject']['#markup'] = '<p>' . $this->t('<strong>Subject:</strong> @subject', ['@subject' => $email['receipt']['subject']]) . '</p>';
      $rndr['body']['#markup'] = $email['receipt']['body'];
      $render['receipt'] = $rndr;
    }
    else {
      $render['noreply']['#markup'] = '<p>' . $this->t('This donation form has no automatic acknowledgement reply configured.  <a href="@url">Edit it to add one</a>.', ['@url' => $give_form->toUrl('edit-form')->toString()]) . '</p>';
    }

    $render['separator']['#markup'] = '<hr />';

    $rndr = [];
    $rndr['subject']['#markup'] = '<p>' . $this->t('<strong>Subject:</strong> @subject', ['@subject' => $email['admin_notice']['subject']]) . '</p>';
    $rndr['body']['#markup'] = $email['admin_notice']['body'];

    $render['notice_email'] = $rndr;

    return $render;
  }

}
