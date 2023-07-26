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

class FlatSecurityControllerTest extends WebTestCase
{
    private KernelBrowser $client;
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

    /*
     * In this test we have the following relations:
     * Tenant 1 -> related with Landlord 1 by Flat 1
     * Tenant 2 -> not related to anyone
     * Tenant 3 -> related with Landlord 1 by Flat 1
     * Tenant 4 -> related with Landlord 1 by Flat 2
     * Landlord 1 -> related with Tenant 1 and Tenant 3 by Flat 1, and with Tenant 4 by Flat 2
     * Landlord 2 -> not related to anyone
     * Landlord 3 -> related with no-one, but has Flat 3
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

        $this->tenantFour = new Tenant();
        $this->tenantFour->setName('Jan Kowalski')
            ->setEmail('jkowalski4@tenant.pl')
            ->setPassword(
                $userPasswordHasher->hashPassword(
                    $this->tenantFour,
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

        $this->landlordThree = new Landlord();
        $this->landlordThree->setName('Jan Kowalski')
            ->setEmail('jkowalski3@landlord.pl')
            ->setPassword(
                $userPasswordHasher->hashPassword(
                    $this->landlordThree,
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

        $this->landlord->addFlat($this->flat);
        $this->landlord->addFlat($this->flatTwo);
        $this->landlordThree->addFlat($this->flatThree);

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
     * Tests for viewing flat
     */
   public function testIfTenantCanViewOwnFlat(): void
   {
       $this->client->loginUser($this->tenant);
       $crawler = $this->client->request('GET', '/panel/flats/' . $this->tenant->getFlatId()->getId());

       $this->assertEquals('http://localhost/panel/flats/' . $this->tenant->getFlatId()->getId(), $crawler->getUri());
       $this->assertResponseStatusCodeSame(200);
   }

    public function testIfTenantCannotViewDifferentFlat(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flatTwo->getId());

        $this->assertEquals('http://localhost/panel/flats/' . $this->flatTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantCannotViewNonExistentFlat(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/' . 123123123);

        $this->assertEquals('http://localhost/panel/flats/123123123', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantCannotViewPreviousFlat(): void
    {
        $this->client->loginUser($this->tenant);

        $this->flat->removeTenant($this->tenant);
        $this->entityManager->persist($this->tenant);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId());

        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCanViewOwnFlats(): void
    {
        $this->client->loginUser($this->landlord);

        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId());

        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);

        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flatTwo->getId());

        $this->assertEquals('http://localhost/panel/flats/' . $this->flatTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfLandlordCannotViewOtherLandlordsFlats(): void
    {
        $this->client->loginUser($this->landlord);

        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flatThree->getId());

        $this->assertEquals('http://localhost/panel/flats/' . $this->flatThree->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCannotViewNotExistentFlat(): void
    {
        $this->client->loginUser($this->landlord);

        $crawler = $this->client->request('GET', '/panel/flats/123123123');

        $this->assertEquals('http://localhost/panel/flats/123123123', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCannotViewPreviousFlat(): void
    {
        $this->client->loginUser($this->landlord);

        $this->flat->removeTenant($this->tenant);
        $this->flat->removeTenant($this->tenantThree);
        $this->landlord->removeFlat($this->flat);
        $this->entityManager->persist($this->flat);
        $this->entityManager->persist($this->landlord);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId());

        $this->assertResponseStatusCodeSame(301);
    }

    /*
     * Tests for editing flat
     */
    public function testIfTenantCannotEditOwnFlat(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/edit/' . $this->tenant->getFlatId()->getId());

        $this->assertEquals('http://localhost/panel/flats/edit/' . $this->tenant->getFlatId()->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantCannotEditRandomFlat(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/edit/' . $this->tenantFour->getFlatId()->getId());

        $this->assertEquals('http://localhost/panel/flats/edit/' . $this->tenantFour->getFlatId()->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCanEditOwnFlat(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/edit/' . $this->flat->getId());

        $this->assertEquals('http://localhost/panel/flats/edit/' . $this->flat->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfLandlordCannotEditDifferentFlat(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/edit/' . $this->flatThree->getId());

        $this->assertEquals('http://localhost/panel/flats/edit/' . $this->flatThree->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCannotEditPreviousFlat(): void
    {
        $this->client->loginUser($this->landlord);

        $this->flat->removeTenant($this->tenant);
        $this->flat->removeTenant($this->tenantThree);
        $this->landlord->removeFlat($this->flat);
        $this->entityManager->persist($this->flat);
        $this->entityManager->persist($this->landlord);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', '/panel/flats/edit/' . $this->flat->getId());

        $this->assertEquals('http://localhost/panel/flats/edit/' . $this->flat->getId(), $crawler->getUri());
        $crawler = $this->client->followRedirect();
        $this->assertResponseStatusCodeSame(403);
    }

    /*
     * Tests for deleting flat
     */
    public function testIfTenantCannotDeleteOwnFlat(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/delete/' . $this->tenant->getFlatId()->getId());

        $this->assertEquals('http://localhost/panel/flats/delete/' . $this->tenant->getFlatId()->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantCannotDeleteRandomFlat(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/delete/' . $this->tenantFour->getFlatId()->getId());

        $this->assertEquals('http://localhost/panel/flats/delete/' . $this->tenantFour->getFlatId()->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCanDeleteOwnFlat(): void
    {
        $this->client->loginUser($this->landlord);
        $flatId = $this->flat->getId();

        $this->flat->removeTenant($this->tenant);
        $this->flat->removeTenant($this->tenantThree);
        $this->entityManager->persist($this->flat);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', '/panel/flats/delete/' . $flatId);

        $this->assertEquals('http://localhost/panel/flats/delete/' . $flatId, $crawler->getUri());
        $crawler = $this->client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfLandlordCannotDeleteDifferentFlat(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/delete/' . $this->flatThree->getId());

        $this->assertEquals('http://localhost/panel/flats/delete/' . $this->flatThree->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    /*
     * Tests for adding flat
     */
    public function testIfTenantCannotAddNewFlat(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/flats/new');

        $this->assertEquals('http://localhost/panel/flats/new', $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfLandlordCanAddNewFlat(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/flats/new');

        $this->assertEquals('http://localhost/panel/flats/new', $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }
}