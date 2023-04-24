<?php

namespace App\Controller\Landlord;

use App\Entity\Flat;
use App\Form\NewFlatTypeFlow;
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
    public function flats(): Response
    {
        return $this->render('panel/flats.html.twig', [
            'controller_name' => 'PanelController',
        ]);
    }

    #[Route('/panel/flats/new', name: 'app_flats_new')]
    public function newFlat(EntityManagerInterface $entityManager, UserInterface $user): Response
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
            }
            if ($flow->nextStep()) {
                $form = $flow->createForm();
            } else {
                $pictures = $this->picturesUploader->getPictures($this->requestStack->getSession()->get('specificPicturesTempDirectory'));
                $picturesForTenant = $this->picturesUploader->getPictures($this->requestStack->getSession()->get('specificPicturesForTenantTempDirectory'));

                $this->requestStack->getSession()->remove('specificPicturesTempDirectory');
                $this->requestStack->getSession()->remove('specificPicturesForTenantTempDirectory');

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
        ]);
    }
}
