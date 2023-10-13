<?php

namespace App\Tests\e2e\Form;


use App\Repository\FlatRepository;
use Facebook\WebDriver\WebDriverDimension;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use App\Tests\e2e\Service\NewFlatFormHandlerTest;

class RegistrationFormTypeTest extends PantherTestCase
{
    use ResetDatabase, Factories;
    private Client $client;
    private FlatRepository $flatRepository;
    public function setUp(): void
    {
        $size = new WebDriverDimension(1920, 2980);

        $this->client = static::createPantherClient(['browser' => static::FIREFOX]);
        $this->client->manage()->window()->setSize($size);

        $container = static::getContainer();
        $this->flatRepository = $container->get(FlatRepository::class);
    }

    public function testAddNewLandlords()
    {
        $crawler = $this->client->request('GET', '/register');
        $this->client->waitForInvisibility('#spinner');

        $form = $crawler->selectButton('Register')->form();

        $this->client->executeScript('document.getElementById("registration_form_dateOfBirth").value = "2000-05-22";');
        $crawler = $this->client->submit($form, [
            'registration_form[name]' => 'Test Test',
            'registration_form[email]' => 'test_env_user@test.pl',
            'registration_form[plainPassword][first]' => 'test12',
            'registration_form[plainPassword][second]' => 'test12',
            'registration_form[roles]' => [
                'ROLE_LANDLORD'
            ],
        ]);

        $this->assertSame(self::$baseUri . '/login', $crawler->getUri());

        $crawler = $this->client->request('GET', '/register');
        $this->client->waitForInvisibility('#spinner');

        $form = $crawler->selectButton('Register')->form();

        $this->client->executeScript('document.getElementById("registration_form_dateOfBirth").value = "2000-05-22";');
        $crawler = $this->client->submit($form, [
            'registration_form[name]' => 'Landlord user',
            'registration_form[email]' => 'test_env_landlord@test.pl',
            'registration_form[plainPassword][first]' => 'test12',
            'registration_form[plainPassword][second]' => 'test12',
            'registration_form[roles]' => [
                'ROLE_LANDLORD'
            ],
        ]);

        $this->assertSame(self::$baseUri . '/login', $crawler->getUri());
    }

    public function testAddNewFlatForFutureTests()
    {
        $crawler = $this->client->request('GET', '/login');
        $this->assertSame(self::$baseUri . '/login', $crawler->getUri());

        $this->client->waitForInvisibility('#spinner');

        $crawler = $this->client->submitForm('Login', [
            '_username' => 'test_env_landlord@test.pl',
            '_password' => 'test12'
        ]);

        // check if user was logged in correctly and have access to /panel
        $crawler = $this->client->request('GET', '/panel');
        $this->client->waitForInvisibility('#spinner');
        $this->assertSame(self::$baseUri . '/panel', $crawler->getUri());

        $flatTest = new NewFlatFormHandlerTest();
        $flatTest->setUp();
        $flatTest->testAddNewFlatFlowWithoutOptionalFields();

        $crawler = $this->client->request('GET', '/panel/flats');
        $this->assertSame(self::$baseUri . '/panel/flats', $crawler->getUri());
        $this->client->waitForInvisibility('#spinner');

        $crawler = $this->client->clickLink('See more');
        $this->client->waitForInvisibility('#spinner');

        $crawler = $this->client->clickLink('Generate invitation code');
        $this->client->waitForInvisibility('#spinner');
    }

    public function testAddNewTenant()
    {
        $crawler = $this->client->request('GET', '/logout');

        $crawler = $this->client->request('GET', '/register');
        $this->client->waitForInvisibility('#spinner');
        $flat = $this->flatRepository->findAll()[0];
        $form = $crawler->selectButton('Register')->form();
        $this->client->executeScript('document.getElementById("registration_form_dateOfBirth").value = "2000-05-22";');
        $crawler = $this->client->submit($form, [
            'registration_form[name]' => 'Tenant user',
            'registration_form[email]' => 'test_env_tenant@test.pl',
            'registration_form[plainPassword][first]' => 'test12',
            'registration_form[plainPassword][second]' => 'test12',
            'registration_form[roles]' => [
                'ROLE_TENANT'
            ],
            'registration_form[code]' => $flat->getInvitationCode()->toBase32(),
        ]);
        $this->assertSame(self::$baseUri . '/login', $crawler->getUri());
    }
}