<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\User\TasksFormType;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Ulid;

class PanelController extends AbstractController
{
    #[Route('/panel', name: 'app_panel')]
    public function index(Security $security, UserRepository $userRepository, SessionInterface $session, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $security->getUser();
        $user = $userRepository->findOneBy(['email' => $user->getUserIdentifier()]);

        $tasks = $user->getTasks();

        $task = new Task();
        $form = $this->createForm(TasksFormType::class, $task, [
            'session' => $session,
        ]);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $description = $form->get('description')->getData();
            if ($description == '')
            {
                $this->addFlash('error', 'Task cannot be empty');
                return $this->redirectToRoute('app_panel');
            }

            $task->setIsDone(false);
            // set position to last
            $task->setPosition($tasks->count() + 1);
            $task->setDescription($description);

            $task->setUser($user);
            $user->addTask($task);

            $entityManager->persist($task);
            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $this->render('panel/index.html.twig', [
            'tasks' => $tasks,
            'form' => $form->createView()
        ]);
    }

    #[Route('/panel/tasks/delete-task/{id}', name: 'app_task_delete')]
    public function deleteTask(int $id, Security $security, UserRepository $userRepository, TaskRepository $taskRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $security->getUser();
        $user = $userRepository->findOneBy(['email' => $user->getUserIdentifier()]);
        $task = $taskRepository->findOneBy(['id' => $id]);

        $user->removeTask($task);
        $entityManager->remove($task);
        $entityManager->persist($user);
        $entityManager->flush();

        $response = new Response();
        $response->setStatusCode(200);
        
        return $response;
    }

    #[Route('/panel/tasks/mark-as-done/{id}', name: 'app_task_mark_as_done')]
    public function markAsDone(int $id, TaskRepository $taskRepository, EntityManagerInterface $entityManager): Response
    {
        $task = $taskRepository->findOneBy(['id' => $id]);
        if ($task->isIsDone())
        {
            $task->setIsDone(false);
        }
        else
        {
            $task->setIsDone(true);
        }
        $entityManager->persist($task);
        $entityManager->flush();

        $response = new Response();
        $response->setStatusCode(200);

        return $response;
    }
}
