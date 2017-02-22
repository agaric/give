<?php

/**
 * @file
 * Contains \Drupal\Tests\give_record\Kernel\GiveRecordFieldTest.
 */

namespace Drupal\Tests\give_record\Kernel;
use Drupal\KernelTests\KernelTestBase;


/**
 * Tests give_record ID field.
 * @group give_record
 */
class GiveRecordFieldTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['give', 'user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('give_donation');
  }

  /**
   * Covers give_record_install().
   */
  public function testGiveIdFieldIsCreated() {
    $this->container->get('module_installer')->install(['give_record']);
    // There should be no updates as give_record_install() should have
    // applied the new field.
    $this->assertTrue(empty($this->container->get('entity.definition_update_manager')->needsUpdates()['give_donation']));
    $this->assertTrue(!empty($this->container->get('entity_field.manager')->getFieldStorageDefinitions('give_donation')['id']));
  }

}
