<?php

namespace App\Controller\Landlord;

use App\Repository\FlatRepository;
use App\Service\FilesUploader;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Type;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

class FlatsHelperController extends AbstractController
{
    #[Route('/panel/flats/delete-picture/{id}', name: 'app_flats_delete_picture')]
    public function index(FilesUploader $fileUploader, Request $request, KernelInterface $kernel, int $id, FlatRepository $flatRepository, EntityManagerInterface $entityManager): Response
    {
        $fileName = $request->request->get('file_name');
        $fileToRemove = preg_replace('/\/(.*)\//', '', $fileName);

        if ($id != 0) {
            $flat = $flatRepository->findOneBy(['id' => $id]);

            if (str_contains($fileName, '/pictures_for_tenant/')) {
                $flat->setPicturesForTenant(array_diff($flat->getPicturesForTenant(), [$fileToRemove]));
            } else {
                $flat->setPictures(array_diff($flat->getPictures(), [$fileToRemove]));
            }
            $entityManager->persist($flat);
            $entityManager->flush();
        }

        $fileName = $kernel->getProjectDir() . '/public' . $fileName;

        $statusCode = $fileUploader->deleteFile($fileName);
        $response = new Response();
        $response->setStatusCode($statusCode);

        return $response;
    }

    #[Route('/panel/flats/create-invitation-code/{id}', name: 'app_flats_create_invitation_code')]
    public function createInvitationCode(FlatRepository $flatRepository, int $id, EntityManagerInterface $entityManager): Response
    {
        $flat = $flatRepository->findOneBy(['id' => $id]);
        $invitationCode = new Ulid();

        $flat->setInvitationCode($invitationCode);
        $entityManager->persist($flat);
        $entityManager->flush();

        return $this->redirectToRoute('app_flats_view', ['id' => $id]);
    }
}
