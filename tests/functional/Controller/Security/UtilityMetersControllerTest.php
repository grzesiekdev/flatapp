<?php

namespace App\Tests\functional\Controller\Security;

use App\Entity\Flat;
use App\Entity\User\Type\Landlord;
use App\Entity\User\Type\Tenant;
use App\Entity\UtilityMeterReading;
use App\Repository\FlatRepository;
use App\Repository\TenantRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use App\Tests\Utils\TestDataProvider;

class UtilityMetersControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private TestDataProvider $testDataProvider;
    private Tenant $tenant;
    private Tenant $tenantTwo;
    private Tenant $tenantThree;
    private Tenant $tenantFour;
    private Landlord $landlord;
    private Landlord $landlordTwo;
    private Landlord $landlordThree;
    private EntityManager $entityManager;
    private KernelInterface $appKernel;
    private TenantRepository $tenantRepository;
    private FlatRepository $flatRepository;
    private Flat $flat;
    private Flat $flatTwo;
    private Flat $flatThree;
    private UtilityMeterReading $utilityMeterReading;
    private UtilityMeterReading $utilityMeterReadingTwo;
    private UtilityMeterReading $utilityMeterReadingThree;

    /*
     * In this test we have the following relations:
     * Tenant 1 -> related with Landlord 1 by Flat 1
     * Tenant 2 -> not related to anyone
     * Tenant 3 -> related with Landlord 1 by Flat 1
     * Tenant 4 -> related with Landlord 1 by Flat 2
     * Landlord 1 -> related with Tenant 1 and Tenant 3 by Flat 1, and with Tenant 4 by Flat 2
     * Landlord 2 -> not related to anyone
     * Landlord 3 -> related with no-one, but has Flat 3
     * Utility meter reading 1 -> related with flat 1
     * Utility meter reading 2 -> related with flat 3
     * Utility meter reading 3 -> related with flat 1 - was edited
     */

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->testDataProvider = self::getContainer()->get('App\Tests\Utils\TestDataProvider');
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->appKernel = self::getContainer()->get(KernelInterface::class);
        $this->tenantRepository = self::getContainer()->get(TenantRepository::class);
        $this->flatRepository = self::getContainer()->get(FlatRepository::class);
        $this->flatRepository = self::getContainer()->get(FlatRepository::class);

        $usersData = [
            'tenant1' => [
                'name' => 'Jan Kowalski',
                'email' => 'jkowalski@tenant.pl',
                'password' => 'test12',
                'dob' => '1922-02-01',
                'role' => 'ROLE_TENANT',
                'image' => 'default-profile-picture.png'
            ],
            'tenant2' => [
                'name' => 'Jan Kowalski',
                'email' => 'jkowalski2@tenant.pl',
                'password' => 'test12',
                'dob' => '1922-02-01',
                'role' => 'ROLE_TENANT',
                'image' => 'default-profile-picture.png'
            ],
            'tenant3' => [
                'name' => 'Jan Kowalski',
                'email' => 'jkowalski3@tenant.pl',
                'password' => 'test12',
                'dob' => '1922-02-01',
                'role' => 'ROLE_TENANT',
                'image' => 'default-profile-picture.png'
            ],
            'tenant4' => [
                'name' => 'Jan Kowalski',
                'email' => 'jkowalski4@tenant.pl',
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
            'landlord2' => [
                'name' => 'Jan Kowalski',
                'email' => 'jkowalski2@landlord.pl',
                'password' => 'test12',
                'dob' => '1922-02-01',
                'role' => 'ROLE_LANDLORD',
                'image' => 'default-profile-picture.png'
            ],
            'landlord3' => [
                'name' => 'Jan Kowalski',
                'email' => 'jkowalski3@landlord.pl',
                'password' => 'test12',
                'dob' => '1922-02-01',
                'role' => 'ROLE_LANDLORD',
                'image' => 'default-profile-picture.png'
            ],
        ];

        $users = $this->testDataProvider->provideUsers($usersData);

        $this->tenant = $users['tenant1'];
        $this->tenantTwo = $users['tenant2'];
        $this->tenantThree = $users['tenant3'];
        $this->tenantFour = $users['tenant4'];

        $this->landlord = $users['landlord1'];
        $this->landlordTwo = $users['landlord2'];
        $this->landlordThree = $users['landlord3'];

        $this->utilityMeterReading = new UtilityMeterReading();
        $this->utilityMeterReading->setDate(new \DateTime('now'));
        $this->utilityMeterReading->setWater(['amount' => 1, 'cost' => 2]);
        $this->utilityMeterReading->setGas(['amount' => 1, 'cost' => 2]);
        $this->utilityMeterReading->setElectricity(['amount' => 1, 'cost' => 2]);
        $this->utilityMeterReading->setInvoices(['test-invoice1.pdf']);

        $this->utilityMeterReadingTwo = new UtilityMeterReading();
        $this->utilityMeterReadingTwo->setDate(new \DateTime('now'));
        $this->utilityMeterReadingTwo->setWater(['amount' => 1, 'cost' => 2]);
        $this->utilityMeterReadingTwo->setGas(['amount' => 1, 'cost' => 2]);
        $this->utilityMeterReadingTwo->setElectricity(['amount' => 1, 'cost' => 2]);
        $this->utilityMeterReadingTwo->setInvoices(['test-invoice2.pdf']);

        $this->utilityMeterReadingThree = new UtilityMeterReading();
        $this->utilityMeterReadingThree->setDate(new \DateTime('now'));
        $this->utilityMeterReadingThree->setWater(['amount' => 1, 'cost' => 2]);
        $this->utilityMeterReadingThree->setGas(['amount' => 1, 'cost' => 2]);
        $this->utilityMeterReadingThree->setElectricity(['amount' => 1, 'cost' => 2]);
        $this->utilityMeterReadingThree->setInvoices(['test-invoice3.pdf']);
        $this->utilityMeterReadingThree->setWasEdited(true);

        $this->flat = new Flat();
        $this->flat->setArea(55);
        $this->flat->setNumberOfRooms(2);
        $this->flat->setAddress('Testowa 12');
        $this->flat->setFloor(3);
        $this->flat->setMaxFloor(5);
        $this->flat->setRent(2000);
        $this->flat->setLandlord($this->landlord);
        $this->flat->addTenant($this->tenant);
        $this->flat->addTenant($this->tenantThree);
        $this->flat->addUtilityMeterReading($this->utilityMeterReading);
        $this->flat->addUtilityMeterReading($this->utilityMeterReadingThree);

        $this->flatTwo = new Flat();
        $this->flatTwo->setArea(55);
        $this->flatTwo->setNumberOfRooms(2);
        $this->flatTwo->setAddress('Testowa 12');
        $this->flatTwo->setFloor(3);
        $this->flatTwo->setMaxFloor(5);
        $this->flatTwo->setRent(2000);
        $this->flatTwo->setLandlord($this->landlord);
        $this->flatTwo->addTenant($this->tenantFour);

        $this->flatThree = new Flat();
        $this->flatThree->setArea(55);
        $this->flatThree->setNumberOfRooms(2);
        $this->flatThree->setAddress('Testowa 12');
        $this->flatThree->setFloor(3);
        $this->flatThree->setMaxFloor(5);
        $this->flatThree->setRent(2000);
        $this->flatThree->setLandlord($this->landlordThree);
        $this->flatThree->addUtilityMeterReading($this->utilityMeterReadingTwo);

        $this->landlord->addFlat($this->flat);
        $this->landlord->addFlat($this->flatTwo);
        $this->landlordThree->addFlat($this->flatThree);

        $this->entityManager->persist($this->utilityMeterReading);
        $this->entityManager->persist($this->utilityMeterReadingTwo);
        $this->entityManager->persist($this->utilityMeterReadingThree);
        $this->entityManager->persist($this->flat);
        $this->entityManager->persist($this->flatTwo);
        $this->entityManager->persist($this->flatThree);
        $this->entityManager->persist($this->landlord);
        $this->entityManager->persist($this->landlordTwo);
        $this->entityManager->persist($this->landlordThree);
        $this->entityManager->persist($this->tenant);
        $this->entityManager->persist($this->tenantTwo);
        $this->entityManager->persist($this->tenantThree);
        $this->entityManager->persist($this->tenantFour);
        $this->entityManager->flush();
    }

    /*
     * Tests for viewing utility meters readings
     */
   public function testIfTenantCanViewOwnReadings(): void
   {
       $this->client->loginUser($this->tenant);
       $crawler = $this->client->request('GET', '/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters');

       $this->assertEquals('http://localhost/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters', $crawler->getUri());
       $this->assertResponseStatusCodeSame(200);
   }

    public function testIfTenantCannotViewOtherFlatReadings(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flatTwo->getId() . '/utility-meters');

        $this->assertEquals('http://localhost/panel/flats/' . $this->flatTwo->getId() . '/utility-meters', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantCannotViewNonExistentReadings(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/' . 123123123 . '/utility-meters');

        $this->assertEquals('http://localhost/panel/flats/123123123/utility-meters', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCanViewOwnReadings(): void
    {
        $this->client->loginUser($this->landlord);

        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId() . '/utility-meters');

        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId() . '/utility-meters', $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);

        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flatTwo->getId() . '/utility-meters');

        $this->assertEquals('http://localhost/panel/flats/' . $this->flatTwo->getId() . '/utility-meters', $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfLandlordCannotViewOtherLandlordsReadings(): void
    {
        $this->client->loginUser($this->landlord);

        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flatThree->getId() . '/utility-meters');

        $this->assertEquals('http://localhost/panel/flats/' . $this->flatThree->getId() . '/utility-meters', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCannotViewNotExistentReading(): void
    {
        $this->client->loginUser($this->landlord);

        $crawler = $this->client->request('GET', '/panel/flats/123123123/utility-meters');

        $this->assertEquals('http://localhost/panel/flats/123123123/utility-meters', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    /*
     * Tests for adding readings
     */
    public function testIfTenantCanAddNewReading(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters/add-new');

        $this->assertEquals('http://localhost/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters/add-new', $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfTenantCannotAddReadingToOtherFlat(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->tenantFour->getFlatId()->getId() . '/utility-meters/add-new');

        $this->assertEquals('http://localhost/panel/flats/' . $this->tenantFour->getFlatId()->getId() . '/utility-meters/add-new', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCannotAddReadingToOwnFlat(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId() . '/utility-meters/add-new');

        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId() . '/utility-meters/add-new', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCannotAddReadingToOtherFlat(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flatThree->getId() . '/utility-meters/add-new');

        $this->assertEquals('http://localhost/panel/flats/' . $this->flatThree->getId() . '/utility-meters/add-new', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    /*
     * Tests for editing readings
     */

    public function testIfTenantCannotEditOwnReading(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters/' . $this->utilityMeterReading->getId());

        $this->assertEquals('http://localhost/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters/' . $this->utilityMeterReading->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantCannotEditDifferentReading(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId());

        $this->assertEquals('http://localhost/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCanEditOwnReading(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId() . '/utility-meters/' . $this->utilityMeterReading->getId());

        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId() . '/utility-meters/' . $this->utilityMeterReading->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfLandlordCannotEditAlreadyEditedReading(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId() . '/utility-meters/' . $this->utilityMeterReadingThree->getId());

        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId() . '/utility-meters/' . $this->utilityMeterReadingThree->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCannotEditDifferentReadingForDifferentFlat(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flatThree->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId());

        $this->assertEquals('http://localhost/panel/flats/' . $this->flatThree->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCannotEditOwnReadingForDifferentFlat(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flatThree->getId() . '/utility-meters/' . $this->utilityMeterReading->getId());

        $this->assertEquals('http://localhost/panel/flats/' . $this->flatThree->getId() . '/utility-meters/' . $this->utilityMeterReading->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCannotEditDifferentReadingForOwnFlat(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId());

        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    /*
     * Tests for deleting readings
     */
    public function testIfTenantCannotDeleteOwnReading(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters/' . $this->utilityMeterReading->getId() . '/delete');

        $this->assertEquals('http://localhost/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters/' . $this->utilityMeterReading->getId() . '/delete', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantCannotDeleteDifferentReading(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId() . '/delete');

        $this->assertEquals('http://localhost/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId() . '/delete', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCanDeleteOwnReading(): void
    {
        $this->client->loginUser($this->landlord);
        $readingId = $this->utilityMeterReading->getId();

        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId() . '/utility-meters/' . $readingId . '/delete');
        $crawler = $this->client->followRedirect();

        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId() . '/utility-meters', $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfLandlordCannotDeleteDifferentReadingForOwnFlat(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId() . '/delete');

        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId() . '/delete', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCannotDeleteDifferentReadingForDifferentFlat(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flatThree->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId() . '/delete');

        $this->assertEquals('http://localhost/panel/flats/' . $this->flatThree->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId() . '/delete', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCannotDeleteOwnReadingForDifferentFlat(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flatThree->getId() . '/utility-meters/' . $this->utilityMeterReading->getId() . '/delete');

        $this->assertEquals('http://localhost/panel/flats/' . $this->flatThree->getId() . '/utility-meters/' . $this->utilityMeterReading->getId() . '/delete', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    /*
     * Tests for deleting readings
     */
    public function testIfLandlordCanDeleteInvoice(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId() . '/utility-meters/' . $this->utilityMeterReading->getId() . '/delete-invoice/' . $this->utilityMeterReading->getInvoices()[0]);
        $crawler = $this->client->followRedirect();

        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId() . '/utility-meters', $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfLandlordCannotDeleteOwnReadingInvoiceForDifferentOwnReading(): void
    {
        // in this test, we want to check if a user cannot delete 'invoice A for reading A',
        // when accessing route 'invoice A for reading B'
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId() . '/utility-meters/' . $this->utilityMeterReading->getId() . '/delete-invoice/' . $this->utilityMeterReadingThree->getInvoices()[0]);

        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId() . '/utility-meters/' . $this->utilityMeterReading->getId() . '/delete-invoice/' . $this->utilityMeterReadingThree->getInvoices()[0], $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantCannotDeleteInvoice(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId() . '/delete-invoice/' . $this->utilityMeterReading->getInvoices()[0]);

        $this->assertEquals('http://localhost/panel/flats/' . $this->tenant->getFlatId()->getId() . '/utility-meters/' . $this->utilityMeterReadingTwo->getId() . '/delete-invoice/' . $this->utilityMeterReading->getInvoices()[0], $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }
}