<?php

namespace App\Controller\Flat;

use App\Entity\UtilityMeterReading;
use App\Form\AdditionalPhotosFormType;
use App\Form\UtilityMetersReadingType;
use App\Repository\FlatRepository;
use App\Repository\UtilityMeterReadingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UtilityMetersController extends AbstractController
{

    #[Route('/panel/flats/{id}/utility-meters', name: 'app_flats_utility_meters')]
    public function utilityMeters(FlatRepository $flatRepository, int $id): Response
    {
        $flat = $flatRepository->findOneBy(['id' => $id]);
        $utilityMeters = $flat->getUtilityMeterReadings();

        return $this->render('panel/flats/utility_meters/utility_meters.html.twig', [
            'flat' => $flat,
            'utility_meters' => $utilityMeters,
        ]);
    }

    #[Route('/panel/flats/{id}/utility-meters/add-new', name: 'app_flats_utility_meters_new')]
    public function addNewUtilityMetersReading(FlatRepository $flatRepository, int $id, Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        $flat = $flatRepository->findOneBy(['id' => $id]);
        $utilityMeterReading = new UtilityMeterReading();

        $user = $security->getUser();
        $form = $this->createForm(UtilityMetersReadingType::class, $utilityMeterReading, ['userRole' => $user->getRoles()]);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $water = ['amount' => $form->get('water_amount')->getData(), 'cost' => $form->get('water_cost')->getData()];
            $gas = ['amount' => $form->get('gas_amount')->getData(), 'cost' => $form->get('gas_cost')->getData()];
            $electricity = ['amount' => $form->get('electricity_amount')->getData(), 'cost' => $form->get('electricity_cost')->getData()];
            $date = new \DateTime('now');

            $utilityMeterReading->setWater($water);
            $utilityMeterReading->setGas($gas);
            $utilityMeterReading->setElectricity($electricity);
            $utilityMeterReading->setDate($date);
            $utilityMeterReading->setFlat($flat);

            $flat->addUtilityMeterReading($utilityMeterReading);

            $entityManager->persist($utilityMeterReading);
            $entityManager->persist($flat);
            $entityManager->flush();

            return $this->redirectToRoute('app_flats_utility_meters', ['id' => $id]);
        }

        return $this->render('panel/flats/utility_meters/utility_meters_new.html.twig', [
            'flat' => $flat,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/panel/flats/{id}/utility-meters/{readingId}', name: 'app_flats_utility_meters_edit')]
    public function editUtilityMetersReading(FlatRepository $flatRepository, int $id, int $readingId, Request $request, EntityManagerInterface $entityManager, Security $security, UtilityMeterReadingRepository $utilityMeterReadingRepository): Response
    {
        $flat = $flatRepository->findOneBy(['id' => $id]);
        $utilityMeterReading = $utilityMeterReadingRepository->findOneBy(['id' => $readingId]);

        $user = $security->getUser();

        // getting amounts of utilities
        $water = $utilityMeterReading->getWater()['amount'];
        $gas = $utilityMeterReading->getGas()['amount'];
        $electricity = $utilityMeterReading->getElectricity()['amount'];

        $form = $this->createForm(UtilityMetersReadingType::class, $utilityMeterReading, [
            'userRole' => $user->getRoles(),
            'water' => $water,
            'gas' => $gas,
            'electricity' => $electricity
        ]);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $water = ['amount' => $water, 'cost' => $form->get('water_cost')->getData()];
            $gas = ['amount' => $gas, 'cost' => $form->get('gas_cost')->getData()];
            $electricity = ['amount' => $electricity, 'cost' => $form->get('electricity_cost')->getData()];
            $date = new \DateTime('now');

            $utilityMeterReading->setWater($water);
            $utilityMeterReading->setGas($gas);
            $utilityMeterReading->setElectricity($electricity);
            $utilityMeterReading->setDate($date);

            $entityManager->persist($utilityMeterReading);
            $entityManager->flush();

            return $this->redirectToRoute('app_flats_utility_meters', ['id' => $id]);
        }

        return $this->render('panel/flats/utility_meters/utility_meters_new.html.twig', [
            'flat' => $flat,
            'form' => $form->createView(),
        ]);
    }

}
