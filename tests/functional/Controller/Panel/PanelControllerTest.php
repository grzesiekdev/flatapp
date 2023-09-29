<?php

namespace App\Tests\functional\Controller\Panel;

use App\Entity\Flat;
use App\Entity\Specialist;
use App\Entity\User\Type\Landlord;
use App\Entity\User\Type\Tenant;
use App\Repository\FlatRepository;
use App\Repository\SpecialistRepository;
use App\Repository\TenantRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use App\Tests\Utils\TestDataProvider;

class PanelControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private TestDataProvider $testDataProvider;
    private Tenant $tenant;
    private Tenant $tenantTwo;
    private Tenant $tenantThree;
    private Tenant $tenantFour;
    private Landlord $landlord;
    private Landlord $landlordTwo;
    private EntityManager $entityManager;
    private KernelInterface $appKernel;
    private TenantRepository $tenantRepository;
    private FlatRepository $flatRepository;
    private SpecialistRepository $specialistRepository;
    private Flat $flat;
    private Flat $flatTwo;
    private Flat $flatThree;

    /*
     * In this test we have the following relations:
     * Tenant 1 -> related with Landlord 1 by Flat 1
     * Tenant 2 -> related with Landlord 1 by Flat 2
     * Tenant 3 -> related witch Landlord 1 by Flat 2
     * Tenant 4 -> related with Landlord 2 by Flat 3
     * Landlord 1 -> related with Tenant 1 Flat 1, and with Tenant 2 and Tenant 3 by Flat 2
     * Landlord 2 -> related with Tenant 4 by Flat 3
     */

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->testDataProvider = self::getContainer()->get('App\Tests\Utils\TestDataProvider');
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->appKernel = self::getContainer()->get(KernelInterface::class);
        $this->tenantRepository = self::getContainer()->get(TenantRepository::class);
        $this->flatRepository = self::getContainer()->get(FlatRepository::class);
        $this->specialistRepository = self::getContainer()->get(SpecialistRepository::class);

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
        ];

        $users = $this->testDataProvider->provideUsers($usersData);

        $this->tenant = $users['tenant1'];
        $this->tenantTwo = $users['tenant2'];
        $this->tenantThree = $users['tenant3'];
        $this->tenantFour = $users['tenant4'];

        $this->landlord = $users['landlord1'];
        $this->landlordTwo = $users['landlord2'];

        $this->flat = new Flat();
        $this->flat->setArea(55);
        $this->flat->setNumberOfRooms(2);
        $this->flat->setAddress('Testowa 12');
        $this->flat->setFloor(3);
        $this->flat->setMaxFloor(5);
        $this->flat->setRent(2000);
        $this->flat->setLandlord($this->landlord);
        $this->flat->addTenant($this->tenant);

        $this->flatTwo = new Flat();
        $this->flatTwo->setArea(25);
        $this->flatTwo->setNumberOfRooms(2);
        $this->flatTwo->setAddress('Słoneczna 32');
        $this->flatTwo->setFloor(3);
        $this->flatTwo->setMaxFloor(5);
        $this->flatTwo->setRent(3040);
        $this->flatTwo->setLandlord($this->landlord);
        $this->flat->addTenant($this->tenantTwo);
        $this->flat->addTenant($this->tenantThree);

        $this->flatThree = new Flat();
        $this->flatThree->setArea(55);
        $this->flatThree->setNumberOfRooms(2);
        $this->flatThree->setAddress('Testowa 12');
        $this->flatThree->setFloor(3);
        $this->flatThree->setMaxFloor(5);
        $this->flatThree->setRent(2540);
        $this->flatThree->setLandlord($this->landlordTwo);
        $this->flatThree->addTenant($this->tenantFour);

        $this->landlord->addFlat($this->flat);
        $this->landlord->addFlat($this->flatTwo);
        $this->landlordTwo->addFlat($this->flatThree);
        $this->entityManager->persist($this->flat);
        $this->entityManager->persist($this->flatTwo);
        $this->entityManager->persist($this->flatThree);
        $this->entityManager->persist($this->landlord);
        $this->entityManager->persist($this->landlordTwo);
        $this->entityManager->persist($this->tenant);
        $this->entityManager->persist($this->tenantTwo);
        $this->entityManager->persist($this->tenantThree);
        $this->entityManager->persist($this->tenantFour);
        $this->entityManager->flush();
    }

    public function testIfLandlordCanSeeCorrectFlatDataOnDashboard(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel');
        $this->assertResponseStatusCodeSame(200);

        $flatsNumber = $crawler->filter('.flats-number h6')->text();
        $tenantsNumber = $crawler->filter('.tenants-number h6')->text();
        $monthlyIncome = $crawler->filter('.monthly-income h6')->text();

        $this->assertEquals('2', $flatsNumber);
        $this->assertEquals('3', $tenantsNumber);
        $this->assertEquals('5040 zł', $monthlyIncome);

        $this->flat->removeTenant($this->tenant);
        $this->entityManager->persist($this->flat);
        $this->entityManager->persist($this->flat);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flatTwo->getId());
        $this->assertResponseStatusCodeSame(200);
        $deleteBtn = $crawler->selectLink('Delete')->link();
        $crawler = $this->client->click($deleteBtn);

        $crawler = $this->client->request('GET', '/panel');
        $this->assertResponseStatusCodeSame(200);

        $flatsNumber = $crawler->filter('.flats-number h6')->text();
        $tenantsNumber = $crawler->filter('.tenants-number h6')->text();
        $monthlyIncome = $crawler->filter('.monthly-income h6')->text();

        $this->assertEquals('1', $flatsNumber);
        $this->assertEquals('2', $tenantsNumber);
        $this->assertEquals('2000 zł', $monthlyIncome);

        $this->client->loginUser($this->landlordTwo);
        $crawler = $this->client->request('GET', '/panel');
        $this->assertResponseStatusCodeSame(200);

        $flatsNumber = $crawler->filter('.flats-number h6')->text();
        $tenantsNumber = $crawler->filter('.tenants-number h6')->text();
        $monthlyIncome = $crawler->filter('.monthly-income h6')->text();

        $this->assertEquals('1', $flatsNumber);
        $this->assertEquals('1', $tenantsNumber);
        $this->assertEquals('2540 zł', $monthlyIncome);
    }

}