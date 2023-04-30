<?php

namespace App\Service;


use App\Entity\Flat;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Twig\Environment;
use Twig\Token;

class NewFlatFormHandler
{
    private FilesUploader $filesUploader;
    private RequestStack $requestStack;
    private EntityManagerInterface $entityManger;
    private Environment $twig;
    private ParameterBagInterface $parameterBag;
    private RouterInterface $router;
    private string $tempPicturesDirectory;
    private string $tempPicturesForTenantDirectory;
    private string $agreementDirectory;
    private int $userId;

    public function __construct(FilesUploader $picturesUploader, RequestStack $session, EntityManagerInterface $entityManger, Environment $twig, ParameterBagInterface $parameterBag, RouterInterface $router) {
        $this->filesUploader = $picturesUploader;
        $this->requestStack = $session;
        $this->entityManger = $entityManger;
        $this->twig = $twig;
        $this->parameterBag = $parameterBag;
        $this->router = $router;
        $this->tempPicturesDirectory = $this->parameterBag->get('temp_pictures');
        $this->tempPicturesForTenantDirectory = $this->parameterBag->get('temp_pictures_for_tenant');
        $this->agreementDirectory = $this->parameterBag->get('agreements');
    }

    public function getSessionVariable(string $name): string|null
    {
        return $this->requestStack->getSession()->get($name);
    }

    public function setSessionVariable(string $name, string $value): void
    {
        $this->requestStack->getSession()->set($name, $value);
    }


    public function uploadPictures(array $pictures, string $sessionVarName): void
    {
        $kindOfPictures = '';
        if ($sessionVarName == 'specificPicturesTempDirectory') {
            $kindOfPictures = $this->tempPicturesDirectory;
        } else if($sessionVarName == 'specificPicturesForTenantTempDirectory') {
            $kindOfPictures = $this->tempPicturesForTenantDirectory;
        }

        $specificPicturesTempDirectory = $this->filesUploader->createTempDir($kindOfPictures, $this->userId);
        $this->setSessionVariable($sessionVarName, $specificPicturesTempDirectory);

        foreach ($pictures as $picture) {
            $this->filesUploader->upload($picture, $specificPicturesTempDirectory);
        }
    }

    public function handleAgreement($agreement): void
    {
        if ($agreement) {
            $agreementDirectory = $this->filesUploader->createTempDir($this->agreementDirectory, $this->userId);
            $newFilename = $this->filesUploader->upload($agreement, $agreementDirectory);

            $this->setSessionVariable('agreementDirectory', $agreementDirectory);
            $this->setSessionVariable('agreementNewName', $newFilename);
        } else {
            $this->requestStack->getSession()->remove('agreementDirectory');
            $this->requestStack->getSession()->remove('agreementNewName');
        }
    }

    public function handleFlatForm(Flat $flat, $flow, $landlord, $userId): RedirectResponse|Response
    {
        $form = $flow->createForm();
        $formData = $form->getData();
        $this->userId = $userId;

        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);

            if ($flow->getCurrentStep() == 3) {
                $pictures = $formData->getPictures();
                $picturesForTenant = $formData->getPicturesForTenant();

                $this->uploadPictures($pictures, 'specificPicturesTempDirectory');
                $this->uploadPictures($picturesForTenant, 'specificPicturesForTenantTempDirectory');

            } elseif ($flow->getCurrentStep() == 4) {
                $agreement = $form->get('rentAgreement')->getData();
                $this->handleAgreement($agreement);
            }
            if ($flow->nextStep()) {
                $form = $flow->createForm();
            } else {
                $pictures = $this->filesUploader->getPictures($this->getSessionVariable('specificPicturesTempDirectory'));
                $picturesForTenant = $this->filesUploader->getPictures($this->getSessionVariable('specificPicturesForTenantTempDirectory'));

                if($this->getSessionVariable('agreementDirectory')) {
                    $agreement = $this->filesUploader->getAgreement($this->getSessionVariable('agreementDirectory'), $this->getSessionVariable('agreementNewName'));
                    $flat->setRentAgreement($agreement);
                }

                $flat->setLandlord($landlord);
                $flat->setPictures($pictures);
                $flat->setPicturesForTenant($picturesForTenant);

                $this->entityManger->persist($flat);
                $this->entityManger->flush();

                $flow->reset();
                return new RedirectResponse($this->router->generate('app_flats'));

            }
        }
        return new Response($this->twig->render('panel/flats/new-flat.html.twig', [
            'form' => $form->createView(),
            'flow' => $flow,
            'form_data' => $formData,
            'pictures' => $this->filesUploader->getTempPictures($this->getSessionVariable('specificPicturesTempDirectory')),
            'pictures_for_tenant' => $this->filesUploader->getTempPictures($this->getSessionVariable('specificPicturesForTenantTempDirectory')),
            'rent_agreement' => $this->getSessionVariable('agreementNewName'),
        ]));
    }


}