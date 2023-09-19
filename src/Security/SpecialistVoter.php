<?php

namespace App\Security;

use App\Entity\Specialist;
use App\Entity\User\User;
use App\Repository\LandlordRepository;
use App\Repository\TenantRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SpecialistVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

    private TenantRepository $tenantRepository;
    private LandlordRepository $landlordRepository;
    public function __construct(TenantRepository $tenantRepository, LandlordRepository $landlordRepository)
    {
        $this->tenantRepository = $tenantRepository;
        $this->landlordRepository = $landlordRepository;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {

        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
            return false;
        }

        if (!$subject instanceof Specialist) {
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

        /** @var User $user */
        $specialist = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($specialist, $user),
            self::EDIT => $this->canEdit($specialist, $user),
            self::DELETE => $this->canDelete($specialist, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canView(Specialist $specialist, User $loggedInUser): bool
    {
        if (in_array('ROLE_TENANT', $loggedInUser->getRoles()))
        {
            $tenant = $this->tenantRepository->findOneBy(['email' => $loggedInUser->getUserIdentifier()]);
            $flat = $tenant->getFlatId();
            if (in_array($specialist, $flat->getSpecialists()->toArray()))
            {
                return true;
            }
        }
        // If user is landlord, then check if he can delete specialist
        return $this->canDelete($specialist, $loggedInUser);
    }

    private function canEdit(Specialist $specialist, User $loggedInUser): bool
    {
        // If user can delete specialist, he can also edit him
        return $this->canDelete($specialist, $loggedInUser);
    }

    private function canDelete(Specialist $specialist, User $loggedInUser): bool
    {
        if (in_array('ROLE_LANDLORD', $loggedInUser->getRoles()))
        {
            $landlord = $this->landlordRepository->findOneBy(['email' => $loggedInUser->getUserIdentifier()]);
            $flats = $landlord->getFlats();

            foreach ($flats as $flat) {
                if (in_array($specialist, $flat->getSpecialists()->toArray())) {
                    return true;
                }
            }
        }
        return false;
    }

}