<?php

namespace App\Tests\e2e\Service;


use Doctrine\ORM\EntityManagerInterface;
use Facebook\WebDriver\WebDriverDimension;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;

class ChatControllerTest extends PantherTestCase
{
    private Client $client;
    private KernelInterface $appKernel;
    private EntityManagerInterface $entityManager;
    protected DatabaseToolCollection $databaseTool;


    public function setUp(): void
    {
        $container = static::getContainer();
        $size = new WebDriverDimension(1920, 2980);
        $this->appKernel = $container->get(KernelInterface::class);
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->client = static::createPantherClient(['browser' => static::FIREFOX]);
        $this->client->manage()->window()->setSize($size);
    }

    public function tearDown(): void
    {
        $crawler = $this->client->request('GET', '/logout');
        $crawler = $this->client->request('GET', '/login');
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->submitForm('Login', [
            '_username' => 'test_env_landlord@test.pl',
            '_password' => 'test12'
        ]);

        $crawler = $this->client->request('GET', '/panel/flats');
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->clickLink('See more');
        $this->client->waitForInvisibility('#spinner');
        $crawler->filter('.remove-tenant-from-flat')->click();
        $crawler = $this->client->refreshCrawler();
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->clickLink('Delete');
        $this->client->waitForInvisibility('#spinner');
    }

    public function testChatBetweenTwoUsers()
    {
        $crawler = $this->client->request('GET', '/logout');

        // authenticating user
        $crawler = $this->client->request('GET', '/login');
        $this->client->waitForInvisibility('#spinner');

        $crawler = $this->client->submitForm('Login', [
            '_username' => 'test_env_tenant@test.pl',
            '_password' => 'test12'
        ]);

        // check if user was logged in correctly and have access to /panel/chat
        $crawler = $this->client->request('GET', '/panel/chat');
        $this->client->waitForInvisibility('#spinner');

        $this->assertSame(self::$baseUri . '/panel/chat', $crawler->getUri());

        $dataReceiverId = $crawler->filter('.chat-window')->attr('data-receiver-id');
        $dataSenderId = $crawler->filter('.chat-window')->attr('data-sender-id');
        $dataReceiverName = $crawler->filter('.chat-window')->attr('data-receiver-name');
        $dataSenderName = $crawler->filter('.chat-window')->attr('data-sender-name');
        $contactName = $crawler->filter('.contact-name')->text();

        $this->assertEquals(2, $dataReceiverId);
        $this->assertEquals(3, $dataSenderId);
        $this->assertEquals('Landlord user', $dataReceiverName);
        $this->assertEquals('Tenant user', $dataSenderName);
        $this->assertEquals('Landlord user', $contactName);

        $date = new \DateTime('now');
        $this->client->executeScript('document.getElementById("chat-input-box").value = "First test message";');
        $this->client->executeScript('document.querySelector(".send-message").click()');
        // TODO: Doesn't work currently - problem with retrieving panther's console logs
//        $consoleLogs = $this->client->getWebDriver()->manage()->getLog('browser');

//        dd($consoleLogs);

//        $this->client->takeScreenshot('screen1.png');
//        $messageSender = $crawler->filter('.sender-message .card-header .fw-bold')->text();
//        $messageDate = $crawler->filter('.sender-message .card-header .text-muted')->text();
//        $messageText = $crawler->filter('.sender-message .card-body .mb-0')->text();
//
//        $this->assertEquals('Tenant user', $messageSender);
//        $this->assertEquals($date->format('d-m-Y H:i:s'), $messageDate);
//        $this->assertEquals('First test message', $messageText);


    }
}