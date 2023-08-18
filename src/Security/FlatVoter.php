<?php

namespace App\Security;

use App\Entity\Flat;
use App\Entity\User\User;
use App\Repository\FlatRepository;
use App\Repository\LandlordRepository;
use App\Repository\TenantRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FlatVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';
    const ADD_UTILITY_METER_READING = 'add';

    private FlatRepository $flatRepository;
    private TenantRepository $tenantRepository;
    private LandlordRepository $landlordRepository;
    public function __construct(FlatRepository $flatRepository, TenantRepository $tenantRepository, LandlordRepository $landlordRepository)
    {
        $this->flatRepository = $flatRepository;
        $this->tenantRepository = $tenantRepository;
        $this->landlordRepository = $landlordRepository;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::ADD_UTILITY_METER_READING])) {
            return false;
        }

        if (!$subject instanceof Flat) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        /** @var Flat $flat */
        $flat = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($flat, $user),
            self::EDIT => $this->canEdit($flat, $user),
            self::DELETE => $this->canDelete($flat, $user),
            self::ADD_UTILITY_METER_READING => $this->canAddUtilityMeterReading($flat, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canView(Flat $flat, User $loggedInUser): bool
    {
        if ($this->canEdit($flat, $loggedInUser))
        {
            $allowed = true;
        } else
        {
            // if user is tenant and have permissions to add utility meters, then he can definitely view this flat
            $allowed = $this->canAddUtilityMeterReading($flat, $loggedInUser);
        }

        return $allowed;
    }

    private function canEdit(Flat $flat, User $loggedInUser): bool
    {
        $allowed = false;
        if (in_array('ROLE_LANDLORD', $loggedInUser->getRoles()))
        {
            $landlord = $this->landlordRepository->findOneBy(['id' => $loggedInUser->getId()]);
            if (!is_null($landlord))
            {
                $allowed = $landlord->getFlats()->contains($flat);
            }
        }
        return $allowed;
    }

    private function canDelete(Flat $flat, User $loggedInUser): bool
    {
        // if a user can edit own flat, then they can also delete it
        return $this->canEdit($flat, $loggedInUser);
    }

    private function canAddUtilityMeterReading(Flat $flat, User $loggedInUser): bool
    {
        $allowed = false;

        if (in_array('ROLE_TENANT', $loggedInUser->getRoles()))
        {
            $tenant = $this->tenantRepository->findOneBy(['id' => $loggedInUser->getId()]);
            if (!is_null($tenant))
            {
                $allowed = $flat->getTenants()->contains($tenant);
            }
        }

        return $allowed;
    }

}