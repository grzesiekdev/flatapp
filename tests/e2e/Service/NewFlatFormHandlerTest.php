<?php

namespace App\Tests\e2e\Service;


use App\Entity\Flat;
use Doctrine\ORM\EntityManagerInterface;
use Facebook\WebDriver\WebDriverDimension;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;

class NewFlatFormHandlerTest extends PantherTestCase
{
    private Client $client;
    private KernelInterface $appKernel;
    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        $container = static::getContainer();
        $size = new WebDriverDimension(1920, 2980);

        $this->appKernel = $container->get(KernelInterface::class);
        $this->entityManager = $this->appKernel->getContainer()->get('doctrine')->getManager();
        $this->client = static::createPantherClient(['browser' => static::FIREFOX]);
        $this->client->manage()->window()->setSize($size);
    }

    // testing whole form for being filled with correct data
    public function testAddNewFlatFlow()
    {
        // authenticating user
        $crawler = $this->client->request('GET', '/login');
        $crawler = $this->client->submitForm('Login', [
            '_username' => 'test_env_user@test.pl',
            '_password' => 'test12'
        ]);

        // check if user was logged in correctly and have access to /panel/flats/new
        $crawler = $this->client->request('GET', '/panel/flats/new');
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

        $crawler = $this->client->submit($form, [
            'new_flat_form[area]' => '55',
            'new_flat_form[numberOfRooms]' => '3',
            'new_flat_form[address]' => 'Test 12, 12-123 Tested',
            'new_flat_form[floor]' => '5',
            'new_flat_form[maxFloor]' => '10',
        ]);

        // check for redirecting to step 2 after filling step 1 with correct data
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(2, $currentStep);

        $form = $crawler->selectButton('next')->form();

        // check if form is empty
        $this->assertEquals('', $form->get('new_flat_form[rent]')->getValue());
        $this->assertEquals('', $form->get('new_flat_form[deposit]')->getValue());

        // add new fees
        $this->client->executeScript('document.querySelector("#add-more").click()');
        $this->client->executeScript('document.querySelector("#add-more").click()');

        $crawler = $this->client->submit($form, [
            'new_flat_form[rent]' => '2600',
            'new_flat_form[deposit]' => '3000',
            'new_flat_form[fees][0][name]' => 'Gas',
            'new_flat_form[fees][0][value]' => '200',
            'new_flat_form[fees][1][name]' => 'Water',
            'new_flat_form[fees][1][value]' => '150',
        ]);

        // check for redirecting to step 3 after filling step 2 with correct data
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(3, $currentStep);

        $form = $crawler->selectButton('next')->form();

        // check if form is empty
        $this->assertEquals([
            "name" => "",
            "type" => "",
            "tmp_name" => "",
            "error" => 4,
            "size" => 0,
            ],
            $form->get('new_flat_form[pictures][]')->getValue()
        );
        $this->assertEquals([
            "name" => "",
            "type" => "",
            "tmp_name" => "",
            "error" => 4,
            "size" => 0,
        ],
            $form->get('new_flat_form[picturesForTenant][]')->getValue()
        );

        $crawler = $this->client->submit($form, [
            'new_flat_form[pictures][]' => $this->appKernel->getProjectDir() . '/tests/e2e/fixtures/screen.png',
            'new_flat_form[picturesForTenant][]' => $this->appKernel->getProjectDir() . '/tests/e2e/fixtures/screen.png',
        ]);

        // check for redirecting to step 4 after filling step 3 with correct data
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(4, $currentStep);

        $form = $crawler->selectButton('next')->form();

        // check if form is empty
        $this->assertEquals('', $form->get('new_flat_form[description]')->getValue());
        $this->assertEquals('', $form->get('new_flat_form[additionalFurnishing]')->getValue());
        $this->assertEquals('', $form->get('new_flat_form[furnishing][]')->getValue());
        $this->assertEquals([
            "name" => "",
            "type" => "",
            "tmp_name" => "",
            "error" => 4,
            "size" => 0,
        ],
            $form->get('new_flat_form[rentAgreement]')->getValue()
        );

        $crawler = $this->client->submit($form, [
            'new_flat_form[description]' => 'This is example description',
            'new_flat_form[additionalFurnishing]' => '2 beds, 4 chairs',
            'new_flat_form[furnishing]' => [
                'furnished',
                'bed',
                'tv'
            ],
            'new_flat_form[rentAgreement]' => $this->appKernel->getProjectDir() . '/tests/e2e/fixtures/agreement.pdf',
        ]);

        // check for redirecting to step 5 after filling step 4 with correct data
        $currentStep = $crawler->filter('#form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(5, $currentStep);

        $form = $crawler->selectButton('finish')->form();

        $flatArea =             $crawler->filter('#new_flat_form > .row > div ol li:nth-child(1)')->text();
        $numberOfRooms =        $crawler->filter('#new_flat_form > .row > div ol li:nth-child(2)')->text();
        $rent =                 $crawler->filter('#new_flat_form > .row > div ol li:nth-child(3)')->text();
        $fee1 =                 $crawler->filter('#new_flat_form > .row > div ol li:nth-child(4) ul li:nth-child(1)')->text();
        $fee2 =                 $crawler->filter('#new_flat_form > .row > div ol li:nth-child(4) ul li:nth-child(2)')->text();
        $deposit =              $crawler->filter('#new_flat_form > .row > div ol li:nth-child(5)')->text();
        $pictures =             $crawler->filter('#new_flat_form > .row > div ol li:nth-child(6) div div')->count();
        $picturesForTenant =    $crawler->filter('#new_flat_form > .row > div ol li:nth-child(7) div div')->count();
        $description =          $crawler->filter('#new_flat_form > .row > div ol li:nth-child(8)')->text();
        $address =              $crawler->filter('#new_flat_form > .row > div ol li:nth-child(9)')->text();
        $rentAgreement =        $crawler->filter('#new_flat_form > .row > div ol li:nth-child(10)')->text();
        $furnishing1 =          $crawler->filter('#new_flat_form > .row > div ol li:nth-child(11) div ul li:nth-child(1)')->text();
        $furnishing2 =          $crawler->filter('#new_flat_form > .row > div ol li:nth-child(11) div ul li:nth-child(2)')->text();
        $furnishing3 =          $crawler->filter('#new_flat_form > .row > div ol li:nth-child(11) div ul li:nth-child(3)')->text();
        $additionalFurnishing = $crawler->filter('#new_flat_form > .row > div ol li:nth-child(12)')->text();

        $this->assertEquals('Flat area: 55 m2', $flatArea);
        $this->assertEquals('Number of rooms: 3', $numberOfRooms);
        $this->assertEquals('Rent: 2600 zł', $rent);
        $this->assertEquals('Gas: 200 zł', $fee1);
        $this->assertEquals('Water: 150 zł', $fee2);
        $this->assertEquals('Deposit: 3000 zł', $deposit);
        $this->assertEquals(1, $pictures);
        $this->assertEquals(1, $picturesForTenant);
        $this->assertEquals('Description: This is example description', $description);
        $this->assertEquals('Address: Test 12, 12-123 Tested', $address);
        $this->assertMatchesRegularExpression('/Rent agreement: agreement-(.{13})\.pdf/', $rentAgreement);
        $this->assertEquals('furnished', $furnishing1);
        $this->assertEquals('bed', $furnishing2);
        $this->assertEquals('tv', $furnishing3);
        $this->assertEquals('Additional furnishing: 2 beds, 4 chairs', $additionalFurnishing);

        $crawler = $this->client->submit($form);
        $this->client->takeScreenshot('screen.png');

        // after successfully creating new flat, it is worth to check if the object was persisted into DB
        $flatRepository = $this->entityManager->getRepository(Flat::class);
        $flat = $flatRepository->findAll();


    }
}