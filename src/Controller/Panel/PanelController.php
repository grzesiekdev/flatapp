<?php

namespace App\Controller\Panel;

use App\Entity\Task;
use App\Form\User\TasksFormType;
use App\Repository\LandlordRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class PanelController extends AbstractController
{
    #[Route('/panel', name: 'app_panel')]
    public function index(Security $security, UserRepository $userRepository, SessionInterface $session, EntityManagerInterface $entityManager, LandlordRepository $landlordRepository): Response
    {
        $user = $security->getUser();
        $user = $userRepository->findOneBy(['email' => $user->getUserIdentifier()]);

        $tenants = 0;
        $income = 0;
        $flatsNumber = 0;

        if (in_array('ROLE_LANDLORD', $user->getRoles()))
        {
            $landlord = $landlordRepository->findOneBy(['email' => $user->getUserIdentifier()]);
            $flats = $landlord->getFlats();

            foreach ($flats as $flat)
            {
                $tenants += count($flat->getTenants()->toArray());
                $income += $flat->getRent();
                $flatsNumber++;
            }
        }

        $queryBuilder = $entityManager->createQueryBuilder();
        $query = $queryBuilder
            ->select('t')
            ->from('App\Entity\Task', 't')
            ->where('t.user = :user')
            ->orderBy('t.position', 'ASC') // Sort by 'position' in ascending order
            ->setParameter('user', $user)
            ->getQuery();
        $tasks = $query->getResult();

        $task = new Task();
        $form = $this->createForm(TasksFormType::class, $task, [
            'session' => $session,
        ]);

        return $this->render('panel/index.html.twig', [
            'tasks' => $tasks,
            'form' => $form->createView(),
            'flats' => $flatsNumber,
            'tenants' => $tenants,
            'income' => $income
        ]);
    }
}
