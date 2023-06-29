<?php

namespace App\Tests\functional\Controller;

use App\Entity\Flat;
use App\Repository\FlatRepository;
use App\Repository\LandlordRepository;
use App\Service\InvitationCodeHandler;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FlatsHelperControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManager $entityManager;
    private Flat $flat;
    private FlatRepository $flatRepository;
    private InvitationCodeHandler $invitationCodeHandler;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $container = static::getContainer();

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
            'Valid until: ' . $this->invitationCodeHandler->getExpirationDate($this->flat->getInvitationCode())->format('d-m-Y H:i:s'),
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

}