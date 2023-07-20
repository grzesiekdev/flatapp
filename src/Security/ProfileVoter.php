<?php

namespace App\Security;

use App\Entity\User\User;
use App\Repository\FlatRepository;
use App\Repository\LandlordRepository;
use App\Repository\TenantRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProfileVoter extends Voter
{
    const VIEW = 'view';

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
        if (!in_array($attribute, [self::VIEW])) {
            return false;
        }

        if (!$subject instanceof User) {
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
        $profile = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($profile, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canView(User $user, User $loggedInUser): bool
    {
        $allowed = $loggedInUser === $user;

        if (in_array('ROLE_TENANT', $loggedInUser->getRoles()))
        {
            $landlord = $this->landlordRepository->findOneBy(['id' => $user->getId()]);
            if (!is_null($landlord)) {
                $flat = $this->flatRepository->findOneBy(['landlord' => $landlord]);
                if (!is_null($flat))
                {
                    $allowed = $flat->getLandlord() === $user;
                }
            }
        } elseif (in_array('ROLE_LANDLORD', $loggedInUser->getRoles()))
        {
            $tenant = $this->tenantRepository->findOneBy(['id' => $user->getId()]);
            if (!is_null($tenant))
            {
                $flat = $tenant->getFlatId();
                if (!is_null($flat))
                {
                    $allowed = $flat->getTenants()->contains($user);
                }
            }
        }
        return $allowed;
    }

}