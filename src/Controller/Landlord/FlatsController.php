<?php

namespace App\Controller\Landlord;

use App\Entity\Flat;
use App\Form\NewFlatTypeFlow;
use App\Repository\FlatRepository;
use App\Service\InvitationCodeHandler;
use App\Service\NewFlatFormHandler;
use App\Service\FilesUploader;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\Date;


class FlatsController extends AbstractController
{
    private NewFlatTypeFlow $newFlatTypeFlow;
    public function __construct(NewFlatTypeFlow $newFlatTypeFlow)
    {
        $this->newFlatTypeFlow = $newFlatTypeFlow;
    }

    #[Route('/panel/flats', name: 'app_flats')]
    public function flats(FlatRepository $flatRepository): Response
    {
        $flats = $flatRepository->findBy(['landlord' => $this->getUser()->getId()]);

        return $this->render('panel/flats/flats.html.twig', [
            'flats' => $flats,
        ]);
    }

    #[Route('/panel/flats/delete/{id}', name: 'app_flats_delete')]
    public function deleteFlat(FlatRepository $flatRepository, int $id, EntityManagerInterface $entityManager): Response
    {
        $flat = $flatRepository->findOneBy(['id' => $id]);
        $entityManager->remove($flat);
        $entityManager->flush();

        return $this->redirectToRoute('app_flats');
    }

    #[Route('/panel/flats/edit/{id}', name: 'app_flats_edit')]
    public function editFlat(NewFlatFormHandler $newFlatFormHandler, int $id, FlatRepository $flatRepository, FilesUploader $filesUploader, ParameterBagInterface $parameterBag): Response
    {
        $flat = $flatRepository->findOneBy(['id' => $id]);
        $landlord = $this->getUser();

        $flatCopy = clone $flat;
        $userId = $this->getUser()->getId();

        $flow = $this->newFlatTypeFlow;
        $flow->bind($flatCopy);

        return $newFlatFormHandler->handleFlatForm($flat, $flow, $landlord, $userId, true, $flatCopy);
    }

    #[Route('/panel/flats/new', name: 'app_flats_new')]
    public function newFlat(NewFlatFormHandler $newFlatFormHandler): Response
    {
        $flat = new Flat();
        $landlord = $this->getUser();

        $flow = $this->newFlatTypeFlow;
        $flow->bind($flat);

        $userId = $this->getUser()->getId();
        return $newFlatFormHandler->handleFlatForm($flat, $flow, $landlord, $userId);
    }

    #[Route('/panel/flats/{id}', name: 'app_flats_view')]
    public function viewFlat(FlatRepository $flatRepository, int $id, InvitationCodeHandler $invitationCodeHandler): Response
    {
        $flat = $flatRepository->findOneBy(['id' => $id]);
        $tenants = $flat->getTenants();

        $invitationCode = [
            'code' => $invitationCodeHandler->getInvitationCode($flat),
        ];

        if ($invitationCode['code']) {
            $invitationCode['expiration_date'] = $invitationCodeHandler->getExpirationDate($invitationCode['code'])->format('d-m-Y H:i:s');
            $currentDate = new \DateTime('now');
            $invitationCode['is_code_valid'] = $invitationCodeHandler->isInvitationCodeValid($invitationCode['code'], $currentDate);
            $invitationCode['invitation_code_encoded'] = $invitationCodeHandler->getEncodedInvitationCode($invitationCode['code']);
        }

        return $this->render('panel/flats/flat.html.twig', [
            'flat' => $flat,
            'invitation_code' => $invitationCode,
            'tenants' => $tenants,
        ]);
    }
}
