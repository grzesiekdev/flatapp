<?php

namespace App\Tests\functional\Controller;

use App\Entity\Flat;
use App\Entity\User\Type\Landlord;
use App\Entity\User\Type\Tenant;
use App\Entity\UtilityMeterReading;
use App\Repository\FlatRepository;
use App\Repository\TenantRepository;
use App\Service\FilesUploader;
use App\Tests\Utils\TestDataProvider;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints\Date;

class UtilityMetersControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private TestDataProvider $testDataProvider;
    private Tenant $tenant;
    private Landlord $landlord;
    private EntityManager $entityManager;
    private KernelInterface $appKernel;
    private TenantRepository $tenantRepository;
    private FlatRepository $flatRepository;
    private Flat $flat;
    private UtilityMeterReading $utilityMeterReading;
    private UtilityMeterReading $utilityMeterReadingTwo;
    private array $invoicesToDelete;
    private FilesUploader $filesUploader;
    private \DateTime $currentDate;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->testDataProvider = self::getContainer()->get('App\Tests\Utils\TestDataProvider');
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->appKernel = self::getContainer()->get(KernelInterface::class);
        $this->tenantRepository = self::getContainer()->get(TenantRepository::class);
        $this->flatRepository = self::getContainer()->get(FlatRepository::class);
        $this->filesUploader = self::getContainer()->get(FilesUploader::class);
        $this->currentDate = new \DateTime('now');

        $usersData = [
            'tenant1' => [
                'name' => 'Jan Kowalski',
                'email' => 'jkowalski@tenant.pl',
                'password' => 'test12',
                'dob' => '1922-02-01',
                'role' => 'ROLE_TENANT',
                'image' => 'default-profile-picture.png'
            ],
            'landlord1' => [
                'name' => 'Jan Kowalski',
                'email' => 'jkowalski@landlord.pl',
                'password' => 'test12',
                'dob' => '1922-02-01',
                'role' => 'ROLE_LANDLORD',
                'image' => 'default-profile-picture.png'
            ],
        ];

        $users = $this->testDataProvider->provideUsers($usersData);

        $this->tenant = $users['tenant1'];
        $this->landlord = $users['landlord1'];

        $this->utilityMeterReading = new UtilityMeterReading();
        $this->utilityMeterReading->setDate(new \DateTime('now'));
        $this->utilityMeterReading->setWater(['amount' => 10, 'cost' => 22]);
        $this->utilityMeterReading->setGas(['amount' => 15, 'cost' => 55]);
        $this->utilityMeterReading->setElectricity(['amount' => 20, 'cost' => 32]);
        $this->utilityMeterReading->setInvoices(['test-invoice1.pdf']);

        $this->utilityMeterReadingTwo = new UtilityMeterReading();
        $this->utilityMeterReadingTwo->setDate(new \DateTime('now'));
        $this->utilityMeterReadingTwo->setWater(['amount' => 54.67, 'cost' => 0]);
        $this->utilityMeterReadingTwo->setGas(['amount' => 12.5, 'cost' => 0]);
        $this->utilityMeterReadingTwo->setElectricity(['amount' => 1.21, 'cost' => 0]);

        $this->flat = new Flat();
        $this->flat->setArea(55);
        $this->flat->setNumberOfRooms(2);
        $this->flat->setAddress('Testowa 12');
        $this->flat->setFloor(3);
        $this->flat->setMaxFloor(5);
        $this->flat->setRent(2000);
        $this->flat->setLandlord($this->landlord);
        $this->flat->addTenant($this->tenant);
        $this->flat->addUtilityMeterReading($this->utilityMeterReading);
        $this->flat->addUtilityMeterReading($this->utilityMeterReadingTwo);

        $this->landlord->addFlat($this->flat);

        $this->entityManager->persist($this->landlord);
        $this->entityManager->persist($this->flat);
        $this->entityManager->persist($this->tenant);
        $this->entityManager->persist($this->utilityMeterReading);
        $this->entityManager->persist($this->utilityMeterReadingTwo);
        $this->entityManager->flush();

        $this->invoicesToDelete = [];
    }

    public function tearDown(): void
    {
        $date = new \DateTime('now');
        $path = self::getContainer()->getParameter('invoices') . '/flat' . $this->flat->getId();

        foreach ($this->invoicesToDelete as $invoice) {
            $this->filesUploader->deleteFile($path . '/' . $date->format('d-m-Y') . '/' . $invoice);
        }
        if (file_exists($path)) {
            rmdir($path. '/' . $date->format('d-m-Y'));
            rmdir($path);
        }

        parent::tearDown();
    }

    public function testIfTenantCanAccessListOfUtilityReadings(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters');
        $this->assertEquals('http://localhost/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters', $crawler->getUri());

        $date1        = $crawler->filter('tbody tr:nth-child(1) th')->text();
        $water1       = $crawler->filter('tbody tr:nth-child(1) td:nth-child(2)')->text();
        $gas1         = $crawler->filter('tbody tr:nth-child(1) td:nth-child(3)')->text();
        $electricity1 = $crawler->filter('tbody tr:nth-child(1) td:nth-child(4)')->text();
        $invoices1    = $crawler->filter('tbody tr:nth-child(1) td:nth-child(5)')->text();

        $date2        = $crawler->filter('tbody tr:nth-child(2) th')->text();
        $water2       = $crawler->filter('tbody tr:nth-child(2) td:nth-child(2)')->text();
        $gas2         = $crawler->filter('tbody tr:nth-child(2) td:nth-child(3)')->text();
        $electricity2 = $crawler->filter('tbody tr:nth-child(2) td:nth-child(4)')->text();
        $invoices2    = $crawler->filter('tbody tr:nth-child(2) td:nth-child(5)')->text();

        $this->assertEquals($this->currentDate->format('d-m-Y'), $date1);
        $this->assertEquals('10 m3, 22 zł', $water1);
        $this->assertEquals('15 m3, 55 zł', $gas1);
        $this->assertEquals('20 kwH, 32 zł', $electricity1);
        $this->assertEquals('Invoices: 1 1. test-invoice1.pdf', $invoices1);

        $this->assertEquals($this->currentDate->format('d-m-Y'), $date2);
        $this->assertEquals('54.67 m3, 0 zł', $water2);
        $this->assertEquals('12.5 m3, 0 zł', $gas2);
        $this->assertEquals('1.21 kwH, 0 zł', $electricity2);
        $this->assertEquals('Invoices: 0', $invoices2);

        $button = $crawler->filter('.btn-success')->text();
        $this->assertEquals('Add new reading', $button);
    }

    public function testIfTenantCanAddNewReading(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters/add-new');
        $this->assertEquals('http://localhost/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters/add-new', $crawler->getUri());

        $date              = $crawler->filter('#utility_meters_reading_date');
        $waterAmount       = $crawler->filter('#utility_meters_reading_water_amount');
        $waterCost         = $crawler->filter('#utility_meters_reading_water_cost');
        $gasAmount         = $crawler->filter('#utility_meters_reading_gas_amount');
        $gasCost           = $crawler->filter('#utility_meters_reading_gas_cost');
        $electricityAmount = $crawler->filter('#utility_meters_reading_electricity_amount');
        $electricityCost   = $crawler->filter('#utility_meters_reading_electricity_cost');
        $invoices          = $crawler->filter('#utility_meters_reading_invoices');

        $this->assertEquals('disabled', $date->attr('disabled'));
        $this->assertEquals($this->currentDate->format('Y-m-d'), $date->attr('value'));
        $this->assertEquals('0', $waterAmount->attr('placeholder'));
        $this->assertEquals('disabled', $waterCost->attr('disabled'));
        $this->assertEquals('0', $gasAmount->attr('placeholder'));
        $this->assertEquals('disabled', $gasCost->attr('disabled'));
        $this->assertEquals('0', $electricityAmount->attr('placeholder'));
        $this->assertEquals('disabled', $electricityCost->attr('disabled'));
        $this->assertEquals('disabled', $invoices->attr('disabled'));

        $form = $crawler->filter('form[name="utility_meters_reading"]')->form([
            'utility_meters_reading[water_amount]' => '23.12',
            'utility_meters_reading[gas_amount]' => '10.543',
            'utility_meters_reading[electricity_amount]' => '1.43',
        ]);

        $crawler = $this->client->submit($form);
        $crawler = $this->client->followRedirect();
        $this->assertEquals('http://localhost/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters', $crawler->getUri());

        $date        = $crawler->filter('tbody tr:nth-child(3) th')->text();
        $water       = $crawler->filter('tbody tr:nth-child(3) td:nth-child(2)')->text();
        $gas         = $crawler->filter('tbody tr:nth-child(3) td:nth-child(3)')->text();
        $electricity = $crawler->filter('tbody tr:nth-child(3) td:nth-child(4)')->text();
        $invoices    = $crawler->filter('tbody tr:nth-child(3) td:nth-child(5)')->text();
        
        $this->assertEquals($this->currentDate->format('d-m-Y'), $date);
        $this->assertEquals('23.12 m3, zł', $water);
        $this->assertEquals('10.54 m3, zł', $gas);
        $this->assertEquals('1.43 kwH, zł', $electricity);
        $this->assertEquals('Invoices: 0', $invoices);
    }

    public function testIfTenantCanAddNewReadingWithEmptyFields(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters/add-new');
        $this->assertEquals('http://localhost/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters/add-new', $crawler->getUri());

        $form = $crawler->filter('form[name="utility_meters_reading"]')->form([]);

        $crawler = $this->client->submit($form);
        $crawler = $this->client->followRedirect();
        $this->assertEquals('http://localhost/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters', $crawler->getUri());

        $date        = $crawler->filter('tbody tr:nth-child(3) th')->text();
        $water       = $crawler->filter('tbody tr:nth-child(3) td:nth-child(2)')->text();
        $gas         = $crawler->filter('tbody tr:nth-child(3) td:nth-child(3)')->text();
        $electricity = $crawler->filter('tbody tr:nth-child(3) td:nth-child(4)')->text();
        $invoices    = $crawler->filter('tbody tr:nth-child(3) td:nth-child(5)')->text();
        
        $this->assertEquals($this->currentDate->format('d-m-Y'), $date);
        $this->assertEquals('0 m3, zł', $water);
        $this->assertEquals('0 m3, zł', $gas);
        $this->assertEquals('0 kwH, zł', $electricity);
        $this->assertEquals('Invoices: 0', $invoices);
    }

    public function testIfLandlordCanAccessListOfUtilityReadings(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId() . '/utility-meters');
        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId() . '/utility-meters', $crawler->getUri());

        $date1        = $crawler->filter('tbody tr:nth-child(1) th')->text();
        $water1       = $crawler->filter('tbody tr:nth-child(1) td:nth-child(2)')->text();
        $gas1         = $crawler->filter('tbody tr:nth-child(1) td:nth-child(3)')->text();
        $electricity1 = $crawler->filter('tbody tr:nth-child(1) td:nth-child(4)')->text();
        $invoices1    = $crawler->filter('tbody tr:nth-child(1) td:nth-child(5)')->text();

        $date2        = $crawler->filter('tbody tr:nth-child(2) th')->text();
        $water2       = $crawler->filter('tbody tr:nth-child(2) td:nth-child(2)')->text();
        $gas2         = $crawler->filter('tbody tr:nth-child(2) td:nth-child(3)')->text();
        $electricity2 = $crawler->filter('tbody tr:nth-child(2) td:nth-child(4)')->text();
        $invoices2    = $crawler->filter('tbody tr:nth-child(2) td:nth-child(5)')->text();

        $this->assertEquals($this->currentDate->format('d-m-Y'), $date1);
        $this->assertEquals('10 m3, 22 zł', $water1);
        $this->assertEquals('15 m3, 55 zł', $gas1);
        $this->assertEquals('20 kwH, 32 zł', $electricity1);
        $this->assertEquals('Invoices: 1 1. test-invoice1.pdf', $invoices1);

        $this->assertEquals($this->currentDate->format('d-m-Y'), $date2);
        $this->assertEquals('54.67 m3, 0 zł', $water2);
        $this->assertEquals('12.5 m3, 0 zł', $gas2);
        $this->assertEquals('1.21 kwH, 0 zł', $electricity2);
        $this->assertEquals('Invoices: 0', $invoices2);

        $editReadingButton  = $crawler->filter('tbody tr:nth-child(1) th a')->attr('href');
        $editReadingButton2  = $crawler->filter('tbody tr:nth-child(2) th a')->attr('href');

        $this->assertEquals('/panel/flats/'. $this->flat->getId() .'/utility-meters/'. $this->utilityMeterReading->getId(), $editReadingButton);
        $this->assertEquals('/panel/flats/'. $this->flat->getId() .'/utility-meters/'. $this->utilityMeterReadingTwo->getId(), $editReadingButton2);

        $deleteReadingButton  = $crawler->filter('tbody tr:nth-child(1) th a:nth-child(2)')->attr('href');
        $deleteReadingButton2 = $crawler->filter('tbody tr:nth-child(2) th a:nth-child(2)')->attr('href');

        $this->assertEquals('/panel/flats/'. $this->flat->getId() .'/utility-meters/'. $this->utilityMeterReading->getId() .'/delete', $deleteReadingButton);
        $this->assertEquals('/panel/flats/'. $this->flat->getId() .'/utility-meters/'. $this->utilityMeterReadingTwo->getId() .'/delete', $deleteReadingButton2);
    }

    public function testIfLandlordCanEditReading(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId());
        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId(), $crawler->getUri());

        $date              = $crawler->filter('#utility_meters_reading_date');
        $waterAmount       = $crawler->filter('#utility_meters_reading_water_amount');
        $waterCost         = $crawler->filter('#utility_meters_reading_water_cost');
        $gasAmount         = $crawler->filter('#utility_meters_reading_gas_amount');
        $gasCost           = $crawler->filter('#utility_meters_reading_gas_cost');
        $electricityAmount = $crawler->filter('#utility_meters_reading_electricity_amount');
        $electricityCost   = $crawler->filter('#utility_meters_reading_electricity_cost');

        $this->assertEquals('disabled', $date->attr('disabled'));
        $this->assertEquals($this->currentDate->format('Y-m-d'), $date->attr('value'));

        $this->assertEquals('54.67', $waterAmount->attr('placeholder'));
        $this->assertEquals('disabled', $waterAmount->attr('disabled'));
        $this->assertEquals('12.5', $gasAmount->attr('placeholder'));
        $this->assertEquals('disabled', $gasAmount->attr('disabled'));
        $this->assertEquals('1.21', $electricityAmount->attr('placeholder'));
        $this->assertEquals('disabled', $electricityAmount->attr('disabled'));

        $this->assertEquals('0', $waterCost->attr('placeholder'));
        $this->assertEquals('0', $gasCost->attr('placeholder'));
        $this->assertEquals('0', $electricityCost->attr('placeholder'));

        $form = $crawler->filter('form[name="utility_meters_reading"]')->form([
            'utility_meters_reading[water_cost]' => '125.4',
            'utility_meters_reading[gas_cost]' => '254.247',
            'utility_meters_reading[electricity_cost]' => '167.90',
            'utility_meters_reading[invoices][0]' => $this->appKernel->getProjectDir() . '/tests/e2e/fixtures/agreement.pdf',
        ]);

        $crawler = $this->client->submit($form);
        $crawler = $this->client->followRedirect();
        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId(). '/utility-meters', $crawler->getUri());

        $date        = $crawler->filter('tbody tr:nth-child(2) th')->text();
        $water       = $crawler->filter('tbody tr:nth-child(2) td:nth-child(2)')->text();
        $gas         = $crawler->filter('tbody tr:nth-child(2) td:nth-child(3)')->text();
        $electricity = $crawler->filter('tbody tr:nth-child(2) td:nth-child(4)')->text();
        $invoices    = $crawler->filter('tbody tr:nth-child(2) td:nth-child(5)')->text();
        
        $this->assertEquals($this->currentDate->format('d-m-Y'), $date);
        $this->assertEquals('54.67 m3, 125.4 zł', $water);
        $this->assertEquals('12.5 m3, 254.25 zł', $gas);
        $this->assertEquals('1.21 kwH, 167.9 zł', $electricity);
        $this->assertMatchesRegularExpression('/Invoices: 1 1\. agreement-[a-z0-9]{13}\.pdf/', $invoices);

        $invoiceToDelete = $crawler->filter('tbody tr:nth-child(2) .invoices-collapse a')->text();
        $this->invoicesToDelete[] = $invoiceToDelete;
    }

    public function testIfLandlordCanEditReadingWithZeros(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId());
        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId(), $crawler->getUri());

        $form = $crawler->filter('form[name="utility_meters_reading"]')->form([
            'utility_meters_reading[water_cost]' => '0',
            'utility_meters_reading[gas_cost]' => '0',
            'utility_meters_reading[electricity_cost]' => '0',
        ]);

        $crawler = $this->client->submit($form);
        $crawler = $this->client->followRedirect();
        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId(). '/utility-meters', $crawler->getUri());

        $date        = $crawler->filter('tbody tr:nth-child(2) th')->text();
        $water       = $crawler->filter('tbody tr:nth-child(2) td:nth-child(2)')->text();
        $gas         = $crawler->filter('tbody tr:nth-child(2) td:nth-child(3)')->text();
        $electricity = $crawler->filter('tbody tr:nth-child(2) td:nth-child(4)')->text();
        $invoices    = $crawler->filter('tbody tr:nth-child(2) td:nth-child(5)')->text();

        $this->assertEquals($this->currentDate->format('d-m-Y'), $date);
        $this->assertEquals('54.67 m3, 0 zł', $water);
        $this->assertEquals('12.5 m3, 0 zł', $gas);
        $this->assertEquals('1.21 kwH, 0 zł', $electricity);
    }

    public function testIfLandlordCanDeleteReading(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId() . '/utility-meters');
        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId() . '/utility-meters', $crawler->getUri());

        $deleteReadingButton  = $crawler->filter('tbody tr:nth-child(1) th a:nth-child(2)')->attr('href');
        $this->assertEquals('/panel/flats/'. $this->flat->getId() .'/utility-meters/'. $this->utilityMeterReading->getId() .'/delete', $deleteReadingButton);

        $crawler = $this->client->request('GET', '/panel/flats/'. $this->flat->getId() .'/utility-meters/'. $this->utilityMeterReading->getId() .'/delete');
        $crawler = $this->client->followRedirect();
        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId(). '/utility-meters', $crawler->getUri());

        $date2        = $crawler->filter('tbody tr:nth-child(1) th')->text();
        $water2       = $crawler->filter('tbody tr:nth-child(1) td:nth-child(2)')->text();
        $gas2         = $crawler->filter('tbody tr:nth-child(1) td:nth-child(3)')->text();
        $electricity2 = $crawler->filter('tbody tr:nth-child(1) td:nth-child(4)')->text();
        $invoices2    = $crawler->filter('tbody tr:nth-child(1) td:nth-child(5)')->text();

        $this->assertEquals($this->currentDate->format('d-m-Y'), $date2);
        $this->assertEquals('54.67 m3, 0 zł', $water2);
        $this->assertEquals('12.5 m3, 0 zł', $gas2);
        $this->assertEquals('1.21 kwH, 0 zł', $electricity2);
        $this->assertEquals('Invoices: 0', $invoices2);

        $rows = $crawler->filter('tbody tr')->count();
        $this->assertEquals(1, $rows);
    }

    public function testIfLandlordCanDeleteInvoice(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId());
        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId(), $crawler->getUri());

        $form = $crawler->filter('form[name="utility_meters_reading"]')->form([
            'utility_meters_reading[water_cost]' => '125.4',
            'utility_meters_reading[gas_cost]' => '254.247',
            'utility_meters_reading[electricity_cost]' => '167.90',
            'utility_meters_reading[invoices][0]' => $this->appKernel->getProjectDir() . '/tests/e2e/fixtures/agreement.pdf',
        ]);

        $crawler = $this->client->submit($form);
        $crawler = $this->client->followRedirect();
        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId(). '/utility-meters', $crawler->getUri());

        $invoices = $crawler->filter('tbody tr:nth-child(2) td:nth-child(5)')->text();
        $this->assertMatchesRegularExpression('/Invoices: 1 1\. agreement-[a-z0-9]{13}\.pdf/', $invoices);

        $deleteInvoiceButtonUrl  = $crawler->filter('tbody tr:nth-child(2) .invoices-collapse a:nth-child(2)')->attr('href');
        $this->assertMatchesRegularExpression('/\/panel\/flats\/'. $this->flat->getId() .'\/utility-meters\/'. $this->utilityMeterReadingTwo->getId() .'\/delete-invoice\/agreement-[a-z0-9]{13}\.pdf/', $deleteInvoiceButtonUrl);

        $crawler = $this->client->request('GET', $deleteInvoiceButtonUrl);
        $crawler = $this->client->followRedirect();
        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId(). '/utility-meters', $crawler->getUri());

        $invoices = $crawler->filter('tbody tr:nth-child(2) td:nth-child(5)')->text();
        $this->assertEquals('Invoices: 0', $invoices);
    }
}