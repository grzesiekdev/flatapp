<?php

namespace App\Controller\Specialists;

use App\Entity\Specialist;
use App\Form\Specialists\NewSpecialistFormType;
use App\Repository\LandlordRepository;
use App\Repository\SpecialistRepository;
use App\Repository\TenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


class SpecialistsController extends AbstractController
{
    #[Route('/panel/specialists', name: 'app_specialists')]
    public function specialists(Security $security, LandlordRepository $landlordRepository, TenantRepository $tenantRepository): Response
    {
        $flats = array();

        $user = $security->getUser();
        if (in_array('ROLE_LANDLORD', $user->getRoles()))
        {
            $user = $landlordRepository->findOneBy(['email' => $user->getUserIdentifier()]);
            $flats = $user->getFlats();
        } else if (in_array('ROLE_TENANT', $user->getRoles()))
        {
            $user = $tenantRepository->findOneBy(['email' => $user->getUserIdentifier()]);
            $flats[] = $user->getFlatId();
        }

        return $this->render('panel/specialists/specialists.html.twig', [
            'flats' => $flats
        ]);
    }

    #[Route('/panel/specialists/delete/{id}', name: 'app_specialists_delete')]
    #[IsGranted('delete', 'specialist', 'You don\'t have permissions to delete this specialist', 403)]
    public function deleteSpecialist(int $id, SpecialistRepository $specialistRepository, EntityManagerInterface $entityManager, Specialist $specialist): Response
    {
        $specialist = $specialistRepository->findOneBy(['id' => $id]);
        $flats = $specialist->getFlats()->toArray();

        foreach ($flats as $flat)
        {
            $flat->removeSpecialist($specialist);
            $entityManager->persist($flat);
        }

        $entityManager->remove($specialist);
        $entityManager->flush();

        $this->addFlash('success', 'Specialist deleted successfully');
        return $this->redirectToRoute('app_specialists');
    }

    #[Route('/panel/specialists/edit/{id}', name: 'app_specialists_edit')]
    #[IsGranted('edit', 'specialist', 'You don\'t have permissions to edit this specialist', 403)]
    public function editSpecialist(int $id, SpecialistRepository $specialistRepository, SessionInterface $session, Request $request, EntityManagerInterface $entityManager, Specialist $specialist): Response
    {
        $specialist = $specialistRepository->findOneBy(['id' => $id]);
        $form = $this->createForm(NewSpecialistFormType::class, $specialist, [
            'session' => $session,
            'specialist_flats' => $specialist->getFlats()->toArray()
        ]);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $specialist = $form->getData();

            $flats = $form->get('flats')->getData();

            if ($flats === []) {
                $this->addFlash('error', 'You have to choose at least one flat');
                return $this->redirectToRoute('app_specialists_edit', ['id' => $id]);
            }

            $flatsToRemove = array_udiff(
                $specialist->getFlats()->toArray(),
                $flats,
                function ($a, $b) {
                    return $a->getId() - $b->getId();
                }
            );

            // removing flats with which specialist is no longer related
            foreach ($flatsToRemove as $flat) {
                $specialist->removeFlat($flat);
                $flat->removeSpecialist($specialist);
            }

            $this->handleSpecialists($flats, $specialist, $entityManager, $form);

            $this->addFlash('success', 'Specialist edited successfully');
            return $this->redirectToRoute('app_specialists_view', ['id' => $specialist->getId()]);
        }

        return $this->render('panel/specialists/new-specialist.html.twig', [
            'specialist' => $specialist,
            'form' => $form->createView(),
            'is_edit' => true
        ]);
    }

    #[Route('/panel/specialists/new', name: 'app_specialists_new')]
    public function newSpecialist(SessionInterface $session, Request $request, EntityManagerInterface $entityManager): Response
    {
        $specialist = new Specialist();
        $form = $this->createForm(NewSpecialistFormType::class, $specialist, [
            'session' => $session,
        ]);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $specialist = $form->getData();

            $flats = $form->get('flats')->getData();
            if ($flats === []) {
                $this->addFlash('error', 'You have to choose at least one flat');
                return $this->redirectToRoute('app_specialists_new');
            }

            $this->handleSpecialists($flats, $specialist, $entityManager, $form);

            $this->addFlash('success', 'Specialist added successfully');
            return $this->redirectToRoute('app_specialists_view', ['id' => $specialist->getId()]);
        }

        return $this->render('panel/specialists/new-specialist.html.twig', [
            'specialist' => $specialist,
            'form' => $form->createView(),
            'is_edit' => false
        ]);
    }

    #[Route('/panel/specialists/{id}', name: 'app_specialists_view')]
    #[IsGranted('view', 'specialist', 'You don\'t have permissions to view this specialist', 403)]
    public function viewSpecialist(int $id, SpecialistRepository $specialistRepository, Specialist $specialist): Response
    {
        $specialist = $specialistRepository->findOneBy(['id' => $id]);

        return $this->render('panel/specialists/view-specialist.html.twig', [
            'specialist' => $specialist,
        ]);
    }

    public function handleSpecialists(array $flats, Specialist $specialist, EntityManagerInterface $entityManager, FormInterface $form): void
    {
        foreach ($flats as $flat) {
            $specialist->addFlat($flat);
            $flat->addSpecialist($specialist);

            $entityManager->persist($flat);
        }

        $gmb = $form->get('gmb')->getData();
        if ($gmb)
        {
            $dom = new \DOMDocument();
            $dom->loadHTML($gmb);
            $iframe = $dom->getElementsByTagName('iframe');
            if ($iframe->length > 0) {
                $gmb = $iframe->item(0)->getAttribute('src');
                $specialist->setGmb($gmb);
            } else
            {
                $specialist->setGmb('');
            }
        }

        $entityManager->persist($specialist);
        $entityManager->flush();
    }
}
