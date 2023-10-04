<?php

namespace App\Controller\Chat;
use App\Repository\LandlordRepository;
use App\Repository\TenantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    #[Route('/panel/chat', name: 'app_chat')]
    public function chat(LandlordRepository $landlordRepository, TenantRepository $tenantRepository, Security $security): Response
    {
        $user = $security->getUser();
        $userEmail = $user->getUserIdentifier();
        $related = array();

        if (in_array('ROLE_LANDLORD', $user->getRoles())) {
            $user = $landlordRepository->findOneBy(['email' => $userEmail]);

            $flats = $user->getFlats()->toArray();
            foreach ($flats as $flat)
            {
                foreach ($flat->getTenants()->toArray() as $tenant)
                {
                    $related[] = $tenant;
                }
            }
        }
        elseif (in_array('ROLE_TENANT', $user->getRoles()))
        {
            $user = $tenantRepository->findOneBy(['email' => $userEmail]);
            $flat = $user->getFlatId();

            $related[] = $flat->getLandlord();
            foreach ($flat->getTenants()->toArray() as $tenant)
            {
                if ($user !== $tenant) {
                    $related[] = $tenant;
                }
            }
        }

        return $this->render('panel/chat/chat.html.twig', [
            'related' => $related
        ]);
    }

    #[Route('/panel/chat/get-data', name: 'app_chat_authenticate')]
    public function chatGetData(LandlordRepository $landlordRepository, TenantRepository $tenantRepository, Security $security): JsonResponse
    {
        $user = $security->getUser();
        $userEmail = $user->getUserIdentifier();
        $related = array();
        if (in_array('ROLE_LANDLORD', $user->getRoles())) {
            $user = $landlordRepository->findOneBy(['email' => $userEmail]);

            $flats = $user->getFlats()->toArray();
            foreach ($flats as $flat)
            {
                foreach ($flat->getTenants()->toArray() as $tenant)
                {
                    $related[] = $tenant->getId();
                }
            }
            $status = true;
        }
        elseif (in_array('ROLE_TENANT', $user->getRoles()))
        {
            $user = $tenantRepository->findOneBy(['email' => $userEmail]);
            $flat = $user->getFlatId();

            $related[] = $flat->getLandlord()->getId();
            foreach ($flat->getTenants()->toArray() as $tenant)
            {
                if ($user !== $tenant) {
                    $related[] = $tenant->getId();
                }
            }
            $status = true;
        }
        else {
            $status = false;
        }

        $responseData = [
            'userId' => $user->getId(),
            'related' => $related,
            'status' => $status
        ];

        return new JsonResponse($responseData);
    }
}
