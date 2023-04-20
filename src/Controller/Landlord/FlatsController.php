<?php

namespace App\Controller\Landlord;

use App\Entity\Flat;
use App\Form\NewFlatTypeFlow;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FlatsController extends AbstractController
{
    private NewFlatTypeFlow $newFlatTypeFlow;
    public function __construct(NewFlatTypeFlow $newFlatTypeFlow)
    {
        $this->newFlatTypeFlow = $newFlatTypeFlow;
    }

    #[Route('/panel/flats', name: 'app_flats')]
    public function flats(): Response
    {
        return $this->render('panel/flats.html.twig', [
            'controller_name' => 'PanelController',
        ]);
    }

    #[Route('/panel/flats/new', name: 'app_flats_new')]
    public function newFlat(EntityManagerInterface $entityManager): Response
    {
        $flat = new Flat();
        $landlord = $this->getUser();

        $flow = $this->newFlatTypeFlow;
        $flow->bind($flat);

        $form = $flow->createForm();
        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);

            if ($flow->nextStep()) {
                $form = $flow->createForm();

            } else {
                $flat->setLandlord($landlord);
                $entityManager->persist($flat);
                $entityManager->flush();
                $flow->reset();

                return $this->redirectToRoute('app_flats');
            }
        }
        return $this->render('panel/new-flat.html.twig', [
            'form' => $form->createView(),
            'flow' => $flow,
        ]);
    }
}
