<?php

namespace App\Controller\Authentication;

use App\Entity\User\Type\Landlord;
use App\Entity\User\Type\Tenant;
use App\Entity\User\User;
use App\Form\RegistrationFormType;
use App\Repository\FlatRepository;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\FilesUploader;
use App\Service\InvitationCodeHandler;
use App\Utils\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;
    private Security $security;

    public function __construct(EmailVerifier $emailVerifier, Security $security)
    {
        $this->emailVerifier = $emailVerifier;
        $this->security = $security;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, FlatRepository $flatRepository, InvitationCodeHandler $invitationCodeHandler, SessionInterface $session, FilesUploader $filesUploader): Response
    {
        if ($this->security->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(RegistrationFormType::class, new User(), [
            'session' => $session,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userType = $form->get('roles')->getData();
            $user = match (current($userType)) {
                UserRole::LANDLORD => new Landlord(),
                UserRole::TENANT => new Tenant(),
                UserRole::DEFAULT => new User(),
            };

            $user->setName($form->get('name')->getData())
            ->setEmail($form->get('email')->getData())
            ->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            )
            ->setDateOfBirth($form->get('dateOfBirth')->getData())
            ->setAddress($form->get('address')->getData())
            ->setPhone($form->get('phone')->getData())
            ->setRoles($userType);

            $profilePicture = $form->get('image')->getData();
            if ($profilePicture)
            {
                $path = $this->getParameter('profile_pictures');
                $newFilename = $filesUploader->upload($profilePicture, $path);
                $user->setImage($newFilename);
            } else {
                $user->setImage('default-profile-picture.png');
            }

            if ($userType[0] == 'ROLE_TENANT') {
                $invitationCode = $form->get('code')->getData();
                if ($invitationCode) {
                    $invitationCode = Ulid::fromBase32($invitationCode); // generating real Ulid from base32
                    $currentDate = new \DateTime('now');
                    if ($invitationCodeHandler->isInvitationCodeValid($invitationCode, $currentDate)) {
                        $flat = $flatRepository->findOneBy(['invitationCode' => $invitationCode]);
                        $user->setFlatId($flat);
                        $user->setTenantSince($currentDate);
                        $flat->addTenant($user);
                        $entityManager->persist($flat);
                    } else {
                        $this->addFlash('error', 'Invalid invitation code.');
                        return $this->redirectToRoute('app_register');
                    }
                } else {
                    $this->addFlash('error', 'Please provide an invitation code.');
                    return $this->redirectToRoute('app_register');
                }
            }

            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('flatapp@bednarski.xyz', 'flatapp Mail Bot'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );
            // do anything else you need here, like send an email

            return $this->redirectToRoute('app_panel');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository): Response
    {
        $id = $request->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_register');
    }
}
