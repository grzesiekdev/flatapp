<?php

namespace App\Controller\Landlord;

use App\Entity\Flat;
use App\Form\NewFlatTypeFlow;
use App\Repository\FlatRepository;
use App\Service\NewFlatFormHandler;
use App\Service\FilesUploader;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\SluggerInterface;


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
    public function editFlat(NewFlatFormHandler $newFlatFormHandler, int $id, FlatRepository $flatRepository): Response
    {
        $flat = $flatRepository->findOneBy(['id' => $id]);
        $landlord = $this->getUser();

        $flow = $this->newFlatTypeFlow;
        $flow->bind($flat);

        $userId = $this->getUser()->getId();
        return $newFlatFormHandler->handleFlatForm($flat, $flow, $landlord, $userId);
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
    public function viewFlat(FlatRepository $flatRepository, int $id): Response
    {
        $flat = $flatRepository->findOneBy(['id' => $id]);

        return $this->render('panel/flats/flat.html.twig', [
            'flat' => $flat,
        ]);
    }
}
