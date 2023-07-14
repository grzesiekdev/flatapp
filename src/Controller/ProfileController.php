<?php

namespace App\Controller;

use App\Repository\FlatRepository;
use App\Repository\TenantRepository;
use App\Repository\UserRepository;
use App\Service\InvitationCodeHandler;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Ulid;


class ProfileController extends AbstractController
{
    #[Route('/panel/profile/{id}', name: 'app_profile')]
    public function profile(UserRepository $userRepository, int $id, Request $request, InvitationCodeHandler $invitationCodeHandler, FlatRepository $flatRepository, TenantRepository $tenantRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $userRepository->findOneBy(['id' => $id]);

        $invitationCodeForm = $this->createFormBuilder($user)
            ->add('code', TextType::class, ['mapped' => false,])
            ->add('save', SubmitType::class, ['label' => 'Enter code'])
            ->getForm();
        $invitationCodeForm->handleRequest($request);
        if($invitationCodeForm->isSubmitted() && $invitationCodeForm->isValid())
        {
            $invitationCode = $invitationCodeForm->get('code')->getData();
            if ($invitationCode) {
                $invitationCode = Ulid::fromBase32($invitationCode); // generating real Ulid from base32
                $currentDate = new \DateTime('now');
                if ($invitationCodeHandler->isInvitationCodeValid($invitationCode, $currentDate)) {
                    $tenant = $tenantRepository->findOneBy(['id' => $id]);
                    $flat = $flatRepository->findOneBy(['invitationCode' => $invitationCode]);
                    $tenant->setFlatId($flat);
                    $tenant->setTenantSince($currentDate);
                    $flat->addTenant($tenant);
                    $entityManager->persist($flat);
                    $entityManager->persist($tenant);
                    $entityManager->flush();
                } else {
                    $this->addFlash('error', 'Invalid invitation code.');
                    return $this->redirectToRoute('app_profile');
                }
            } else {
                $this->addFlash('error', 'Please provide an invitation code.');
                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('panel/profile/profile.html.twig', [
            'user' => $user,
            'invitation_code_form' => $invitationCodeForm->createView()
        ]);
    }
}