<?php

namespace Drupal\Tests\give\Unit;

use Drupal\give\MailHandler;
use Drupal\give\DonationInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\give\MailHandler
 * @group give
 */
class MailHandlerTest extends UnitTestCase {

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $logger;

  /**
   * Mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $mailManager;

  /**
   * Give mail donations service.
   *
   * @var \Drupal\give\MailHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $giveMailHandler;

  /**
   * The give form entity.
   *
   * @var \Drupal\give\GiveFormInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $giveForm;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The user storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->mailManager = $this->getMock('\Drupal\Core\Mail\MailManagerInterface');
    $this->languageManager = $this->getMock('\Drupal\Core\Language\LanguageManagerInterface');
    $this->logger = $this->getMock('\Psr\Log\LoggerInterface');
    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->userStorage = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $this->entityManager->expects($this->any())
      ->method('getStorage')
      ->with('user')
      ->willReturn($this->userStorage);

    $string_translation = $this->getStringTranslationStub();
    $this->giveMailHandler = new MailHandler($this->mailManager, $this->languageManager, $this->logger, $string_translation, $this->entityManager);
    $language = new Language(array('id' => 'en'));

    $this->languageManager->expects($this->any())
      ->method('getDefaultLanguage')
      ->will($this->returnValue($language));

    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue($language));
  }

  /**
   * Tests the children() method with an invalid key.
   *
   * @expectedException \Drupal\give\MailHandlerException
   * @expectedExceptionDonation Unable to determine donation recipient
   *
   * @covers ::sendMailDonations
   */
  public function testInvalidRecipient() {
    $donation = $this->getMock('\Drupal\give\DonationInterface');
    $donation->expects($this->once())
      ->method('getGiveForm')
      ->willReturn($this->getMock('\Drupal\give\GiveFormInterface'));
    $donor = $this->getMock('\Drupal\Core\Session\AccountInterface');
    $this->userStorage->expects($this->any())
      ->method('load')
      ->willReturn($donor);
    // User IDs 1 and 0 have special implications, use 3 instead.
    $donor->expects($this->any())
      ->method('id')
      ->willReturn(3);
    $donor->expects($this->once())
      ->method('isAnonymous')
      ->willReturn(FALSE);
    $this->giveMailHandler->sendMailDonations($donation, $donor);
  }

  /**
   * Tests the sendMailDonations method.
   *
   * @dataProvider getSendMailDonations
   *
   * @covers ::sendMailDonations
   */
  public function testSendMailDonations(DonationInterface $donation, AccountInterface $donor, $results) {
    $this->logger->expects($this->once())
      ->method('notice');
    $this->mailManager->expects($this->any())
      ->method('mail')
      ->willReturnCallback(
        function($module, $key, $to, $langcode, $params, $from) use (&$results) {
          $result = array_shift($results);
          $this->assertEquals($module, $result['module']);
          $this->assertEquals($key, $result['key']);
          $this->assertEquals($to, $result['to']);
          $this->assertEquals($langcode, $result['langcode']);
          $this->assertArrayEquals($params, $result['params']);
          $this->assertEquals($from, $result['from']);
        });
    $this->userStorage->expects($this->any())
      ->method('load')
      ->willReturn(clone $donor);
    $this->giveMailHandler->sendMailDonations($donation, $donor);
  }

  /**
   * Data provider for ::testSendMailDonations.
   */
  public function getSendMailDonations() {
    $data = array();
    $recipients = array('admin@drupal.org', 'user@drupal.org');
    $default_result = array(
      'module' => 'give',
      'key' => '',
      'to' => implode(', ', $recipients),
      'langcode' => 'en',
      'params' => array(),
      'from' => 'anonymous@drupal.org',
    );
    $results = array();
    $donation = $this->getAnonymousMockDonation($recipients, '');
    $donor = $this->getMockDonor();
    $result = array(
      'key' => 'page_mail',
      'params' => array(
        'give_donation' => $donation,
        'donor' => $donor,
        'give_form' => $donation->getGiveForm(),
      ),
    );
    $results[] = $result + $default_result;
    $data[] = array($donation, $donor, $results);

    $results = array();
    $donation = $this->getAnonymousMockDonation($recipients, 'reply');
    $donor = $this->getMockDonor();
    $result = array(
      'key' => 'page_mail',
      'params' => array(
        'give_donation' => $donation,
        'donor' => $donor,
        'give_form' => $donation->getGiveForm(),
      ),
    );
    $results[] = $result + $default_result;
    $result['key'] = 'page_receipt';
    $result['to'] = 'anonymous@drupal.org';
    $result['from'] = NULL;
    $results[] = $result + $default_result;
    $data[] = array($donation, $donor, $results);

    $results = array();
    $donation = $this->getAnonymousMockDonation($recipients, '', TRUE);
    $donor = $this->getMockDonor();
    $result = array(
      'key' => 'page_mail',
      'params' => array(
        'give_donation' => $donation,
        'donor' => $donor,
        'give_form' => $donation->getGiveForm(),
      ),
    );
    $results[] = $result + $default_result;
    $data[] = array($donation, $donor, $results);

    $results = array();
    $donation = $this->getAnonymousMockDonation($recipients, 'reply', TRUE);
    $donor = $this->getMockDonor();
    $result = array(
      'key' => 'page_mail',
      'params' => array(
        'give_donation' => $donation,
        'donor' => $donor,
        'give_form' => $donation->getGiveForm(),
      ),
    );
    $results[] = $result + $default_result;
    $result['key'] = 'page_receipt';
    $result['from'] = NULL;
    $results[] = $result + $default_result;
    $data[] = array($donation, $donor, $results);

    //For authenticated user.
    $results = array();
    $donation = $this->getAuthenticatedMockDonation();
    $donor = $this->getMockDonor(FALSE, 'user@drupal.org');
    $result = array(
      'module' => 'give',
      'key' => 'user_mail',
      'to' => 'user2@drupal.org',
      'langcode' => 'en',
      'params' => array(
        'give_donation' => $donation,
        'donor' => $donor,
      ),
      'from' => 'user@drupal.org',
    );
    $results[] = $result;
    $data[] = array($donation, $donor, $results);

    $results = array();
    $donation = $this->getAuthenticatedMockDonation(TRUE);
    $donor = $this->getMockDonor(FALSE, 'user@drupal.org');
    $result = array(
      'module' => 'give',
      'key' => 'user_mail',
      'to' => 'user2@drupal.org',
      'langcode' => 'en',
      'params' => array(
        'give_donation' => $donation,
        'donor' => $donor,
      ),
      'from' => 'user@drupal.org',
    );
    $results[] = $result;

    $data[] = array($donation, $donor, $results);

    return $data;
  }

  /**
   * Builds a mock donor on given scenario.
   *
   * @param bool $anonymous
   *   TRUE if the donor is anonymous.
   * @param string $mail_address
   *   The mail address of the user.
   *
   * @return \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   *   Mock donor for testing.
   */
  protected function getMockDonor($anonymous = TRUE, $mail_address = 'anonymous@drupal.org') {
    $donor = $this->getMock('\Drupal\Core\Session\AccountInterface');
    $donor->expects($this->once())
      ->method('isAnonymous')
      ->willReturn($anonymous);
    $donor->expects($this->any())
      ->method('getEmail')
      ->willReturn($mail_address);
    $donor->expects($this->any())
      ->method('getUsername')
      ->willReturn('user');
    // User ID 1 has special implications, use 3 instead.
    $donor->expects($this->any())
      ->method('id')
      ->willReturn($anonymous ? 0 : 3);
    if ($anonymous) {
      // Anonymous user values set in params include updated values for name and
      // mail.
      $donor->name = 'Anonymous (not verified)';
      $donor->mail = 'anonymous@drupal.org';
    }
    return $donor;
  }

  /**
   * Builds a mock donation from anonymous user.
   *
   * @param array $recipients
   *   An array of recipient email addresses.
   * @param bool $auto_reply
   *   TRUE if auto reply is enable.
   * @param bool $recurring
   *   TRUE if a donation should recur monthly, FALSE if not.
   *
   * @return \Drupal\give\DonationInterface|\PHPUnit_Framework_MockObject_MockObject
   *   Mock donation for testing.
   */
  protected function getAnonymousMockDonation($recipients, $auto_reply, $recurring = FALSE) {
    $donation = $this->getMock('\Drupal\give\DonationInterface');
    $donation->expects($this->any())
      ->method('getDonorName')
      ->willReturn('Anonymous');
    $donation->expects($this->once())
      ->method('getDonorMail')
      ->willReturn('anonymous@drupal.org');
    $donation->expects($this->once())
      ->method('recurring')
      ->willReturn($recurring);
    $donation->expects($this->any())
      ->method('getGiveForm')
      ->willReturn($this->getMockGiveForm($recipients, $auto_reply));
    return $donation;
  }

  /**
   * Builds a mock donation from authenticated user.
   *
   * @param bool $recurring
   *   TRUE if a donation should recur monthly, FALSE if not.
   *
   * @return \Drupal\give\DonationInterface|\PHPUnit_Framework_MockObject_MockObject
   *   Mock donation for testing.
   */
  protected function getAuthenticatedMockDonation($recurring = FALSE) {
    $donation = $this->getMock('\Drupal\give\DonationInterface');
    $donation->expects($this->once())
      ->method('recurring')
      ->willReturn($recurring);
    $recipient = $this->getMock('\Drupal\user\UserInterface');
    $recipient->expects($this->once())
      ->method('getEmail')
      ->willReturn('user2@drupal.org');
    $recipient->expects($this->once())
      ->method('getUsername')
      ->willReturn('user2');
    $recipient->expects($this->once())
      ->method('getPreferredLangcode')
      ->willReturn('en');
    $donation->expects($this->any())
      ->method('getGiveForm')
      ->willReturn($this->getMockGiveForm('user2@drupal.org', FALSE));
    return $donation;
  }

  /**
   * Builds a mock donation on given scenario.
   *
   * @param array $recipients
   *   An array of recipient email addresses.
   * @param string $reply
   *   A reply receipt to send to the donor.
   *
   * @return \Drupal\give\GiveFormInterface|\PHPUnit_Framework_MockObject_MockObject
   *   Mock donation for testing.
   */
  protected function getMockGiveForm($recipients, $reply) {
    $give_form = $this->getMock('\Drupal\give\GiveFormInterface');
    $give_form->expects($this->once())
      ->method('getRecipients')
      ->willReturn($recipients);
    $give_form->expects($this->once())
      ->method('getReply')
      ->willReturn($reply);

    return $give_form;
  }

}
