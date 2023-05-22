<?php

namespace App\Tests\e2e\Form;


use Facebook\WebDriver\WebDriverDimension;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class RegistrationFormTypeTest extends PantherTestCase
{
    use ResetDatabase, Factories;
    private Client $client;
    public function setUp(): void
    {
        $size = new WebDriverDimension(1920, 2980);

        $this->client = static::createPantherClient(['browser' => static::FIREFOX]);
        $this->client->manage()->window()->setSize($size);
    }

    public function testAddNewUser()
    {
        $crawler = $this->client->request('GET', '/register');

        $form = $crawler->selectButton('Register')->form();

        $this->client->executeScript('document.getElementById("registration_form_dateOfBirth").value = "2000-05-22";');
        $crawler = $this->client->submit($form, [
            'registration_form[name]' => 'Test Test',
            'registration_form[email]' => 'test_env_user@test.pl',
            'registration_form[plainPassword][first]' => 'test12',
            'registration_form[plainPassword][second]' => 'test12',
            'registration_form[roles]' => [
                'landlord'
            ],
        ]);

        $this->assertSame(self::$baseUri . '/login', $crawler->getUri());
    }
}