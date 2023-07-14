<?php

namespace App\Controller;

use App\Form\EditProfileFormType;
use App\Form\InvitationCodeFormType;
use App\Repository\FlatRepository;
use App\Repository\TenantRepository;
use App\Repository\UserRepository;
use App\Service\FilesUploader;
use App\Service\InvitationCodeHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class ProfileController extends AbstractController
{
    #[Route('/panel/profile/{id}', name: 'app_profile')]
    public function profile(UserRepository $userRepository, int $id, Request $request, InvitationCodeHandler $invitationCodeHandler, SessionInterface $session): Response
    {
        $user = $userRepository->findOneBy(['id' => $id]);
        $form = $this->createForm(InvitationCodeFormType::class, $user, [
            'session' => $session,
        ]);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $invitationCode = $form->get('code')->getData();
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
            'invitation_code_form' => $form->createView()
        ]);
    }
    #[Route('/panel/profile/{id}/edit', name: 'app_profile_edit')]
    public function profileEdit(UserRepository $userRepository, int $id, SessionInterface $session, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, FilesUploader $filesUploader): Response
    {
        $user = $userRepository->findOneBy(['id' => $id]);
        $form = $this->createForm(EditProfileFormType::class, $user, [
            'session' => $session,
        ]);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $user = $form->getData();

            $profilePicture = $form->get('image')->getData();
            if ($profilePicture)
            {
                $originalFileName = pathinfo($profilePicture->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFileName);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $profilePicture->guessExtension();

                // This function returns a path in format /uploads/profile_pictures/user{id}
                $path = $filesUploader->createTempDir($this->getParameter('profile_pictures'), $id);
                try {
                    $profilePicture->move(
                        $path,
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error occurred when uploading profile picture');
                }

                $user->setImage($newFilename);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_profile', ['id' => $user->getId()]);
        }

        return $this->render('panel/profile/profile_edit.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }

}