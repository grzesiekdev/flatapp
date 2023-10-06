<?php

namespace App\Controller\Chat;
use App\Entity\Message;
use App\Repository\LandlordRepository;
use App\Repository\TenantRepository;
use App\Repository\UserRepository;
use App\Service\ChatHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    public LandlordRepository $landlordRepository;
    public TenantRepository $tenantRepository;
    public UserRepository $userRepository;
    public EntityManagerInterface $entityManager;
    public Security $security;

    public function __construct(LandlordRepository $landlordRepository, TenantRepository $tenantRepository, UserRepository $userRepository, EntityManagerInterface $entityManager, Security $security)
    {
        $this->tenantRepository = $tenantRepository;
        $this->landlordRepository = $landlordRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    #[Route('/panel/chat', name: 'app_chat')]
    public function chat(ChatHelper $chatHelper): Response
    {
        $related = $chatHelper->getRelatedUsersOfSender();

        return $this->render('panel/chat/chat.html.twig', [
            'related' => $related
        ]);
    }

    #[Route('/panel/chat/save-into-db', name: 'app_chat_save_into_db')]
    public function saveMessageIntoDatabase(Request $request, ChatHelper $chatHelper, Security $security) : JsonResponse
    {
        $loggedInUser = $security->getUser();
        $jsonContent = $request->getContent();

        $data = json_decode($jsonContent, true);
        $receiverId = $data['receiver'];
        $senderId = $data['sender'];
        $content = $data['message'];
        $date = new \DateTime('now');
        $date = $date->modify('+ 2 hours');

        $receiver = $this->userRepository->findOneBy(['id' => $receiverId]);
        $sender = $this->userRepository->findOneBy(['id' => $senderId]);

        $related = $chatHelper->getRelatedUsersOfSender();
        if (in_array($receiver, $related) && $sender === $loggedInUser)
        {
            $message = new Message();
            $message->setMessage($content);
            $message->setDate($date);
            $message->setReceiver($receiver);
            $message->setSender($sender);

            $sender->addSentMessage($message);
            $receiver->addReceivedMessage($message);

            $this->entityManager->persist($message);
            $this->entityManager->persist($receiver);
            $this->entityManager->persist($sender);
            $this->entityManager->flush();

            $date = $date->format('d-m-Y H:i:s');
            return new JsonResponse(['status' => 'success', 'date' => $date], 200);
        }
        else {
            return new JsonResponse(['status' => 'error', 'message' => 'Failed to save the message.'], 403);
        }
    }

    #[Route('/panel/chat/get-conversation/{receiverId}', name: 'app_chat_get_conversation')]
    public function getConversation(int $receiverId, ChatHelper $chatHelper): JsonResponse
    {
        $conversations = $chatHelper->getConversations($receiverId);
        if($conversations !== 500) {
            foreach ($conversations[$receiverId] as $message) {
                $data = [
                    'message' => $message->getMessage(),
                    'date' => $message->getDate()->format('d-m-Y H:i:s'),
                    'senderName' => $message->getSender()->getName(),
                    'senderId' => $message->getSender()->getId(),
                    'profilePicture' => $message->getSender()->getImage()
                ];
                $responseData[] = $data;
            }
            return new JsonResponse(json_encode($responseData), 200, [], true);
        }
        else {
            return new JsonResponse('failure', 500);
        }
    }

    #[Route('/panel/chat/get-last-message/{receiverId}', name: 'app_chat_get_last_messages')]
    public function getLastMessages(int $receiverId, ChatHelper $chatHelper) : JsonResponse
    {
        $conversations = $chatHelper->getConversations($receiverId);
        $conversation = $conversations[$receiverId];

        $lastMessage = array_shift($conversation);

        if (!is_null($lastMessage))
        {
            $lastMessage = $lastMessage->getMessage();
        }

        return new JsonResponse(['lastMessage' => $lastMessage], 200);
    }
}
