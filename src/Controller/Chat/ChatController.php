<?php

namespace App\Controller\Chat;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    #[Route('/panel/chat', name: 'app_chat')]
    public function chat(): Response
    {
        return $this->render('panel/chat/chat.html.twig', [
        ]);
    }
}
