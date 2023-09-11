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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;


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
            foreach ($flats as $flat) {
                $specialist->addFlat($flat);
                $flat->addSpecialist($specialist);

                $entityManager->persist($flat);
            }

            $gmb = $form->get('gmb')->getData();

            $dom = new \DOMDocument();
            $dom->loadHTML($gmb);
            $iframe = $dom->getElementsByTagName('iframe');
            if ($iframe->length > 0) {
                $gmb = $iframe->item(0)->getAttribute('src');
                $specialist->setGmb($gmb);
            }

            $entityManager->persist($specialist);
            $entityManager->flush();

            return $this->redirectToRoute('app_specialists_view', ['id' => $specialist->getId()]);
        }

        return $this->render('panel/specialists/new-specialist.html.twig', [
            'specialist' => $specialist,
            'form' => $form->createView()
        ]);
    }

    #[Route('/panel/specialists/{id}', name: 'app_specialists_view')]
    public function viewSpecialist(int $id, SpecialistRepository $specialistRepository): Response
    {
        $specialist = $specialistRepository->findOneBy(['id' => $id]);

        return $this->render('panel/specialists/view-specialist.html.twig', [
            'specialist' => $specialist,
        ]);
    }
}
