<?php

namespace App\Service;


use App\Entity\Flat;
use DateTime;
use Symfony\Component\Uid\Ulid;

class InvitationCodeHandler
{
    public function getInvitationCode(Flat $flat): Ulid | null
    {
        return $flat->getInvitationCode();
    }

    public function getExpirationDate(Ulid $invitationCode): \DateTimeImmutable
    {
        return $invitationCode->getDateTime()->modify('+24 hours');
    }

    public function getEncodedInvitationCode(Ulid $invitationCode): string
    {
        return $invitationCode->toBase58();
    }

    public function isInvitationCodeValid(Ulid $invitationCode): bool
    {
        $currentDate = new DateTime();
        $initialDate = $invitationCode->getDateTime();
        $expirationDate = $this->getExpirationDate($invitationCode);

        return $currentDate > $initialDate && $currentDate < $expirationDate;
    }
}
