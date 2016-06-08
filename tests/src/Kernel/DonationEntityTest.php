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
    $this->assertEqual($donation->getAmount(), 0);
    $this->assertEqual($donation->getDonorName(), '');
    $this->assertEqual($donation->getDonorMail(), '');
    $this->assertFalse($donation->recurring());

    // Check for default values.
    $this->assertEqual('tzedakah', $donation->getGiveForm()->id());

    // Set some values and check for them afterwards.
    $donation->setAmount(4200);
    $donation->setDonorName('donor_name');
    $donation->setDonorMail('donor_mail');
    $donation->setRecurring(TRUE);

    $this->assertEqual($donation->getAmount(), 4200);
    $this->assertEqual($donation->getDonorName(), 'donor_name');
    $this->assertEqual($donation->getDonorMail(), 'donor_mail');
    $this->assertTrue($donation->recurring());

    $no_access_user = $this->createUser(['uid' => 2]);
    $access_user = $this->createUser(['uid' => 3], ['access give forms']);
    $admin = $this->createUser(['uid' => 4], ['administer give']);

    $this->assertFalse(\Drupal::entityManager()->getAccessControlHandler('give_donation')->createAccess(NULL, $no_access_user));
    $this->assertTrue(\Drupal::entityManager()->getAccessControlHandler('give_donation')->createAccess(NULL, $access_user));
    $this->assertTrue($donation->access('edit', $admin));
    $this->assertFalse($donation->access('edit', $access_user));
  }

}
