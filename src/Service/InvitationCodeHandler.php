<?php

namespace App\Service;


use App\Entity\Flat;
use App\Repository\FlatRepository;
use App\Repository\TenantRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

class InvitationCodeHandler
{
    private FlatRepository $flatRepository;
    private TenantRepository $tenantRepository;
    private EntityManagerInterface $entityManager;
    public function __construct(FlatRepository $flatRepository, TenantRepository $tenantRepository, EntityManagerInterface $entityManager)
    {
        $this->flatRepository = $flatRepository;
        $this->tenantRepository = $tenantRepository;
        $this->entityManager = $entityManager;
    }
    public function getInvitationCode(Flat $flat): Ulid | null
    {
        return $flat->getInvitationCode();
    }

    public function getExpirationDate(Ulid $invitationCode): \DateTimeImmutable
    {
        // app is currently being developed for the Polish market, so I've used UTC + 2 hours to check if code is valid
        return $invitationCode->getDateTime()->modify('+26 hours');
    }

    public function getEncodedInvitationCode(Ulid $invitationCode): string
    {
        return $invitationCode->toBase58();
    }

    public function isInvitationCodeValid(Ulid $invitationCode, DateTime $currentDate): bool
    {
        $initialDate = $invitationCode->getDateTime();
        $expirationDate = $this->getExpirationDate($invitationCode);

        // check if there is flat with this code
        $flat = $this->flatRepository->findOneBy(['invitationCode' => $invitationCode]);

        return ($currentDate > $initialDate && $currentDate < $expirationDate) && !is_null($flat);
    }

    public function setInvitationCode(int $id, string $invitationCode, DateTime $currentDate) : void
    {
        $tenant = $this->tenantRepository->findOneBy(['id' => $id]);
        $flat = $this->flatRepository->findOneBy(['invitationCode' => $invitationCode]);
        $tenant->setFlatId($flat);
        $tenant->setTenantSince($currentDate);
        $flat->addTenant($tenant);

        $this->entityManager->persist($flat);
        $this->entityManager->persist($tenant);
        $this->entityManager->flush();
    }
}
