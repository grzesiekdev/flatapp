<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\User\User;
use App\Repository\LandlordRepository;
use App\Repository\TenantRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerBuilder;


class ChatHelper
{
    public LandlordRepository $landlordRepository;
    public TenantRepository $tenantRepository;
    public UserRepository $userRepository;
    public EntityManagerInterface $entityManager;
    public Security $security;
    public ContainerBuilder $container;

    public function __construct(LandlordRepository $landlordRepository, TenantRepository $tenantRepository, UserRepository $userRepository, EntityManagerInterface $entityManager, Security $security)
    {
        $this->tenantRepository = $tenantRepository;
        $this->landlordRepository = $landlordRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function getRelatedUsersOfSender() : array
    {
        $user = $this->security->getUser();
        $userEmail = $user->getUserIdentifier();
        $related = array();

        if (in_array('ROLE_LANDLORD', $user->getRoles())) {
            $user = $this->landlordRepository->findOneBy(['email' => $userEmail]);

            $flats = $user->getFlats()->toArray();
            foreach ($flats as $flat)
            {
                foreach ($flat->getTenants()->toArray() as $tenant)
                {
                    $related[] = $tenant;
                }
            }
        }
        elseif (in_array('ROLE_TENANT', $user->getRoles()))
        {
            $user = $this->tenantRepository->findOneBy(['email' => $userEmail]);
            $flat = $user->getFlatId();
            if (!is_null($flat))
            {
                $related[] = $flat->getLandlord();
                foreach ($flat->getTenants()->toArray() as $tenant)
                {
                    if ($user !== $tenant) {
                        $related[] = $tenant;
                    }
                }
            }
        }

        return $related;
    }

    function getUserMessages($sender, $receiver) {
        $userSent = $sender->getSentMessages()->filter(function ($message) use ($receiver) {
            return $message->getReceiver() == $receiver;
        })->toArray();

        $userReceived = $sender->getReceivedMessages()->filter(function ($message) use ($receiver) {
            return $message->getSender() == $receiver;
        })->toArray();

        return array_merge($userSent, $userReceived);
    }

    function sortMessagesByDate($messages) {
        usort($messages, function ($a, $b) {
            return $a->getDate() <=> $b->getDate();
        });

        return $messages;
    }

}