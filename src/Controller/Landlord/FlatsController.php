<?php

namespace App\Controller\Landlord;

use App\Entity\Flat;
use App\Form\NewFlatTypeFlow;
use App\Repository\FlatRepository;
use App\Service\PicturesUploader;
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
    private PicturesUploader $picturesUploader;
    private RequestStack $requestStack;
    public function __construct(NewFlatTypeFlow $newFlatTypeFlow, PicturesUploader $picturesUploader, RequestStack $session)
    {
        $this->newFlatTypeFlow = $newFlatTypeFlow;
        $this->picturesUploader = $picturesUploader;
        $this->requestStack = $session;
    }

    #[Route('/panel/flats', name: 'app_flats')]
    public function flats(FlatRepository $flatRepository): Response
    {
        $flats = $flatRepository->findBy(['landlord' => $this->getUser()->getId()]);

        return $this->render('panel/flats.html.twig', [
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

    #[Route('/panel/flats/new', name: 'app_flats_new')]
    public function newFlat(EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $flat = new Flat();
        $landlord = $this->getUser();

        $flow = $this->newFlatTypeFlow;
        $flow->bind($flat);

        $form = $flow->createForm();
        $formData = $form->getData();

        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);

            if ($flow->getCurrentStep() == 3) {
                $pictures = $formData->getPictures();
                $picturesForTenant = $formData->getPicturesForTenant();

                $tempPicturesDirectory = $this->getParameter('temp_pictures');
                $tempPicturesForTenantDirectory = $this->getParameter('temp_pictures_for_tenant');

                $specificPicturesTempDirectory = $this->picturesUploader->createTempDir($tempPicturesDirectory, $this->getUser()->getId());
                $this->requestStack->getSession()->set('specificPicturesTempDirectory', $specificPicturesTempDirectory);
                foreach ($pictures as $picture) {
                    $this->picturesUploader->upload($picture, $specificPicturesTempDirectory);
                }

                $specificPicturesForTenantTempDirectory = $this->picturesUploader->createTempDir($tempPicturesForTenantDirectory, $this->getUser()->getId());
                $this->requestStack->getSession()->set('specificPicturesForTenantTempDirectory', $specificPicturesForTenantTempDirectory);
                foreach ($picturesForTenant as $picture) {
                    $this->picturesUploader->upload($picture, $specificPicturesForTenantTempDirectory);
                }
            } elseif ($flow->getCurrentStep() == 4) {
                $agreement = $form->get('rentAgreement')->getData();
                if ($agreement) {
                    $originalFileName = pathinfo($agreement->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFileName);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$agreement->guessExtension();

                    $agreementDirectory = $this->picturesUploader->createTempDir($this->getParameter('agreements'), $this->getUser()->getId());
                    try {
                        $agreement->move(
                            $agreementDirectory,
                            $newFilename
                        );
                    } catch (FileException $e) {
                        dd($e);
                    }
                    $this->requestStack->getSession()->set('agreementDirectory', $agreementDirectory);
                    $this->requestStack->getSession()->set('agreementNewName', $newFilename);
                } else {
                    $this->requestStack->getSession()->remove('agreementDirectory');
                    $this->requestStack->getSession()->remove('agreementNewName');
                }
            }
            if ($flow->nextStep()) {
                $form = $flow->createForm();
            } else {
                $pictures = $this->picturesUploader->getPictures($this->requestStack->getSession()->get('specificPicturesTempDirectory'));
                $picturesForTenant = $this->picturesUploader->getPictures($this->requestStack->getSession()->get('specificPicturesForTenantTempDirectory'));
                if($this->requestStack->getSession()->get('agreementDirectory')) {
                    $agreement = $this->picturesUploader->getAgreement($this->requestStack->getSession()->get('agreementDirectory'), $this->requestStack->getSession()->get('agreementNewName'));
                    $flat->setRentAgreement($agreement);
                }

                $flat->setLandlord($landlord);
                $flat->setPictures($pictures);
                $flat->setPicturesForTenant($picturesForTenant);
                $entityManager->persist($flat);
                $entityManager->flush();
                $flow->reset();

                return $this->redirectToRoute('app_flats');
            }
        }
        return $this->render('panel/new-flat.html.twig', [
            'form' => $form->createView(),
            'flow' => $flow,
            'form_data' => $formData,
            'pictures' => $this->picturesUploader->getTempPictures($this->requestStack->getSession()->get('specificPicturesTempDirectory')),
            'pictures_for_tenant' => $this->picturesUploader->getTempPictures($this->requestStack->getSession()->get('specificPicturesForTenantTempDirectory')),
            'rent_agreement' => $this->requestStack->getSession()->get('agreementNewName'),
        ]);
    }
}
