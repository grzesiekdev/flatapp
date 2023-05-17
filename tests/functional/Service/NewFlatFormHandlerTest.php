<?php

namespace App\Tests\functional\Service;

use App\Repository\UserRepository;
use App\Service\FilesUploader;
use App\Service\NewFlatFormHandler;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Tests\SessionHelper;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;

class NewFlatFormHandlerTest extends PantherTestCase
{
    private KernelBrowser $client;
    private ParameterBagInterface $parameterBag;
    private NewFlatFormHandler $newFlatFormHandler;
    private RequestStack $requestStack;

    use SessionHelper;
    public function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $userRepository = $container->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail('test@test.pl');
        $this->client->loginUser($testUser);

        $this->parameterBag =  $container->get(ParameterBagInterface::class);
        $this->newFlatFormHandler = $container->get(NewFlatFormHandler::class);
        $this->requestStack = $container->get(RequestStack::class);
    }

    public function testUserLoggedInCorrectly()
    {
        $this->client->request('GET', '/panel/flats/new');
        $this->assertResponseIsSuccessful();
    }

    public function testGetSessionVariable()
    {
        $session = $this->createSession($this->client);
        $crawler = $this->client->request('GET', '/panel/flats/new');

        $session->set('test', 'value');
        $actual = $session->get('test');

        $this->assertEquals('value', $actual);
    }

    public function testNewFlatForUserBeingRedirectedToStepTwoAfterSubmittingStepOne()
    {
        $crawler = $this->client->request('GET', '/panel/flats/new');
        $form = $crawler->selectButton('next')->form();

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $crawler = $this->client->submit($form, [
            'new_flat_form' => [
                'area' => 55,
                'numberOfRooms' => 3,
                'address' => 'Test 12, 12-123 Tested',
                'floor' => '5',
                'maxFloor' => '10',
            ]
        ]);

        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(2, $currentStep);
    }

    // testing whole form for being filled with correct data
    public function testAddNewFlatFlow()
    {
        $client = static::createPantherClient(['browser' => static::FIREFOX]);

        // authenticating user
        $crawler = $client->request('GET', '/login');
        $crawler = $client->submitForm('Login', [
            '_username' => 'testowyuser@test.pl',
            '_password' => 'test12'
        ]);

        // check if user was logged in correctly and have access to /panel/flats/new
        $crawler = $client->request('GET', '/panel/flats/new');
        $this->assertSame(self::$baseUri . '/panel/flats/new', $crawler->getUri());

        // check if user is on the step 1
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(1, $currentStep);

        $form = $crawler->selectButton('next')->form();

        // check if form is empty
        $this->assertEquals('', $form->get('new_flat_form[area]')->getValue());
        $this->assertEquals('', $form->get('new_flat_form[numberOfRooms]')->getValue());
        $this->assertEquals('', $form->get('new_flat_form[address]')->getValue());
        $this->assertEquals(0, $form->get('new_flat_form[floor]')->getValue());
        $this->assertEquals(1, $form->get('new_flat_form[maxFloor]')->getValue());

        // check for redirecting to step 2 after filling step 1 with correct data
        $crawler = $client->submit($form, [
            'new_flat_form[area]' => '55',
            'new_flat_form[numberOfRooms]' => '3',
            'new_flat_form[address]' => 'Test 12, 12-123 Tested',
            'new_flat_form[floor]' => '5',
            'new_flat_form[maxFloor]' => '10',
        ]);

        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(2, $currentStep);

        $form = $crawler->selectButton('next')->form();
    }
}