<?php

namespace App\Tests\functional\Controller\Security;

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

class SpecialistSecurityControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private TestDataProvider $testDataProvider;
    private Tenant $tenant;
    private Tenant $tenantTwo;
    private Tenant $tenantThree;
    private Landlord $landlord;
    private Landlord $landlordTwo;
    private Specialist $specialist;
    private Specialist $specialistTwo;
    private Specialist $specialistThree;
    private EntityManager $entityManager;
    private KernelInterface $appKernel;
    private TenantRepository $tenantRepository;
    private FlatRepository $flatRepository;
    private SpecialistRepository $specialistRepository;
    private Flat $flat;
    private Flat $flatThree;

    /**
     * In this test we have the following relations: <br>
     * Tenant 1 -> related with Landlord 1 by Flat 1 <br>
     * Tenant 2 -> not related to anyone <br>
     * Tenant 3 -> related with Landlord 1 by Flat 1 <br>
     * Landlord 1 -> related with Tenant 1 and Tenant 3 by Flat 1 <br>
     * Landlord 2 -> not related to anyone, but has Flat 3 <br>
     * Specialist 1 -> related with Flat 1 and Flat 3 <br>
     * Specialist 2 -> related with Flat 3 <br>
     * Specialist 3 -> related with Flat 1 <br>
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

        $this->landlord = $users['landlord1'];
        $this->landlordTwo = $users['landlord2'];

        $this->specialist = new Specialist();
        $this->specialist->setName("Test T.");
        $this->specialist->setProfession("Plumber");

        $this->specialistTwo = new Specialist();
        $this->specialistTwo->setName("Rick Warner");
        $this->specialistTwo->setProfession("Electrician");

        $this->specialistThree = new Specialist();
        $this->specialistThree->setName("Albert E.");
        $this->specialistThree->setProfession("Physicist");

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
        $this->flat->addSpecialist($this->specialist);
        $this->flat->addSpecialist($this->specialistThree);

        $this->flatThree = new Flat();
        $this->flatThree->setArea(55);
        $this->flatThree->setNumberOfRooms(2);
        $this->flatThree->setAddress('Testowa 12');
        $this->flatThree->setFloor(3);
        $this->flatThree->setMaxFloor(5);
        $this->flatThree->setRent(2000);
        $this->flatThree->setLandlord($this->landlordTwo);
        $this->flatThree->addSpecialist($this->specialist);
        $this->flatThree->addSpecialist($this->specialistTwo);

        $this->landlord->addFlat($this->flat);
        $this->landlordTwo->addFlat($this->flatThree);

        $this->specialist->addFlat($this->flat);
        $this->specialist->addFlat($this->flatThree);
        $this->specialistTwo->addFlat($this->flatThree);
        $this->specialistThree->addFlat($this->flat);

        $this->entityManager->persist($this->flat);
        $this->entityManager->persist($this->flatThree);
        $this->entityManager->persist($this->landlord);
        $this->entityManager->persist($this->landlordTwo);
        $this->entityManager->persist($this->tenant);
        $this->entityManager->persist($this->tenantTwo);
        $this->entityManager->persist($this->tenantThree);
        $this->entityManager->persist($this->specialist);
        $this->entityManager->persist($this->specialistTwo);
        $this->entityManager->persist($this->specialistThree);
        $this->entityManager->flush();
    }

    /*
     * Tests for viewing list of specialists
     */
    public function testIfTenantCanViewListOfSpecialists(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/specialists');

        $this->assertEquals('http://localhost/panel/specialists', $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfLandlordCanViewListOfSpecialists(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/specialists');

        $this->assertEquals('http://localhost/panel/specialists', $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    /*
     * Tests for viewing specific specialist
     */
    public function testIfTenantCanViewRelatedSpecialist(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/specialists/' . $this->specialist->getId());

        $this->assertEquals('http://localhost/panel/specialists/' . $this->specialist->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfTenantCannotViewNotRelatedSpecialist(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/specialists/' . $this->specialistTwo->getId());

        $this->assertEquals('http://localhost/panel/specialists/' . $this->specialistTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantWithoutFlatCannotViewAnySpecialist(): void
    {
        $this->client->loginUser($this->tenantTwo);
        $crawler = $this->client->request('GET', '/panel/specialists/' . $this->specialist->getId());

        $this->assertEquals('http://localhost/panel/specialists/' . $this->specialist->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);

        $crawler = $this->client->request('GET', '/panel/specialists/' . $this->specialistTwo->getId());

        $this->assertEquals('http://localhost/panel/specialists/' . $this->specialistTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCanViewRelatedSpecialist(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/specialists/' . $this->specialist->getId());

        $this->assertEquals('http://localhost/panel/specialists/' . $this->specialist->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);

        $crawler = $this->client->request('GET', '/panel/specialists/' . $this->specialistThree->getId());

        $this->assertEquals('http://localhost/panel/specialists/' . $this->specialistThree->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfLandlordCanNotViewNotRelatedSpecialist(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/specialists/' . $this->specialistTwo->getId());

        $this->assertEquals('http://localhost/panel/specialists/' . $this->specialistTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordWithoutTenantsCanViewRelatedSpecialist(): void
    {
        $this->client->loginUser($this->landlordTwo);
        $crawler = $this->client->request('GET', '/panel/specialists/' . $this->specialistTwo->getId());

        $this->assertEquals('http://localhost/panel/specialists/' . $this->specialistTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfLandlordWithoutTenantsCanNotViewNotRelatedSpecialist(): void
    {
        $this->client->loginUser($this->landlordTwo);
        $crawler = $this->client->request('GET', '/panel/specialists/' . $this->specialistThree->getId());

        $this->assertEquals('http://localhost/panel/specialists/' . $this->specialistThree->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    /*
     * Tests for adding new specialist
     */
    public function testIfLandlordCanAddSpecialist(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/specialists/new');

        $this->assertEquals('http://localhost/panel/specialists/new', $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfLTenantCanNotAddSpecialist(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/specialists/new');

        $this->assertEquals('http://localhost/panel/specialists/new', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    /*
     * Tests for editing specialist
     */
    public function testIfLandlordCanEditRelatedSpecialist(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/specialists/edit/' . $this->specialist->getId());

        $this->assertEquals('http://localhost/panel/specialists/edit/' . $this->specialist->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);

        $crawler = $this->client->request('GET', '/panel/specialists/edit/' . $this->specialistThree->getId());

        $this->assertEquals('http://localhost/panel/specialists/edit/' . $this->specialistThree->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfLandlordCanNotEditNotRelatedSpecialist(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/specialists/edit/' . $this->specialistTwo->getId());

        $this->assertEquals('http://localhost/panel/specialists/edit/' . $this->specialistTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantCanNotEditRelatedSpecialist(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/specialists/edit/' . $this->specialist->getId());

        $this->assertEquals('http://localhost/panel/specialists/edit/' . $this->specialist->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantCanNotEditNotRelatedSpecialist(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/specialists/edit/' . $this->specialistTwo->getId());

        $this->assertEquals('http://localhost/panel/specialists/edit/' . $this->specialistTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    /*
     * Tests for deleting specialist
     */
    public function testIfLandlordCanDeleteRelatedSpecialist(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/specialists/delete/' . $this->specialist->getId());
        $crawler = $this->client->followRedirect();

        $this->assertResponseStatusCodeSame(200);

        $crawler = $this->client->request('GET', '/panel/specialists/delete/' . $this->specialistThree->getId());
        $crawler = $this->client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfLandlordCanNotDeleteNotRelatedSpecialist(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/specialists/delete/' . $this->specialistTwo->getId());

        $this->assertEquals('http://localhost/panel/specialists/delete/' . $this->specialistTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantCanNotDeleteRelatedSpecialist(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/specialists/delete/' . $this->specialist->getId());

        $this->assertEquals('http://localhost/panel/specialists/delete/' . $this->specialist->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantCanNotDeleteNotRelatedSpecialist(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/specialists/delete/' . $this->specialistTwo->getId());

        $this->assertEquals('http://localhost/panel/specialists/delete/' . $this->specialistTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }
}