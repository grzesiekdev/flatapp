<?php

namespace App\Controller;

use App\Entity\User\Tenant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PanelController extends AbstractController
{
    #[Route('/panel', name: 'app_panel')]
    public function index(): Response
    {
        return $this->render('panel/index.html.twig', [
            'controller_name' => 'PanelController',
        ]);
    }
}
