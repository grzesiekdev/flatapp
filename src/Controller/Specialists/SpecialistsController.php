<?php

namespace App\Controller\Specialists;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class SpecialistsController extends AbstractController
{
    #[Route('/panel/specialists', name: 'app_specialists')]
    public function specialists(): Response
    {
        return $this->render('panel/specialists/specialists.html.twig', [

        ]);
    }
}
