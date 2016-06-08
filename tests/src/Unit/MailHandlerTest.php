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
      ->method('isPersonal')
      ->willReturn(TRUE);
    $donation->expects($this->once())
      ->method('getPersonalRecipient')
      ->willReturn(NULL);
    $donation->expects($this->once())
      ->method('getGiveForm')
      ->willReturn($this->getMock('\Drupal\give\GiveFormInterface'));
    $sender = $this->getMock('\Drupal\Core\Session\AccountInterface');
    $this->userStorage->expects($this->any())
      ->method('load')
      ->willReturn($sender);
    // User IDs 1 and 0 have special implications, use 3 instead.
    $sender->expects($this->any())
      ->method('id')
      ->willReturn(3);
    $sender->expects($this->once())
      ->method('isAnonymous')
      ->willReturn(FALSE);
    $this->giveMailHandler->sendMailDonations($donation, $sender);
  }

  /**
   * Tests the sendMailDonations method.
   *
   * @dataProvider getSendMailDonations
   *
   * @covers ::sendMailDonations
   */
  public function testSendMailDonations(DonationInterface $donation, AccountInterface $sender, $results) {
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
      ->willReturn(clone $sender);
    $this->giveMailHandler->sendMailDonations($donation, $sender);
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
    $sender = $this->getMockSender();
    $result = array(
      'key' => 'page_mail',
      'params' => array(
        'give_donation' => $donation,
        'sender' => $sender,
        'give_form' => $donation->getGiveForm(),
      ),
    );
    $results[] = $result + $default_result;
    $data[] = array($donation, $sender, $results);

    $results = array();
    $donation = $this->getAnonymousMockDonation($recipients, 'reply');
    $sender = $this->getMockSender();
    $result = array(
      'key' => 'page_mail',
      'params' => array(
        'give_donation' => $donation,
        'sender' => $sender,
        'give_form' => $donation->getGiveForm(),
      ),
    );
    $results[] = $result + $default_result;
    $result['key'] = 'page_autoreply';
    $result['to'] = 'anonymous@drupal.org';
    $result['from'] = NULL;
    $results[] = $result + $default_result;
    $data[] = array($donation, $sender, $results);

    $results = array();
    $donation = $this->getAnonymousMockDonation($recipients, '', TRUE);
    $sender = $this->getMockSender();
    $result = array(
      'key' => 'page_mail',
      'params' => array(
        'give_donation' => $donation,
        'sender' => $sender,
        'give_form' => $donation->getGiveForm(),
      ),
    );
    $results[] = $result + $default_result;
    $result['key'] = 'page_copy';
    $result['to'] = 'anonymous@drupal.org';
    $results[] = $result + $default_result;
    $data[] = array($donation, $sender, $results);

    $results = array();
    $donation = $this->getAnonymousMockDonation($recipients, 'reply', TRUE);
    $sender = $this->getMockSender();
    $result = array(
      'key' => 'page_mail',
      'params' => array(
        'give_donation' => $donation,
        'sender' => $sender,
        'give_form' => $donation->getGiveForm(),
      ),
    );
    $results[] = $result + $default_result;
    $result['key'] = 'page_copy';
    $result['to'] = 'anonymous@drupal.org';
    $results[] = $result + $default_result;
    $result['key'] = 'page_autoreply';
    $result['from'] = NULL;
    $results[] = $result + $default_result;
    $data[] = array($donation, $sender, $results);

    //For authenticated user.
    $results = array();
    $donation = $this->getAuthenticatedMockDonation();
    $sender = $this->getMockSender(FALSE, 'user@drupal.org');
    $result = array(
      'module' => 'give',
      'key' => 'user_mail',
      'to' => 'user2@drupal.org',
      'langcode' => 'en',
      'params' => array(
        'give_donation' => $donation,
        'sender' => $sender,
        'recipient' => $donation->getPersonalRecipient(),
      ),
      'from' => 'user@drupal.org',
    );
    $results[] = $result;
    $data[] = array($donation, $sender, $results);

    $results = array();
    $donation = $this->getAuthenticatedMockDonation(TRUE);
    $sender = $this->getMockSender(FALSE, 'user@drupal.org');
    $result = array(
      'module' => 'give',
      'key' => 'user_mail',
      'to' => 'user2@drupal.org',
      'langcode' => 'en',
      'params' => array(
        'give_donation' => $donation,
        'sender' => $sender,
        'recipient' => $donation->getPersonalRecipient(),
      ),
      'from' => 'user@drupal.org',
    );
    $results[] = $result;

    $result['key'] = 'user_copy';
    $result['to'] = $result['from'];
    $results[] = $result;
    $data[] = array($donation, $sender, $results);

    return $data;
  }

  /**
   * Builds a mock sender on given scenario.
   *
   * @param bool $anonymous
   *   TRUE if the sender is anonymous.
   * @param string $mail_address
   *   The mail address of the user.
   *
   * @return \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   *   Mock sender for testing.
   */
  protected function getMockSender($anonymous = TRUE, $mail_address = 'anonymous@drupal.org') {
    $sender = $this->getMock('\Drupal\Core\Session\AccountInterface');
    $sender->expects($this->once())
      ->method('isAnonymous')
      ->willReturn($anonymous);
    $sender->expects($this->any())
      ->method('getEmail')
      ->willReturn($mail_address);
    $sender->expects($this->any())
      ->method('getUsername')
      ->willReturn('user');
    // User ID 1 has special implications, use 3 instead.
    $sender->expects($this->any())
      ->method('id')
      ->willReturn($anonymous ? 0 : 3);
    if ($anonymous) {
      // Anonymous user values set in params include updated values for name and
      // mail.
      $sender->name = 'Anonymous (not verified)';
      $sender->mail = 'anonymous@drupal.org';
    }
    return $sender;
  }

  /**
   * Builds a mock donation from anonymous user.
   *
   * @param array $recipients
   *   An array of recipient email addresses.
   * @param bool $auto_reply
   *   TRUE if auto reply is enable.
   * @param bool $copy_sender
   *   TRUE if a copy should be sent, FALSE if not.
   *
   * @return \Drupal\give\DonationInterface|\PHPUnit_Framework_MockObject_MockObject
   *   Mock donation for testing.
   */
  protected function getAnonymousMockDonation($recipients, $auto_reply, $copy_sender = FALSE) {
    $donation = $this->getMock('\Drupal\give\DonationInterface');
    $donation->expects($this->any())
      ->method('getSenderName')
      ->willReturn('Anonymous');
    $donation->expects($this->once())
      ->method('getSenderMail')
      ->willReturn('anonymous@drupal.org');
    $donation->expects($this->any())
      ->method('isPersonal')
      ->willReturn(FALSE);
    $donation->expects($this->once())
      ->method('copySender')
      ->willReturn($copy_sender);
    $donation->expects($this->any())
      ->method('getGiveForm')
      ->willReturn($this->getMockGiveForm($recipients, $auto_reply));
    return $donation;
  }

  /**
   * Builds a mock donation from authenticated user.
   *
   * @param bool $copy_sender
   *   TRUE if a copy should be sent, FALSE if not.
   *
   * @return \Drupal\give\DonationInterface|\PHPUnit_Framework_MockObject_MockObject
   *   Mock donation for testing.
   */
  protected function getAuthenticatedMockDonation($copy_sender = FALSE) {
    $donation = $this->getMock('\Drupal\give\DonationInterface');
    $donation->expects($this->any())
      ->method('isPersonal')
      ->willReturn(TRUE);
    $donation->expects($this->once())
      ->method('copySender')
      ->willReturn($copy_sender);
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
      ->method('getPersonalRecipient')
      ->willReturn($recipient);
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
   * @param string $auto_reply
   *   An auto-reply donation to send to the donation author.
   *
   * @return \Drupal\give\GiveFormInterface|\PHPUnit_Framework_MockObject_MockObject
   *   Mock donation for testing.
   */
  protected function getMockGiveForm($recipients, $auto_reply) {
    $give_form = $this->getMock('\Drupal\give\GiveFormInterface');
    $give_form->expects($this->once())
      ->method('getRecipients')
      ->willReturn($recipients);
    $give_form->expects($this->once())
      ->method('getReply')
      ->willReturn($auto_reply);

    return $give_form;
  }

}
