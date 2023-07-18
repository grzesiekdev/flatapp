<?php

namespace App\Controller;

use App\Entity\User\Type\Landlord;
use App\Form\EditProfileFormType;
use App\Form\InvitationCodeFormType;
use App\Repository\FlatRepository;
use App\Repository\LandlordRepository;
use App\Repository\TenantRepository;
use App\Repository\UserRepository;
use App\Service\FilesUploader;
use App\Service\InvitationCodeHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class ProfileController extends AbstractController
{
    #[Route('/panel/profile/{id}', name: 'app_profile')]
    public function profile(UserRepository $userRepository, int $id, Request $request, InvitationCodeHandler $invitationCodeHandler, SessionInterface $session, Security $security, FlatRepository $flatRepository, TenantRepository $tenantRepository, LandlordRepository $landlordRepository): Response
    {
        $user = $userRepository->findOneBy(['id' => $id]);
        $form = $this->createForm(InvitationCodeFormType::class, $user, [
            'session' => $session,
        ]);

        if (is_null($user))
        {
            throw new \Exception('User doesn\'t exist!');
        }

        $loggedInUser = $security->getUser();
        $allowed = false;

        if (in_array('ROLE_TENANT', $loggedInUser->getRoles()))
        {
            $allowed = $loggedInUser === $user;
            $landlord = $landlordRepository->findOneBy(['id' => $user->getId()]);
            if (!is_null($landlord)) {
                $flat = $flatRepository->findOneBy(['landlord' => $landlord]);
                if (!is_null($flat))
                {
                    $allowed = $flat->getLandlord() === $user;
                }
            }
        } elseif (in_array('ROLE_LANDLORD', $loggedInUser->getRoles()))
        {
            $allowed = $loggedInUser === $user;
            $tenant = $tenantRepository->findOneBy(['id' => $user->getId()]);
            if (!is_null($tenant))
            {
                $flat = $tenant->getFlatId();
                if (!is_null($flat))
                {
                    $allowed = $flat->getTenants()->contains($user);
                }
            }
        }

        if (!$allowed) {
            throw new AccessDeniedException('Access denied.');
        }

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $invitationCode = $form->get('code')->getData();
            if ($invitationCode) {
                $invitationCode = Ulid::fromBase32($invitationCode); // generating real Ulid from base32
                $currentDate = new \DateTime('now');
                if ($invitationCodeHandler->isInvitationCodeValid($invitationCode, $currentDate)) {
                    $invitationCodeHandler->setInvitationCode($id, $invitationCode, $currentDate);
                    $this->addFlash('success', 'Flat added successfully');
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
    public function profileEdit(UserRepository $userRepository, int $id, SessionInterface $session, Request $request, EntityManagerInterface $entityManager, FilesUploader $filesUploader): Response
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
                $path = $this->getParameter('profile_pictures');
                if ($user->getImage() && $user->getImage() !== 'default-profile-picture.png')
                {
                    $filesUploader->deleteFile($path . '/' . $user->getImage());
                }
                $newFilename = $filesUploader->upload($profilePicture, $path);
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

    #[Route('/panel/profile/{id}/delete-picture', name: 'app_profile_delete_picture')]
    public function profileDeletePicture(UserRepository $userRepository, int $id, EntityManagerInterface $entityManager, FilesUploader $filesUploader): Response
    {
        $user = $userRepository->findOneBy(['id' => $id]);
        $response = new Response();
        if ($user->getImage() && $user->getImage() !== 'default-profile-picture.png')
        {
            $path = $this->getParameter('profile_pictures');
            $filesUploader->deleteFile($path . '/' . $user->getImage());
            $user->setImage('default-profile-picture.png');

            $entityManager->persist($user);
            $entityManager->flush();

            $response->setStatusCode(Response::HTTP_OK);
        } else {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        return $response;
    }

}