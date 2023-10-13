<?php

namespace App\Tests\e2e\Service;


use App\Entity\Flat;
use Doctrine\ORM\EntityManagerInterface;
use Facebook\WebDriver\WebDriverDimension;
use PHPUnit\Exception;
use PHPUnit\Framework\Constraint\ExceptionMessage;
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
        $crawler = $this->client->request('GET', '/logout');

        // authenticating user
        $crawler = $this->client->request('GET', '/login');
        $this->client->waitForInvisibility('#spinner');

        $crawler = $this->client->submitForm('Login', [
            '_username' => 'test_env_user@test.pl',
            '_password' => 'test12'
        ]);

        // check if user was logged in correctly and have access to /panel/flats/new
        $crawler = $this->client->request('GET', '/panel/flats/new');
        $this->client->waitForInvisibility('#spinner');

        $this->assertSame(self::$baseUri . '/panel/flats/new', $crawler->getUri());

        // check if user is on the step 1
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(1, $currentStep);

        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');

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
        $this->client->waitForInvisibility('#spinner');

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
        $this->client->waitForInvisibility('#spinner');

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
        $this->client->waitForInvisibility('#spinner');

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

        // we have to add text dynamically with JS to CKEditor since it's textarea is hidden
        $this->client->executeScript('CKEDITOR.instances["new_flat_form_description"].setData("This is example description");');

        $crawler = $this->client->submit($form, [
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
        $this->client->waitForInvisibility('#spinner');

        $address            = $crawler->filter('.card-title:nth-child(1)')->text();
        $area               = $crawler->filter('.table-flat-info tr:nth-child(1) td:nth-child(2)')->text();
        $numberOfRooms      = $crawler->filter('.table-flat-info tr:nth-child(1) td:nth-child(6)')->text();
        $rent               = $crawler->filter('.table-flat-info tr:nth-child(2) td:nth-child(2)')->text();
        $deposit            = $crawler->filter('.table-flat-info tr:nth-child(2) td:nth-child(6)')->text();
        $floor              = $crawler->filter('.table-flat-info tr:nth-child(3) td:nth-child(2)')->text();
        $agreement          = $crawler->filter('.table-flat-info tr:nth-child(3) td:nth-child(6)')->text();
        $fee1name           = $crawler->filter('.table-fees tbody tr:nth-child(1) td:nth-child(2)')->text();
        $fee1value          = $crawler->filter('.table-fees tbody tr:nth-child(1) td:nth-child(3)')->text();
        $fee2name           = $crawler->filter('.table-fees tbody tr:nth-child(2) td:nth-child(2)')->text();
        $fee2value          = $crawler->filter('.table-fees tbody tr:nth-child(2) td:nth-child(3)')->text();
        $furnishing1        = $crawler->filter('.table-furnishing tbody tr:nth-child(1) td:nth-child(2)')->text();
        $furnishing2        = $crawler->filter('.table-furnishing tbody tr:nth-child(2) td:nth-child(2)')->text();
        $furnishing3        = $crawler->filter('.table-furnishing tbody tr:nth-child(3) td:nth-child(2)')->text();
        $furnishing4        = $crawler->filter('.table-furnishing tbody tr:nth-child(4) td:nth-child(2)')->text();
        $description        = $crawler->filter('.flat-description')->text();
        $pictures           = $crawler->filter('#flatPicturesSlider > .carousel-inner .carousel-item')->count();
        $picturesForTenant  = $crawler->filter('.pictures-for-tenant div')->count();
        $this->client->takeScreenshot('screen.png');
        $this->assertEquals('55 m2', $area);
        $this->assertEquals('3', $numberOfRooms);
        $this->assertEquals('2600 zł', $rent);
        $this->assertEquals('Gas', $fee1name);
        $this->assertEquals('200 zł', $fee1value);
        $this->assertEquals('Water', $fee2name);
        $this->assertEquals('150 zł', $fee2value);
        $this->assertEquals('3000 zł', $deposit);
        $this->assertEquals('5 / 10', $floor);
        $this->assertEquals(1, $pictures);
        $this->assertEquals(1, $picturesForTenant);
        $this->assertEquals('This is example description', $description);
        $this->assertEquals('Test 12, 12-123 Tested', $address);
        $this->assertEquals('agreement-...', $agreement);
        $this->assertEquals('Furnished', $furnishing1);
        $this->assertEquals('Bed', $furnishing2);
        $this->assertEquals('Tv', $furnishing3);
        $this->assertEquals('2 beds, 4 chairs', $furnishing4);

        $crawler = $this->client->submit($form);

        // after successfully creating new flat, it is worth to check if the object was persisted into DB
        $flats = $this->entityManager->getRepository(Flat::class)->findAll();
        // since DB should be empty, the first returned flat is the one we've created in this test
        $flat = $flats[0];

        $this->assertEquals(55, $flat->getArea());
        $this->assertEquals(3, $flat->getNumberOfRooms());
        $this->assertEquals([
            [
                'name' => 'Gas',
                'value' => 200
            ],
            [
                'name' => 'Water',
                'value' => 150
            ]
        ], $flat->getFees());
        $this->assertEquals(2600, $flat->getRent());
        $this->assertEquals(3000, $flat->getDeposit());
        $this->assertMatchesRegularExpression('/screen-(.{13})\.png/', $flat->getPictures()[2]);
        $this->assertEquals('<p>This is example description</p>', $flat->getDescription());
        $this->assertEquals('Test 12, 12-123 Tested', $flat->getAddress());
        $this->assertEquals('agreement-...', $agreement);
        $this->assertEquals([["furnished","bed","tv"]], $flat->getFurnishing());
        $this->assertEquals('2 beds, 4 chairs', $flat->getAdditionalFurnishing());
        $this->assertEquals(5, $flat->getFloor());
        $this->assertEquals(10, $flat->getMaxFloor());
    }

    public function testAddNewFlatFlowWithoutOptionalFields()
    {
        // check if user was logged in correctly and have access to /panel/flats/new
        $crawler = $this->client->request('GET', '/panel/flats/new');
        $this->client->waitForInvisibility('#spinner');

        $this->assertSame(self::$baseUri . '/panel/flats/new', $crawler->getUri());

        // check if user is on the step 1
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(1, $currentStep);

        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');

        $crawler = $this->client->submit($form, [
            'new_flat_form[area]' => '33',
            'new_flat_form[numberOfRooms]' => '2',
            'new_flat_form[address]' => 'Test 12, 12-123 Tested',
            'new_flat_form[floor]' => '1',
            'new_flat_form[maxFloor]' => '14',
        ]);

        // check for redirecting to step 2 after filling step 1 with correct data
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(2, $currentStep);

        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');

        $crawler = $this->client->submit($form, [
            'new_flat_form[rent]' => '2100',
        ]);

        // check for redirecting to step 3 after filling step 2 with correct data
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(3, $currentStep);

        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');

        $crawler = $this->client->submit($form);

        // check for redirecting to step 4 after filling step 3 with correct data
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(4, $currentStep);

        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');

        $crawler = $this->client->submit($form);

        // check for redirecting to step 5 after filling step 4 with correct data
        $currentStep = $crawler->filter('#form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(5, $currentStep);

        $form = $crawler->selectButton('finish')->form();
        $this->client->waitForInvisibility('#spinner');

        $flatArea =      $crawler->filter('.table-flat-info tr:nth-child(1) td:nth-child(2)')->text();
        $numberOfRooms = $crawler->filter('.table-flat-info tr:nth-child(1) td:nth-child(6)')->text();
        $rent =          $crawler->filter('.table-flat-info tr:nth-child(2) td:nth-child(2)')->text();
        $address =       $crawler->filter('.card-title:nth-child(1)')->text();

        $this->assertEquals('33 m2', $flatArea);
        $this->assertEquals('2', $numberOfRooms);
        $this->assertEquals('2100 zł', $rent);
        $this->assertEquals('Test 12, 12-123 Tested', $address);

        $crawler = $this->client->submit($form);

        // after successfully creating new flat, it is worth to check if the object was persisted into DB
        $flats = $this->entityManager->getRepository(Flat::class)->findAll();
        $flat = $flats[1] ?? $flats[0];

        $this->assertEquals(33, $flat->getArea());
        $this->assertEquals(2, $flat->getNumberOfRooms());
        $this->assertEquals('Test 12, 12-123 Tested', $flat->getAddress());
        $this->assertEquals(1, $flat->getFloor());
        $this->assertEquals(14, $flat->getMaxFloor());
    }

    public function testAddNewFlatFlowForGoingBack()
    {
        // check if user was logged in correctly and have access to /panel/flats/new
        $crawler = $this->client->request('GET', '/panel/flats/new');
        $this->client->waitForInvisibility('#spinner');

        $this->assertSame(self::$baseUri . '/panel/flats/new', $crawler->getUri());

        // check if user is on the step 1
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(1, $currentStep);

        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');

        $crawler = $this->client->submit($form, [
            'new_flat_form[area]' => '70',
            'new_flat_form[numberOfRooms]' => '3',
            'new_flat_form[address]' => 'Testowa 1, 90-432 Testowo',
            'new_flat_form[floor]' => '0',
            'new_flat_form[maxFloor]' => '3',
        ]);

        // check for redirecting to step 2 after filling step 1 with correct data
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(2, $currentStep);

        // go back to the step 1
        $form = $crawler->selectButton('back')->form();
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->submit($form);

        // check if user was redirected to the step 1
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(1, $currentStep);

        // check if data was not deleted
        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');
        $this->assertEquals('70', $form->get('new_flat_form[area]')->getValue());
        $this->assertEquals('3', $form->get('new_flat_form[numberOfRooms]')->getValue());
        $this->assertEquals('Testowa 1, 90-432 Testowo', $form->get('new_flat_form[address]')->getValue());
        $this->assertEquals(0, $form->get('new_flat_form[floor]')->getValue());
        $this->assertEquals(3, $form->get('new_flat_form[maxFloor]')->getValue());

        // edit some data, go to the next step
        $crawler = $this->client->submit($form, [
            'new_flat_form[area]' => '65',
            'new_flat_form[address]' => 'Testowa 8, 90-432 Testowo',
            'new_flat_form[maxFloor]' => '5',
        ]);

        // check for redirecting to step 2 after filling step 1 with correct data
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(2, $currentStep);

        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');

        // add new fees
        $this->client->executeScript('document.querySelector("#add-more").click()');
        $this->client->executeScript('document.querySelector("#add-more").click()');
        $this->client->executeScript('document.querySelector("#add-more").click()');

        $crawler = $this->client->submit($form, [
            'new_flat_form[rent]' => '3500',
            'new_flat_form[deposit]' => '4000',
            'new_flat_form[fees][0][name]' => 'Gas',
            'new_flat_form[fees][0][value]' => '200',
            'new_flat_form[fees][1][name]' => 'Water',
            'new_flat_form[fees][1][value]' => '150',
            'new_flat_form[fees][2][name]' => 'Electricity',
            'new_flat_form[fees][2][value]' => '120',
        ]);

        // check for redirecting to step 3 after filling step 2 with correct data
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(3, $currentStep);

        // go back to the step 2
        $form = $crawler->selectButton('back')->form();
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->submit($form);

        // check if user was redirected to the step 2
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(2, $currentStep);

        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');

        // check if data was not deleted
        $this->assertEquals('3500', $form->get('new_flat_form[rent]')->getValue());
        $this->assertEquals('4000', $form->get('new_flat_form[deposit]')->getValue());
        $this->assertEquals('Gas', $form->get('new_flat_form[fees][0][name]')->getValue());
        $this->assertEquals('200', $form->get('new_flat_form[fees][0][value]')->getValue());
        $this->assertEquals('Water', $form->get('new_flat_form[fees][1][name]')->getValue());
        $this->assertEquals('150', $form->get('new_flat_form[fees][1][value]')->getValue());
        $this->assertEquals('Electricity', $form->get('new_flat_form[fees][2][name]')->getValue());
        $this->assertEquals('120', $form->get('new_flat_form[fees][2][value]')->getValue());

        // delete second fee
        $this->client->executeScript('document.querySelector("#new_flat_form_fees_1 .remove-fee i").click()');

        // check if fee was deleted correctly
        $fees = $crawler->filter('#fees-container div > .mt-3.col-sm-5')->count();
        $this->assertEquals(2, $fees);

        // edit some data
        $crawler = $this->client->submit($form, [
            'new_flat_form[rent]' => '3700',
            'new_flat_form[deposit]' => '4100',
        ]);

        // check for redirecting to step 3 after filling step 2 with correct data
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(3, $currentStep);

        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->submit($form, [
            'new_flat_form[pictures][]' => $this->appKernel->getProjectDir() . '/tests/e2e/fixtures/pictures/img1.jpg',
            'new_flat_form[picturesForTenant][]' => $this->appKernel->getProjectDir() . '/tests/e2e/fixtures/pictures_for_tenant/img1.jpeg',
        ]);

        // go back to step 3
        $form = $crawler->selectButton('back')->form();
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->submit($form);

        // check if user was redirected to the step 3
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(3, $currentStep);

        // upload more files
        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->submit($form, [
            'new_flat_form[pictures][]' => $this->appKernel->getProjectDir() . '/tests/e2e/fixtures/pictures/img2.jpeg',
            'new_flat_form[picturesForTenant][]' => $this->appKernel->getProjectDir() . '/tests/e2e/fixtures/pictures_for_tenant/img2.jpeg',
        ]);

        // go back to the step 3
        $form = $crawler->selectButton('back')->form();
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->submit($form);

        // check if user was redirected to step 3
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(3, $currentStep);

        $this->client->executeScript('document.querySelector(".pictures-container .flat-picture-box:nth-child(1) .delete-picture").click()');
        $this->client->executeScript('document.querySelector(".pictures-for-tenant-container .flat-picture-box:nth-child(1) .delete-picture").click()');

        // check if pictures were deleted after going back to the step 3
        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->submit($form);

        $form = $crawler->selectButton('back')->form();
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->submit($form);

        $pictures = $crawler->filter('.pictures-container .flat-picture-box')->count();
        $picturesForTenant = $crawler->filter('.pictures-for-tenant-container .flat-picture-box')->count();

        $this->assertEquals(1, $pictures);
        $this->assertEquals(1, $picturesForTenant);

        // add 2 more pictures
        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->submit($form, [
            'new_flat_form[pictures][]' => $this->appKernel->getProjectDir() . '/tests/e2e/fixtures/pictures/img3.jpg',
            'new_flat_form[picturesForTenant][]' => $this->appKernel->getProjectDir() . '/tests/e2e/fixtures/pictures_for_tenant/img3.jpeg',
        ]);

        // check for redirecting to step 4 after filling step 3 with correct data
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(4, $currentStep);

        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');

        // we have to add text dynamically with JS to CKEditor since it's textarea is hidden
        $this->client->executeScript('CKEDITOR.instances["new_flat_form_description"].setData("This is example description");');
        $crawler = $this->client->submit($form, [
            'new_flat_form[additionalFurnishing]' => '2 beds, 4 chairs',
            'new_flat_form[furnishing]' => [
                'furnished',
                'kitchen set',
                'tv',
                'utensils',
            ],
            'new_flat_form[rentAgreement]' => $this->appKernel->getProjectDir() . '/tests/e2e/fixtures/agreement.pdf',
        ]);

        // check for redirecting to step 5 after filling step 4 with correct data
        $currentStep = $crawler->filter('#form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(5, $currentStep);

        $address            = $crawler->filter('.card-title:nth-child(1)')->text();
        $area               = $crawler->filter('.table-flat-info tr:nth-child(1) td:nth-child(2)')->text();
        $numberOfRooms      = $crawler->filter('.table-flat-info tr:nth-child(1) td:nth-child(6)')->text();
        $rent               = $crawler->filter('.table-flat-info tr:nth-child(2) td:nth-child(2)')->text();
        $deposit            = $crawler->filter('.table-flat-info tr:nth-child(2) td:nth-child(6)')->text();
        $floor              = $crawler->filter('.table-flat-info tr:nth-child(3) td:nth-child(2)')->text();
        $agreement          = $crawler->filter('.table-flat-info tr:nth-child(3) td:nth-child(6)')->text();
        $fee1name           = $crawler->filter('.table-fees tbody tr:nth-child(1) td:nth-child(2)')->text();
        $fee1value          = $crawler->filter('.table-fees tbody tr:nth-child(1) td:nth-child(3)')->text();
        $furnishing1        = $crawler->filter('.table-furnishing tbody tr:nth-child(1) td:nth-child(2)')->text();
        $furnishing2        = $crawler->filter('.table-furnishing tbody tr:nth-child(2) td:nth-child(2)')->text();
        $furnishing3        = $crawler->filter('.table-furnishing tbody tr:nth-child(3) td:nth-child(2)')->text();
        $furnishing4        = $crawler->filter('.table-furnishing tbody tr:nth-child(4) td:nth-child(2)')->text();
        $furnishing5        = $crawler->filter('.table-furnishing tbody tr:nth-child(5) td:nth-child(2)')->text();
        $description        = $crawler->filter('.flat-description')->text();
        $pictures           = $crawler->filter('#flatPicturesSlider > .carousel-inner .carousel-item')->count();
        $picturesForTenant  = $crawler->filter('.pictures-for-tenant div')->count();

        $this->assertEquals('65 m2', $area);
        $this->assertEquals('3', $numberOfRooms);
        $this->assertEquals('3700 zł', $rent);
        $this->assertEquals('Gas', $fee1name);
        $this->assertEquals('200 zł', $fee1value);
        $this->assertEquals('4100 zł', $deposit);
        $this->assertEquals('0 / 5', $floor);
        $this->assertEquals(2, $pictures);
        $this->assertEquals(2, $picturesForTenant);
        $this->assertEquals('This is example description', $description);
        $this->assertEquals('Testowa 8, 90-432 Testowo', $address);
        $this->assertEquals('agreement-...', $agreement);
        $this->assertEquals('Furnished', $furnishing1);
        $this->assertEquals('Utensils', $furnishing2);
        $this->assertEquals('Kitchen set', $furnishing3);
        $this->assertEquals('Tv', $furnishing4);
        $this->assertEquals('2 beds, 4 chairs', $furnishing5);

        //go back to the first step using navigation, and change area
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->clickLink('Basic flat info');

        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(1, $currentStep);

        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->submit($form, [
            'new_flat_form[area]' => '67',
        ]);

        //go to step 3 and delete one picture for tenant, add another main picture
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->clickLink('Pictures');

        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(3, $currentStep);

        $this->client->executeScript('document.querySelector(".pictures-for-tenant-container .flat-picture-box:nth-child(1) .delete-picture").click()');

        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->submit($form, [
            'new_flat_form[pictures][]' => $this->appKernel->getProjectDir() . '/tests/e2e/fixtures/pictures/img4.jpg',
        ]);

        //go to the summary again
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->clickLink('Confirmation');

        $flatArea =          $crawler->filter('.table-flat-info tr:nth-child(1) td:nth-child(2)')->text();
        $pictures =          $crawler->filter('#flatPicturesSlider > .carousel-inner .carousel-item')->count();
        $picturesForTenant = $crawler->filter('.pictures-for-tenant div')->count();

        $this->assertEquals('67 m2', $flatArea);
        $this->assertEquals(3, $pictures);
        $this->assertEquals(1, $picturesForTenant);

        //go to step 2, add new fee and change deposit
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->clickLink('Fees');

        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');

        $this->client->executeScript('document.querySelector("#add-more").click()');
        $this->client->wait(10);

        $crawler = $this->client->submit($form, [
            'new_flat_form[deposit]' => '4500',
            'new_flat_form[fees][3][name]' => 'Additional fee',
            'new_flat_form[fees][3][value]' => '300',
        ]);

        //go to the summary again
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->clickLink('Confirmation');

        $deposit   = $crawler->filter('.table-flat-info tr:nth-child(2) td:nth-child(6)')->text();
        $fee3name  = $crawler->filter('.table-fees tbody tr:nth-child(3) td:nth-child(2)')->text();
        $fee3value = $crawler->filter('.table-fees tbody tr:nth-child(3) td:nth-child(3)')->text();

        $this->assertEquals('4500 zł', $deposit);
        $this->assertEquals('Additional fee', $fee3name);
        $this->assertEquals('300 zł', $fee3value);

        $form = $crawler->selectButton('finish')->form();
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->submit($form);

        // after successfully creating new flat, it is worth to check if the object was persisted into DB
        $flats = $this->entityManager->getRepository(Flat::class)->findAll();
        // since DB should be empty, the first returned flat is the one we've created in this test
        $flat = $flats[2];

        $this->assertEquals(67, $flat->getArea());
        $this->assertEquals(3, $flat->getNumberOfRooms());
        $this->assertEquals([
            0 => [
                'name' => 'Gas',
                'value' => 200
            ],
            2 => [
                'name' => 'Electricity',
                'value' => 120
            ],
            3 => [
                'name' => 'Additional fee',
                'value' => 300
            ],
        ], $flat->getFees());
        $this->assertEquals(3700, $flat->getRent());
        $this->assertEquals(4500, $flat->getDeposit());
        $this->assertMatchesRegularExpression('/img2-(.{13})\.jpg/', $flat->getPictures()[2]);
        $this->assertMatchesRegularExpression('/img3-(.{13})\.webp/', $flat->getPictures()[3]);
        $this->assertMatchesRegularExpression('/img4-(.{13})\.jpg/', $flat->getPictures()[4]);
        $this->assertMatchesRegularExpression('/img3-(.{13})\.jpg/', $flat->getPicturesForTenant()[2]);
        $this->assertEquals('<p>This is example description</p>', $flat->getDescription());
        $this->assertEquals('Testowa 8, 90-432 Testowo', $flat->getAddress());
        $this->assertEquals('agreement-...', $agreement);
        $this->assertEquals([["furnished", "utensils", "kitchen set", "tv"]], $flat->getFurnishing());
        $this->assertEquals('2 beds, 4 chairs', $flat->getAdditionalFurnishing());
        $this->assertEquals(0, $flat->getFloor());
        $this->assertEquals(5, $flat->getMaxFloor());
    }

    public function testAddNewFlatFlowForEditingExistingFlat()
    {
        // check if user has access to /panel/flats
        $crawler = $this->client->request('GET', '/panel/flats');
        $this->client->waitForInvisibility('#spinner');

        $this->assertSame(self::$baseUri . '/panel/flats', $crawler->getUri());

        $crawler->filter('#flat-4 a[href="/panel/flats/4"]')->click();
        $crawler = $this->client->refreshCrawler();
        $this->client->waitFor('a[href="/panel/flats/edit/4"]');

        // check if user was redirected to /panel/flats/3
        $this->assertSame(self::$baseUri . '/panel/flats/4', $crawler->getUri());

        // check if data on the card is correct
        $address            = $crawler->filter('.card-title:nth-child(1)')->text();
        $area               = $crawler->filter('.table-flat-info tr:nth-child(1) td:nth-child(2)')->text();
        $numberOfRooms      = $crawler->filter('.table-flat-info tr:nth-child(1) td:nth-child(6)')->text();
        $rent               = $crawler->filter('.table-flat-info tr:nth-child(2) td:nth-child(2)')->text();
        $deposit            = $crawler->filter('.table-flat-info tr:nth-child(2) td:nth-child(6)')->text();
        $floor              = $crawler->filter('.table-flat-info tr:nth-child(3) td:nth-child(2)')->text();
        $agreement          = $crawler->filter('.table-flat-info tr:nth-child(3) td:nth-child(6)')->text();
        $fee1name           = $crawler->filter('.table-fees tbody tr:nth-child(1) td:nth-child(2)')->text();
        $fee1value          = $crawler->filter('.table-fees tbody tr:nth-child(1) td:nth-child(3)')->text();
        $fee2name           = $crawler->filter('.table-fees tbody tr:nth-child(2) td:nth-child(2)')->text();
        $fee2value          = $crawler->filter('.table-fees tbody tr:nth-child(2) td:nth-child(3)')->text();
        $fee3name           = $crawler->filter('.table-fees tbody tr:nth-child(3) td:nth-child(2)')->text();
        $fee3value          = $crawler->filter('.table-fees tbody tr:nth-child(3) td:nth-child(3)')->text();
        $furnishing1        = $crawler->filter('.table-furnishing tbody tr:nth-child(1) td:nth-child(2)')->text();
        $furnishing2        = $crawler->filter('.table-furnishing tbody tr:nth-child(2) td:nth-child(2)')->text();
        $furnishing3        = $crawler->filter('.table-furnishing tbody tr:nth-child(3) td:nth-child(2)')->text();
        $furnishing4        = $crawler->filter('.table-furnishing tbody tr:nth-child(4) td:nth-child(2)')->text();
        $furnishing5        = $crawler->filter('.table-furnishing tbody tr:nth-child(5) td:nth-child(2)')->text();
        $description        = $crawler->filter('.flat-description')->text();
        $pictures           = $crawler->filter('#flatPicturesSlider > .carousel-inner .carousel-item')->count();
        $picturesForTenant  = $crawler->filter('.pictures-for-tenant a')->count();

        $this->assertEquals('Testowa 8, 90-432 Testowo', $address);
        $this->assertEquals('67 m2', $area);
        $this->assertEquals('3', $numberOfRooms);
        $this->assertEquals('3700 zł', $rent);
        $this->assertEquals('4500 zł', $deposit);
        $this->assertEquals('0 / 5', $floor);
        $this->assertEquals('agreement-...', $agreement);
        $this->assertEquals('Gas', $fee1name);
        $this->assertEquals('200 zł', $fee1value);
        $this->assertEquals('Electricity', $fee2name);
        $this->assertEquals('120 zł', $fee2value);
        $this->assertEquals('Additional fee', $fee3name);
        $this->assertEquals('300 zł', $fee3value);
        $this->assertEquals('Furnished', $furnishing1);
        $this->assertEquals('Utensils', $furnishing2);
        $this->assertEquals('Kitchen set', $furnishing3);
        $this->assertEquals('Tv', $furnishing4);
        $this->assertEquals('2 beds, 4 chairs', $furnishing5);
        $this->assertEquals('This is example description', $description);
        $this->assertEquals('3', $pictures);
        $this->assertEquals('1', $picturesForTenant);

        // edit flat
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->clickLink('Edit');

        // go to step 3 and update pictures
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->clickLink('Pictures');
        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');

        $this->client->executeScript('document.querySelector(".pictures-container .flat-picture-box:nth-child(1) .delete-picture").click()');
        $crawler = $this->client->submit($form, [
            'new_flat_form[picturesForTenant][]' => $this->appKernel->getProjectDir() . '/tests/e2e/fixtures/pictures_for_tenant/img4.jpg',
        ]);

        // go back to step 1 and change some data
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->clickLink('Basic flat info');
        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');

        $crawler = $this->client->submit($form, [
            'new_flat_form[area]' => '87',
            'new_flat_form[floor]' => '2',
            'new_flat_form[maxFloor]' => '9',
        ]);

        // go to summary and submit
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->clickLink('Confirmation');
        $form = $crawler->selectButton('finish')->form();
        $this->client->waitForInvisibility('#spinner');

        $crawler = $this->client->submit($form);
        $this->client->waitForInvisibility('#spinner');

        $crawler->filter('#flat-4 a[href="/panel/flats/4"]')->click();
        $crawler = $this->client->refreshCrawler();
        $this->client->waitFor('a[href="/panel/flats/edit/4"]');

        // check if user was redirected to /panel/flats/3
        $this->assertSame(self::$baseUri . '/panel/flats/4', $crawler->getUri());

        // check if data was successfully updated
        $area               = $crawler->filter('.table-flat-info tr:nth-child(1) td:nth-child(2)')->text();
        $floor              = $crawler->filter('.table-flat-info tr:nth-child(3) td:nth-child(2)')->text();
        $pictures           = $crawler->filter('#flatPicturesSlider > .carousel-inner .carousel-item')->count();
        $picturesForTenant  = $crawler->filter('.pictures-for-tenant a')->count();

        $this->assertEquals('87 m2', $area);
        $this->assertEquals('2 / 9', $floor);
        $this->assertEquals('2', $pictures);
        $this->assertEquals('2', $picturesForTenant);

        // delete flat, check if user was redirected
        $this->client->waitForInvisibility('#spinner');
        $crawler->filter('a[href="/panel/flats/delete/4"]')->click();
        $crawler = $this->client->refreshCrawler();
        $this->assertSame(self::$baseUri . '/panel/flats', $crawler->getUri());

        $flats = $crawler->filter('.flat-list > .card')->count();
        $this->assertEquals(2, $flats);
    }

    public function testAddNewFlatFlowForStartOver()
    {
        // check if user was logged in correctly and have access to /panel/flats/new
        $crawler = $this->client->request('GET', '/panel/flats/new');
        $this->client->waitForInvisibility('#spinner');
        $this->assertSame(self::$baseUri . '/panel/flats/new', $crawler->getUri());

        // check if user is on the step 1
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(1, $currentStep);

        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->submit($form, [
            'new_flat_form[area]' => '70',
            'new_flat_form[numberOfRooms]' => '3',
            'new_flat_form[address]' => 'Testowa 1, 90-432 Testowo',
            'new_flat_form[floor]' => '0',
            'new_flat_form[maxFloor]' => '3',
        ]);

        // check if user is on the step 2
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(2, $currentStep);

        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->submit($form, [
            'new_flat_form[rent]' => '3500',
            'new_flat_form[deposit]' => '4000',
        ]);

        // check if user is on the step 3
        $currentStep = $crawler->filter('#new_flat_form_flow_newFlatType_step')->attr('value');
        $this->assertEquals(3, $currentStep);

        // start over, check if form has been reseted
        $form = $crawler->selectButton('start over')->form();
        $this->client->waitForInvisibility('#spinner');
        $crawler = $this->client->submit($form);

        $form = $crawler->selectButton('next')->form();
        $this->client->waitForInvisibility('#spinner');

        $this->assertEquals('', $form->get('new_flat_form[area]')->getValue());
        $this->assertEquals('', $form->get('new_flat_form[numberOfRooms]')->getValue());
        $this->assertEquals('', $form->get('new_flat_form[address]')->getValue());
        $this->assertEquals(0, $form->get('new_flat_form[floor]')->getValue());
        $this->assertEquals(1, $form->get('new_flat_form[maxFloor]')->getValue());

        $stepOne = $crawler->filter('.craue_formflow_steplist li:nth-child(1) a')->count();
        $stepTwo = $crawler->filter('.craue_formflow_steplist li:nth-child(2) a')->count();
        $stepThree = $crawler->filter('.craue_formflow_steplist li:nth-child(3) a')->count();

        $this->assertEquals(0, $stepOne);
        $this->assertEquals(0, $stepTwo);
        $this->assertEquals(0, $stepThree);

    }
}