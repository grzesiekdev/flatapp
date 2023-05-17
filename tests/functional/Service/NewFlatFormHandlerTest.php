<?php

namespace App\Tests\functional\Service;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Tests\SessionHelper;

class NewFlatFormHandlerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ParameterBagInterface $parameterBag;
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
}