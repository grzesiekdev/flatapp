<?php

namespace App\Tests\functional\Controller\Flats;

use App\Entity\Flat;
use App\Entity\User\Type\Tenant;
use App\Repository\FlatRepository;
use App\Repository\LandlordRepository;
use App\Service\InvitationCodeHandler;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class FlatsHelperControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManager $entityManager;
    private Flat $flat;
    private FlatRepository $flatRepository;
    private InvitationCodeHandler $invitationCodeHandler;
    private UserPasswordHasherInterface $userPasswordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $container = static::getContainer();
        $this->userPasswordHasher = $container->get(UserPasswordHasherInterface::class);;

        $this->flatRepository = $container->get(FlatRepository::class);
        $this->invitationCodeHandler = $container->get(InvitationCodeHandler::class);
        $userRepository = $container->get(LandlordRepository::class);

        $landlord = $userRepository->findOneByEmail('test_env_user@test.pl');
        $this->client->loginUser($landlord);

        $this->flat = new Flat();
        $this->flat->setArea(30);
        $this->flat->setNumberOfRooms(2);
        $this->flat->setRent(3000);
        $this->flat->setAddress('Testowa 12, 34-123 Testowo');
        $this->flat->setFloor(3);
        $this->flat->setMaxFloor(4);
        $this->flat->setLandlord($landlord);

        $this->entityManager->persist($this->flat);
        $this->entityManager->flush();
    }

    public function testUserGeneratingInvitationCode(): void
    {
        $id = $this->flat->getId();
        $crawler = $this->client->request('GET', '/panel/flats/' . $id . '/generate-invitation-code');

        $this->assertMatchesRegularExpression('/[0-9A-Z]{26}/', $this->flat->getInvitationCode());
    }

    public function testUserGeneratingInvitationCodeForDisplayingCode(): void
    {
        $id = $this->flat->getId();
        $crawler = $this->client->request('GET', '/panel/flats/' . $id . '/generate-invitation-code');
        $crawler = $this->client->followRedirect();

        $code = $crawler->filter('#invitation-code')->text();

        $this->assertEquals($this->flat->getInvitationCode()->toBase32(), $code);
    }

    public function testUserGeneratingInvitationCodeForWholeScenario(): void
    {
        $id = $this->flat->getId();
        $crawler = $this->client->request('GET', '/panel/flats/' . $id);
        $link = $crawler->selectLink('Generate invitation code')->link();
        $this->client->click($link);
        $crawler = $this->client->followRedirect();

        $flat = $this->flatRepository->findOneBy(['id' => $id]);
        $this->flat = $flat;

        $code = $crawler->filter('#invitation-code')->text();
        $expirationDate = $crawler->filter('#expiration-date')->text();

        $this->assertEquals($this->flat->getInvitationCode()->toBase32(), $code);
        $this->assertEquals(
            'Valid until: ' . $this->invitationCodeHandler->getExpirationDate($this->flat->getInvitationCode())->modify('+2 hours')->format('d-m-Y H:i:s'),
            $expirationDate
        );
    }

    public function testUserDeletingInvitationCode() : void
    {
        $id = $this->flat->getId();

        $crawler = $this->client->request('GET', '/panel/flats/' . $id . '/generate-invitation-code');
        $this->assertMatchesRegularExpression('/[0-9A-Z]{26}/', $this->flat->getInvitationCode());

        $crawler = $this->client->request('GET', '/panel/flats/' . $id . '/delete-invitation-code');

        $flat = $this->flatRepository->findOneBy(['id' => $id]);
        $this->flat = $flat;

        $this->assertNull($this->flat->getInvitationCode());
    }

    public function testUserDeletingInvitationCodeForWholeScenario(): void
    {
        $id = $this->flat->getId();
        $crawler = $this->client->request('GET', '/panel/flats/' . $id);
        $link = $crawler->selectLink('Generate invitation code')->link();
        $this->client->click($link);
        $crawler = $this->client->followRedirect();

        $link = $crawler->filter('.remove-invitation-code')->link();
        $this->client->click($link);
        $crawler = $this->client->followRedirect();

        $flat = $this->flatRepository->findOneBy(['id' => $id]);
        $this->flat = $flat;

        $this->assertNull($this->flat->getInvitationCode());
    }

    public function testDeleteTenantFromFlat(): void
    {
        $user = new Tenant();
        $user->setEmail('test_env_tenant_2@test.pl');
        $user->setName('Test tenant');
        $user->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user,
                'test12'
            )
        );
        $user->setDateOfBirth(new \DateTime('1922-02-01'));
        $this->entityManager->persist($user);
        $this->flat->addTenant($user);

        $this->entityManager->persist($this->flat);
        $this->entityManager->flush();

        $tenantId = $this->flat->getTenants()[0]->getId();
        $flatId = $this->flat->getId();

        // before removing tenant, we except that getTenants() will return User, not Null
        $this->assertNotNull($this->flat->getTenants()[0]);
        $crawler = $this->client->request('GET', '/panel/flats/' . $flatId . '/remove-tenant/' . $tenantId);

        // after removing tenant, we expect to get Null
        $this->assertNull($this->flat->getTenants()[0]);
    }

    public function testDeleteTenantFromFlatWithCrawler(): void
    {
        $user = new Tenant();
        $user->setEmail('test_env_tenant_2@test.pl');
        $user->setName('Test tenant');
        $user->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user,
                'test12'
            )
        );
        $user->setDateOfBirth(new \DateTime('1922-02-01'));
        $this->entityManager->persist($user);
        $this->flat->addTenant($user);

        $this->entityManager->persist($this->flat);
        $this->entityManager->flush();

        $flatId = $this->flat->getId();

        // before removing tenant, we except that getTenants() will return User, not Null
        $this->assertNotNull($this->flat->getTenants()[0]);
        $crawler = $this->client->request('GET', '/panel/flats/' . $flatId);

        $link = $crawler->filter('.remove-tenant-from-flat')->link();
        $this->client->click($link);
        $crawler = $this->client->followRedirect();
        
        $this->flat = $this->flatRepository->findOneById(['id' => $this->flat->getId()]);

        // after removing tenant, we expect to get Null
        $this->assertNull($this->flat->getTenants()[0]);
    }
}