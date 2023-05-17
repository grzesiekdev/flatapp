<?php

namespace App\Tests\e2e\Service;


use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;

class NewFlatFormHandlerTest extends PantherTestCase
{
    private Client $client;
    public function setUp(): void
    {
        $this->client = static::createPantherClient(['browser' => static::FIREFOX]);
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