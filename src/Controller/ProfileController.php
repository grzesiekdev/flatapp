<?php

namespace App\Controller;

use App\Form\InvitationCodeFormType;
use App\Repository\FlatRepository;
use App\Repository\TenantRepository;
use App\Repository\UserRepository;
use App\Service\InvitationCodeHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class ProfileController extends AbstractController
{
    #[Route('/panel/profile/{id}', name: 'app_profile')]
    public function profile(UserRepository $userRepository, int $id, Request $request, InvitationCodeHandler $invitationCodeHandler, FlatRepository $flatRepository, TenantRepository $tenantRepository, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        $user = $userRepository->findOneBy(['id' => $id]);

        $invitationCodeForm = $this->createForm(InvitationCodeFormType::class, $user, [
            'session' => $session,
        ]);

        $invitationCodeForm->handleRequest($request);
        if($invitationCodeForm->isSubmitted() && $invitationCodeForm->isValid())
        {
            $invitationCode = $invitationCodeForm->get('code')->getData();
            if ($invitationCode) {
                $invitationCode = Ulid::fromBase32($invitationCode); // generating real Ulid from base32
                $currentDate = new \DateTime('now');
                if ($invitationCodeHandler->isInvitationCodeValid($invitationCode, $currentDate)) {
                    $invitationCodeHandler->setInvitationCode($id, $invitationCode, $currentDate);
                } else {
                    $this->addFlash('error', 'Invalid invitation code.');
                    return $this->redirectToRoute('app_profile', ['id' => $user->getId()]);
                }
            } else {
                $this->addFlash('error', 'Please provide an invitation code.');
                return $this->redirectToRoute('app_profile', ['id' => $user->getId()]);
            }
        }

        return $this->render('panel/profile/profile.html.twig', [
            'user' => $user,
            'invitation_code_form' => $invitationCodeForm->createView()
        ]);
    }
}