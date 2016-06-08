<?php

namespace Drupal\Tests\give\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the donation entity class.
 *
 * @group give
 * @see \Drupal\give\Entity\Donation
 */
class DonationEntityTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'system',
    'give',
    'field',
    'user',
    'give_test',
  );

  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('give', 'give_test'));
  }

  /**
   * Test some of the methods.
   */
  public function testDonationMethods() {
    $donation_storage = $this->container->get('entity.manager')->getStorage('give_donation');
    $donation = $donation_storage->create(array('give_form' => 'feedback'));

    // Check for empty values first.
    $this->assertEqual($donation->getDonation(), '');
    $this->assertEqual($donation->getSenderName(), '');
    $this->assertEqual($donation->getSenderMail(), '');
    $this->assertFalse($donation->copySender());

    // Check for default values.
    $this->assertEqual('feedback', $donation->getGiveForm()->id());
    $this->assertFalse($donation->isPersonal());

    // Set some values and check for them afterwards.
    $donation->setDonation('welcome_donation');
    $donation->setSenderName('sender_name');
    $donation->setSenderMail('sender_mail');
    $donation->setCopySender(TRUE);

    $this->assertEqual($donation->getDonation(), 'welcome_donation');
    $this->assertEqual($donation->getSenderName(), 'sender_name');
    $this->assertEqual($donation->getSenderMail(), 'sender_mail');
    $this->assertTrue($donation->copySender());

    $no_access_user = $this->createUser(['uid' => 2]);
    $access_user = $this->createUser(['uid' => 3], ['access site-wide give form']);
    $admin = $this->createUser(['uid' => 4], ['administer give forms']);

    $this->assertFalse(\Drupal::entityManager()->getAccessControlHandler('give_donation')->createAccess(NULL, $no_access_user));
    $this->assertTrue(\Drupal::entityManager()->getAccessControlHandler('give_donation')->createAccess(NULL, $access_user));
    $this->assertTrue($donation->access('edit', $admin));
    $this->assertFalse($donation->access('edit', $access_user));
  }

}
