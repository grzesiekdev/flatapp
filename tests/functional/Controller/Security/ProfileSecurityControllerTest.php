<?php

namespace App\Tests\functional\Controller\Security;

use App\Entity\Flat;
use App\Entity\User\Type\Landlord;
use App\Entity\User\Type\Tenant;
use App\Repository\FlatRepository;
use App\Repository\TenantRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileSecurityControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private Tenant $tenant;
    private Tenant $tenantTwo;
    private Tenant $tenantThree;
    private Landlord $landlord;
    private Landlord $landlordTwo;
    private EntityManager $entityManager;
    private KernelInterface $appKernel;
    private TenantRepository $tenantRepository;
    private FlatRepository $flatRepository;
    private Flat $flat;

    /*
     * In this test we have the following relations:
     * Tenant 1 -> related with Landlord 1 by Flat 1
     * Tenant 2 -> not related to anyone
     * Tenant 3 -> related with Landlord 1 by Flat 1
     * Landlord 1 -> related with Tenant 1 and Tenant 3 by Flat 1
     * Landlord 2 -> not related to anyone
     */

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $userPasswordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->appKernel = self::getContainer()->get(KernelInterface::class);
        $this->tenantRepository = self::getContainer()->get(TenantRepository::class);
        $this->flatRepository = self::getContainer()->get(FlatRepository::class);

        $this->tenant = new Tenant();
        $this->tenant->setName('Jan Kowalski')
            ->setEmail('jkowalski@tenant.pl')
            ->setPassword(
                $userPasswordHasher->hashPassword(
                    $this->tenant,
                    'test12'
                )
            )
            ->setDateOfBirth(new \DateTime('1922-02-01'))
            ->setRoles(['ROLE_TENANT'])
            ->setImage('default-profile-picture.png')
        ;

        $this->tenantTwo = new Tenant();
        $this->tenantTwo->setName('Jan Kowalski')
            ->setEmail('jkowalski2@tenant.pl')
            ->setPassword(
                $userPasswordHasher->hashPassword(
                    $this->tenantTwo,
                    'test12'
                )
            )
            ->setDateOfBirth(new \DateTime('1922-02-01'))
            ->setRoles(['ROLE_TENANT'])
            ->setImage('default-profile-picture.png')
        ;

        $this->tenantThree = new Tenant();
        $this->tenantThree->setName('Jan Kowalski')
            ->setEmail('jkowalski3@tenant.pl')
            ->setPassword(
                $userPasswordHasher->hashPassword(
                    $this->tenantThree,
                    'test12'
                )
            )
            ->setDateOfBirth(new \DateTime('1922-02-01'))
            ->setRoles(['ROLE_TENANT'])
            ->setImage('default-profile-picture.png')
        ;

        $this->landlord = new Landlord();
        $this->landlord->setName('Jan Kowalski')
            ->setEmail('jkowalski@landlord.pl')
            ->setPassword(
                $userPasswordHasher->hashPassword(
                    $this->landlord,
                    'test12'
                )
            )
            ->setDateOfBirth(new \DateTime('1922-02-01'))
            ->setRoles(['ROLE_LANDLORD'])
            ->setImage('default-profile-picture.png')
        ;

        $this->landlordTwo = new Landlord();
        $this->landlordTwo->setName('Jan Kowalski')
            ->setEmail('jkowalski2@landlord.pl')
            ->setPassword(
                $userPasswordHasher->hashPassword(
                    $this->landlordTwo,
                    'test12'
                )
            )
            ->setDateOfBirth(new \DateTime('1922-02-01'))
            ->setRoles(['ROLE_LANDLORD'])
            ->setImage('default-profile-picture.png')
        ;

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

        $this->entityManager->persist($this->flat);
        $this->entityManager->persist($this->landlord);
        $this->entityManager->persist($this->landlordTwo);
        $this->entityManager->persist($this->tenant);
        $this->entityManager->persist($this->tenantTwo);
        $this->entityManager->persist($this->tenantThree);
        $this->entityManager->flush();
    }

    public function testIfTenantCanAccessOwnProfile(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->tenant->getId());

        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfTenantCanAccessLandlordProfile(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->landlord->getId());

        $this->assertEquals('http://localhost/panel/profile/' . $this->landlord->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfTenantCannotAccessAnotherTenantProfile(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->tenantTwo->getId());

        $this->assertEquals('http://localhost/panel/profile/' . $this->tenantTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantCannotAccessNotRelatedLandlordProfile(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->landlordTwo->getId());

        $this->assertEquals('http://localhost/panel/profile/' . $this->landlordTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantCannotAccessPreviousLandlordProfile(): void
    {
        $this->client->loginUser($this->tenant);

        $this->flat->removeTenant($this->tenant);
        $this->entityManager->persist($this->tenant);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', '/panel/profile/' . $this->landlord->getId());

        $this->assertEquals('http://localhost/panel/profile/' . $this->landlord->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantCannotAccessRandomNonExistentProfile(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/profile/' . 3421232);

        $this->assertEquals('http://localhost/panel/profile/3421232', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCanAccessOwnProfile(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->landlord->getId());

        $this->assertEquals('http://localhost/panel/profile/' . $this->landlord->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfLandlordCanAccessTenantProfile(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->tenant->getId());

        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfLandlordCanAccessSecondTenantProfile(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->tenantThree->getId());

        $this->assertEquals('http://localhost/panel/profile/' . $this->tenantThree->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfLandlordCannotAccessAnotherLandlordProfile(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->landlordTwo->getId());

        $this->assertEquals('http://localhost/panel/profile/' . $this->landlordTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCannotAccessNotRelatedTenantProfile(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->tenantTwo->getId());

        $this->assertEquals('http://localhost/panel/profile/' . $this->tenantTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCannotAccessRandomNonExistentProfile(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/profile/' . 342134232);

        $this->assertEquals('http://localhost/panel/profile/342134232', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCannotAccessPreviousTenantProfile(): void
    {
        $this->client->loginUser($this->landlord);

        $this->flat->removeTenant($this->tenant);
        $this->entityManager->persist($this->tenant);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', '/panel/profile/' . $this->tenant->getId());

        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantCanEditOwnProfile(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->tenant->getId() . '/edit');

        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId() . '/edit', $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfTenantCannotEditAnotherTenantProfile(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->tenantTwo->getId() . '/edit');

        $this->assertEquals('http://localhost/panel/profile/' . $this->tenantTwo->getId() . '/edit', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantCannotEditLandlordProfile(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->landlord->getId() . '/edit');

        $this->assertEquals('http://localhost/panel/profile/' . $this->landlord->getId() . '/edit', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCanEditOwnProfile(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->landlord->getId() . '/edit');

        $this->assertEquals('http://localhost/panel/profile/' . $this->landlord->getId() . '/edit', $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfLandlordCannotEditAnotherLandlordProfile(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->landlordTwo->getId() . '/edit');

        $this->assertEquals('http://localhost/panel/profile/' . $this->landlordTwo->getId() . '/edit', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCannotEditTenantProfile(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->tenant->getId() . '/edit');

        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId() . '/edit', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

}