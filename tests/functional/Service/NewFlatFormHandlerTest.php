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


class NewFlatFormHandlerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ParameterBagInterface $parameterBag;
    private NewFlatFormHandler $newFlatFormHandler;
    private RequestStack $requestStack;
    public function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);

        $testUser = $userRepository->findOneByEmail('')
        $this->parameterBag =  $container->get(ParameterBagInterface::class);
        $this->newFlatFormHandler = $container->get(NewFlatFormHandler::class);
        $this->requestStack = $container->get(RequestStack::class);
    }

    public function testGetSessionVariable()
    {
        $crawler = $this->client->request('GET', '/login');
        $this->requestStack->getSession()->set('test', 'value');
        $actual = $this->newFlatFormHandler->getSessionVariable('test');

        $this->assertEquals('value', $actual);
    }
}