<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\User\User;
use App\Repository\LandlordRepository;
use App\Repository\TenantRepository;
use App\Repository\UserRepository;
use DateTime;
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
        if (!empty($messages))
        {
            usort($messages, function ($a, $b) {
                return $a->getDate() <=> $b->getDate();
            });
        }
        return $messages;
    }

    function getConversations(int $receiverId)
    {
        $loggedInUserEmail = $this->security->getUser()->getUserIdentifier();
        $sender = $this->userRepository->findOneBy(['email' => $loggedInUserEmail]);
        $receiver = $this->userRepository->findOneBy(['id' => $receiverId]);

        $related = $this->getRelatedUsersOfSender();
        if (in_array($receiver, $related)) {
            $conversations = array();

            $userMessages = $this->getUserMessages($sender, $receiver);
            $sortedMessages = $this->sortMessagesByDate($userMessages);
            $sortedMessages = array_reverse($sortedMessages);
            $conversations[$receiver->getId()] = $sortedMessages;

            return $conversations;
        } else {
            return 500;
        }
    }

    function timeElapsedString($datetime) : string {
        $now = new DateTime;
        $now = $now->modify('+ 2 hours');
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        if ($diff->y > 0) {
            return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        }
        if ($diff->m > 0) {
            return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        }
        if ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        }
        if ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        }
        if ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        }
        return 'just now';
    }
}