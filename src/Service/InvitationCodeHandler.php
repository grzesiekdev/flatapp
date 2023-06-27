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

        return $currentDate > $initialDate && $currentDate < $expirationDate;
    }
}
