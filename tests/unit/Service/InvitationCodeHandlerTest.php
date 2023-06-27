<?php

namespace App\Tests\unit\Service;

use App\Entity\Flat;
use App\Repository\LandlordRepository;
use App\Service\InvitationCodeHandler;
use DateTime;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

class InvitationCodeHandlerTest extends KernelTestCase
{
    private InvitationCodeHandler $invitationCodeHandler;
    private Flat $flat;
    private EntityManager $entityManager;

    public function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = static::getContainer();

        $this->invitationCodeHandler = $container->get(InvitationCodeHandler::class);
        $userRepository = $container->get(LandlordRepository::class);
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $landlord = $userRepository->findOneByEmail('test_env_user@test.pl');
        $invitationCode = new Ulid();

        $this->flat = new Flat();
        $this->flat->setArea(30);
        $this->flat->setNumberOfRooms(2);
        $this->flat->setRent(3000);
        $this->flat->setAddress('Testowa 12, 34-123 Testowo');
        $this->flat->setFloor(3);
        $this->flat->setMaxFloor(4);
        $this->flat->setLandlord($landlord);
        $this->flat->setInvitationCode($invitationCode);

        $this->entityManager->persist($this->flat);
        $this->entityManager->flush();
    }

    public function testGetInvitationCode() : void
    {
        $this->assertMatchesRegularExpression('/[0-9A-Z]{26}/', $this->flat->getInvitationCode());
    }

    public function testGetExpirationDate() : void
    {
        $expirationDate = $this->invitationCodeHandler->getExpirationDate($this->flat->getInvitationCode());
        $expected = new DateTime('now');
        $expected = $expected->modify('+26 hours');

        $interval = $expected->diff($expirationDate)->f;
        $this->assertLessThan(1, $interval);
    }

    public function testGetEncodedInvitationCode() : void
    {
        $encodedCode = $this->invitationCodeHandler->getEncodedInvitationCode($this->flat->getInvitationCode());
        $this->assertMatchesRegularExpression('/[0-9a-zA-Z]{22}/', $encodedCode);
    }

    public function testIsInvitationCodeValidForCorrectData() : void
    {
        $currentDate = new DateTime('now');
        $code = $this->flat->getInvitationCode();

        $this->assertTrue($this->invitationCodeHandler->isInvitationCodeValid($code, $currentDate));
    }

    public function testIsInvitationCodeValidForPlus12Hours() : void
    {
        $currentDate = new DateTime('now');
        $currentDate = $currentDate->modify('+12 hours');
        $code = $this->flat->getInvitationCode();

        $this->assertTrue($this->invitationCodeHandler->isInvitationCodeValid($code, $currentDate));
    }

    public function testIsInvitationCodeValidForPlus24Hours() : void
    {
        $currentDate = new DateTime('now');
        $currentDate = $currentDate->modify('+24 hours');
        $code = $this->flat->getInvitationCode();

        $this->assertTrue($this->invitationCodeHandler->isInvitationCodeValid($code, $currentDate));
    }

    public function testIsInvitationCodeValidForPlus26Hours() : void
    {
        $currentDate = new DateTime('now');
        $currentDate = $currentDate->modify('+26 hours');
        // there is a ~1-millisecond difference between those times, so function should return false
        // dd($currentDate->format('Y-m-d H:i:s.v') . ' | ' . $this->flat->getInvitationCode()->getDateTime()->format('Y-m-d H:i:s.v'));
        $code = $this->flat->getInvitationCode();

        $this->assertFalse($this->invitationCodeHandler->isInvitationCodeValid($code, $currentDate));
    }

    public function testIsInvitationCodeValidForPlus27Hours() : void
    {
        $currentDate = new DateTime('now');
        $currentDate = $currentDate->modify('+27 hours');
        $code = $this->flat->getInvitationCode();

        $this->assertFalse($this->invitationCodeHandler->isInvitationCodeValid($code, $currentDate));
    }

    public function testIsInvitationCodeValidForMinus1Hour() : void
    {
        $currentDate = new DateTime('now');
        $currentDate = $currentDate->modify('-1 hours');
        $code = $this->flat->getInvitationCode();

        $this->assertFalse($this->invitationCodeHandler->isInvitationCodeValid($code, $currentDate));
    }
}