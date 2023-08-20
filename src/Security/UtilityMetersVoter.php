<?php

namespace App\Security;

use App\Entity\Flat;
use App\Entity\User\User;
use App\Entity\UtilityMeterReading;
use App\Repository\FlatRepository;
use App\Repository\LandlordRepository;
use App\Repository\TenantRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UtilityMetersVoter extends Voter
{
    const EDIT = 'edit';
    const DELETE = 'delete';
    const DELETE_INVOICE = 'deleteInvoice';

    private FlatRepository $flatRepository;
    private TenantRepository $tenantRepository;
    private LandlordRepository $landlordRepository;
    private ProfileVoter $profileVoter;

    public function __construct(FlatRepository $flatRepository, TenantRepository $tenantRepository, LandlordRepository $landlordRepository, ProfileVoter $profileVoter)
    {
        $this->flatRepository = $flatRepository;
        $this->tenantRepository = $tenantRepository;
        $this->landlordRepository = $landlordRepository;
        $this->profileVoter = $profileVoter;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::EDIT, self::DELETE, self::DELETE_INVOICE])) {
            return false;
        }

        if (gettype($subject) == 'array') {
            if (!$subject[0] instanceof UtilityMeterReading || !$subject[1] instanceof Flat) {
                return false;
            }
        } else {
            if (!$subject instanceof UtilityMeterReading) {
                return false;
            }
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $loggedInUser = $token->getUser();

        if (!$loggedInUser instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        $utilityMetersReading = $subject[0];
        $flat = $subject[1];
        $invoice = '';
        if (count($subject) == 3)
        {
            // if invoice is being deleted, then subject has third value
            $invoice = $subject[2];
        }

        return match($attribute) {
            self::EDIT => $this->canEdit($utilityMetersReading, $flat, $loggedInUser),
            self::DELETE => $this->canDelete($utilityMetersReading, $flat, $loggedInUser),
            self::DELETE_INVOICE => $this->canDeleteInvoice($utilityMetersReading, $flat, $loggedInUser, $invoice),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    public function canEdit(UtilityMeterReading $utilityMeterReading, Flat $flat, User $user): bool
    {
        $allowed = false;
        // if we can delete this reading AND it wasn't edited yet, then allow
        if ($this->canDelete($utilityMeterReading, $flat, $user) && !$utilityMeterReading->isWasEdited()) {
            $allowed = true;
        }

        return $allowed;
    }

    public function canDelete(UtilityMeterReading $utilityMeterReading, Flat $flat, User $user): bool
    {
        $allowed = false;

        $utilityFlat = $utilityMeterReading->getFlat();

        if ($utilityFlat === $flat && in_array('ROLE_LANDLORD', $user->getRoles()))
        {
            $landlord = $this->landlordRepository->findOneBy(['id' => $user->getId()]);
            // if we are an owner of the flat, and reading wasn't edit yet, then allow
            if (!is_null($landlord) && $landlord->getFlats()->contains($flat))
            {
                $allowed = true;
            }
        }

        return $allowed;
    }

    public function canDeleteInvoice(UtilityMeterReading $utilityMeterReading, Flat $flat, User $user, $invoice): bool
    {
        $allowed = false;
        if (array_search($invoice, $utilityMeterReading->getInvoices()) !== false) {

            $allowed = $this->canDelete($utilityMeterReading, $flat, $user);
        }
        return $allowed;
    }

}