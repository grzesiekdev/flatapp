<?php

namespace App\Controller\Flat;

use App\Repository\FlatRepository;
use App\Repository\TenantRepository;
use App\Repository\UserRepository;
use App\Service\FilesUploader;
use Doctrine\ORM\EntityManagerInterface;
use \Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Ulid;

class FlatsHelperController extends AbstractController
{
    #[Route('/panel/flats/{id}/delete-picture', name: 'app_flats_delete_picture')]
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

    #[Route('/panel/flats/{id}/generate-invitation-code', name: 'app_flats_generate_invitation_code')]
    public function createInvitationCode(FlatRepository $flatRepository, int $id, EntityManagerInterface $entityManager): Response
    {
        $flat = $flatRepository->findOneBy(['id' => $id]);
        $invitationCode = new Ulid();

        $flat->setInvitationCode($invitationCode);
        $entityManager->persist($flat);
        $entityManager->flush();

        return $this->redirectToRoute('app_flats_view', ['id' => $id]);
    }

    #[Route('/panel/flats/{id}/delete-invitation-code', name: 'app_flats_delete_invitation_code')]
    public function deleteInvitationCode(FlatRepository $flatRepository, int $id, EntityManagerInterface $entityManager): Response
    {
        $flat = $flatRepository->findOneBy(['id' => $id]);
        $flat->setInvitationCode(null);

        $entityManager->persist($flat);
        $entityManager->flush();

        return $this->redirectToRoute('app_flats_view', ['id' => $id]);
    }

    #[Route('/panel/flats/{flatId}/remove-tenant/{tenantId}', name: 'app_flats_remove_tenant')]
    public function removeTenant(FlatRepository $flatRepository, TenantRepository $tenantRepository, int $flatId, int $tenantId, EntityManagerInterface $entityManager, Security $security, UserRepository $userRepository): Response
    {
        $flat = $flatRepository->findOneBy(['id' => $flatId]);
        $tenant = $tenantRepository->findOneBy(['id' => $tenantId]);
        $currentUser = $security->getUser();

        if (is_null($flat) || !in_array($tenant, $flat->getTenants()->toArray())) {
            throw $this->createAccessDeniedException('This user is not assigned to this flat');
        }

        if ($currentUser->getRoles()[0] === 'ROLE_LANDLORD') {
            if ($tenant === $currentUser) {
                throw $this->createAccessDeniedException('You cannot remove yourself from this flat');
            }
        } else {
            if ($currentUser !== $tenant)  {
                throw $this->createAccessDeniedException('You cannot remove this tenant from this flat');
            }
        }

        if (is_null($tenant)) {
            throw $this->createAccessDeniedException('This user doesnt exist');
        } else {
            $flat->removeTenant($tenant);
            $tenant->setFlatId(null);

            $entityManager->persist($flat);
            $entityManager->persist($tenant);
            $entityManager->flush();
        }

        if ($currentUser->getRoles()[0] == 'ROLE_TENANT') return $this->redirectToRoute('app_panel');
        else return $this->redirectToRoute('app_flats_view', ['id' => $flatId]);
    }

}
