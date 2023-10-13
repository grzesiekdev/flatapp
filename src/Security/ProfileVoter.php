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
    const EDIT = 'edit';
    const DELETE = 'delete';

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
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
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
            self::EDIT => $this->canEdit($profile, $user),
            self::DELETE => $this->canDelete($profile, $user),
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
                $flats = $this->flatRepository->findBy(['landlord' => $user]);
                foreach ($flats as $flat)
                {
                    $allowed = $flat->getTenants()->contains($loggedInUser) && $flat->getLandlord() === $user;
                    if ($allowed)
                    {
                        break;
                    }
                }
            }
        } elseif (in_array('ROLE_LANDLORD', $loggedInUser->getRoles()))
        {
            $tenant = $this->tenantRepository->findOneBy(['id' => $user->getId()]);
            if (!is_null($tenant))
            {
                $flats = $this->flatRepository->findBy(['landlord' => $loggedInUser]);
                foreach ($flats as $flat)
                {
                    $allowed = $flat->getTenants()->contains($user);
                    if ($allowed)
                    {
                        break;
                    }
                }
            }
        }
        return $allowed;
    }

    private function canEdit(User $user, User $loggedInUser): bool
    {
        return $loggedInUser === $user;
    }

    private function canDelete(User $user, User $loggedInUser): bool
    {
        return $loggedInUser === $user;
    }

}