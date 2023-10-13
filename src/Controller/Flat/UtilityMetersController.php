<?php

namespace App\Controller\Flat;

use App\Entity\Flat;
use App\Entity\UtilityMeterReading;
use App\Form\Flat\UtilityMetersReadingType;
use App\Repository\FlatRepository;
use App\Repository\UtilityMeterReadingRepository;
use App\Security\UtilityMetersVoter;
use App\Service\FilesUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UtilityMetersController extends AbstractController
{

    #[Route('/panel/flats/{id}/utility-meters', name: 'app_flats_utility_meters')]
    #[IsGranted('view', 'flat', 'You don\'t have permissions to view this flat', 403)]
    public function utilityMeters(FlatRepository $flatRepository, int $id, Flat $flat = null): Response
    {
        $flat = $flatRepository->findOneBy(['id' => $id]);
        $utilityMeters = $flat->getUtilityMeterReadings();

        return $this->render('panel/flats/utility_meters/utility_meters.html.twig', [
            'flat' => $flat,
            'utility_meters' => $utilityMeters,
        ]);
    }

    #[Route('/panel/flats/{id}/utility-meters/add-new', name: 'app_flats_utility_meters_new')]
    #[IsGranted('add', 'flat', 'You don\'t have permissions to add utility meters reading to this flat', 403)]
    public function addNewUtilityMetersReading(FlatRepository $flatRepository, int $id, Request $request, EntityManagerInterface $entityManager, Security $security, Flat $flat = null ): Response
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

            $this->addFlash('success', 'Utility meter reading added successfully');
            return $this->redirectToRoute('app_flats_utility_meters', ['id' => $id]);
        }

        return $this->render('panel/flats/utility_meters/utility_meters_new.html.twig', [
            'flat' => $flat,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/panel/flats/{id}/utility-meters/{readingId}', name: 'app_flats_utility_meters_edit')]
    public function editUtilityMetersReading(FlatRepository $flatRepository, int $id, int $readingId, Request $request, EntityManagerInterface $entityManager, Security $security, UtilityMeterReadingRepository $utilityMeterReadingRepository, FilesUploader $filesUploader, ParameterBagInterface $parameterBag): Response
    {
        $flat = $flatRepository->findOneBy(['id' => $id]);
        $utilityMeterReading = $utilityMeterReadingRepository->findOneBy(['id' => $readingId]);

        if (!$this->isGranted(UtilityMetersVoter::EDIT, [$utilityMeterReading, $flat])) {
            throw $this->createAccessDeniedException('You do not have permission to edit this utility meter reading');
        }

        $user = $security->getUser();

        // getting amounts of utilities
        $water = $utilityMeterReading->getWater()['amount'];
        $gas = $utilityMeterReading->getGas()['amount'];
        $electricity = $utilityMeterReading->getElectricity()['amount'];
        $date = $utilityMeterReading->getDate();

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

            $invoices = $form->get('invoices')->getData();
            $invoicesNames = [];

            if ($invoices) {
                $invoicesPath = $parameterBag->get('invoices') . '/flat' . $id . '/' . $date->format('d-m-Y');
                $filesUploader->createDir($invoicesPath);
                foreach ($invoices as $invoice) {
                    $invoicesNames[] = $filesUploader->upload($invoice, $invoicesPath);
                }
            }

            $utilityMeterReading->setWater($water);
            $utilityMeterReading->setGas($gas);
            $utilityMeterReading->setElectricity($electricity);
            $utilityMeterReading->setDate($date);
            $utilityMeterReading->setWasEdited(true);
            $utilityMeterReading->setInvoices($invoicesNames);

            $entityManager->persist($utilityMeterReading);
            $entityManager->flush();

            $this->addFlash('success', 'Utility meter reading edited successfully');
            return $this->redirectToRoute('app_flats_utility_meters', ['id' => $id]);
        }

        return $this->render('panel/flats/utility_meters/utility_meters_new.html.twig', [
            'flat' => $flat,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/panel/flats/{id}/utility-meters/{readingId}/delete', name: 'app_flats_utility_meters_delete')]
    public function deleteUtilityMetersReading(FlatRepository $flatRepository, int $id, int $readingId, EntityManagerInterface $entityManager, UtilityMeterReadingRepository $utilityMeterReadingRepository, ParameterBagInterface $parameterBag, FilesUploader $filesUploader): Response
    {
        $flat = $flatRepository->findOneBy(['id' => $id]);
        $utilityMeterReading = $utilityMeterReadingRepository->findOneBy(['id' => $readingId]);

        if (!$this->isGranted(UtilityMetersVoter::DELETE, [$utilityMeterReading, $flat])) {
            throw $this->createAccessDeniedException('You do not have permission to delete this utility meter reading');
        }

        $invoices = $utilityMeterReading->getInvoices();
        foreach ($invoices as $invoice)
        {
            $invoicePath = $parameterBag->get('invoices') . '/flat' . $id . '/' . $utilityMeterReading->getDate()->format('d-m-Y') . '/' . $invoice;
            $filesUploader->deleteFile($invoicePath);
        }

        $flat->removeUtilityMeterReading($utilityMeterReading);
        $entityManager->remove($utilityMeterReading);
        $entityManager->persist($flat);
        $entityManager->flush();

        $this->addFlash('success', 'Utility meter reading deleted successfully');
        return $this->redirectToRoute('app_flats_utility_meters', ['id' => $id]);
    }

    #[Route('/panel/flats/{id}/utility-meters/{readingId}/delete-invoice/{invoice}', name: 'app_flats_utility_meters_delete_invoice')]
    public function deleteInvoice(int $id, int $readingId, string $invoice, EntityManagerInterface $entityManager, UtilityMeterReadingRepository $utilityMeterReadingRepository, FlatRepository $flatRepository, FilesUploader $filesUploader, ParameterBagInterface $parameterBag): Response
    {
        $flat = $flatRepository->findOneBy(['id' => $id]);
        $utilityMeterReading = $utilityMeterReadingRepository->findOneBy(['id' => $readingId]);

        if (!$this->isGranted(UtilityMetersVoter::DELETE_INVOICE, [$utilityMeterReading, $flat, $invoice])) {
            throw $this->createAccessDeniedException('You do not have permission to delete this Invoice');
        }

        $invoicePath = $parameterBag->get('invoices') . '/flat' . $id . '/' . $utilityMeterReading->getDate()->format('d-m-Y') . '/' . $invoice;
        $deleteStatus = $filesUploader->deleteFile($invoicePath);
        if ($deleteStatus == 200)
        {
            $invoices = $utilityMeterReading->getInvoices();
            $invoiceToDelete = array_search($invoice, $invoices);
            unset($invoices[$invoiceToDelete]);
            $utilityMeterReading->setInvoices($invoices);

            $entityManager->persist($utilityMeterReading);
            $entityManager->flush();

            $this->addFlash('success', 'Utility meter invoice deleted successfully');
        }

        return $this->redirectToRoute('app_flats_utility_meters', ['id' => $id]);
    }

}
