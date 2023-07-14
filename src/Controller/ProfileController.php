<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;


class ProfileController extends AbstractController
{
    #[Route('/panel/profile/{id}', name: 'app_profile')]
    public function profile(UserRepository $userRepository, int $id): Response
    {
        $user = $userRepository->findOneBy(['id' => $id]);

        return $this->render('panel/profile/profile.html.twig', [
            'user' => $user,
        ]);
    }
}