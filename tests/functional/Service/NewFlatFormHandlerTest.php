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


class NewFlatFormHandlerTest extends WebTestCase
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
}