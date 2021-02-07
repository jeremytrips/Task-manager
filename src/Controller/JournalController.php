<?php

namespace App\Controller;

use App\Entity\TaskList;
use App\Repository\TaskListRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;


class JournalController extends AbstractController
{
    /**
     * @Route("/", name="journal")
     */
    public function index(TaskListRepository $repo): Response
    {
        // Index of the website. Retrieve every lists and return them in the context.
        return $this->render(
            'journal/index.html.twig', [
                'tasklists' => $repo->findAll()
        ]);
    }

    /**
     * @Route("/{id}", name="task_list")
     */
    public function FunctionName(TaskList $taskList, LoggerInterface $logger)
    {
        // Return the id list and display all it's task.
        // Use paramConverter to return the list with id = id   
        return $this->render(
            'journal/tasklist.html.twig', [
                'task_list' => $taskList,
        ]);
    }
}
