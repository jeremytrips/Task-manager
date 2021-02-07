<?php

namespace App\Controller;

use DateTime;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

use App\Entity\TaskList;
use App\Repository\TaskListRepository;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;

use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

class JournalController extends AbstractController
{
    /**
     * @Route("/", name="lists")
     */
    public function index(TaskListRepository $repo): Response
    {
        // Index of the website. Retrieve every lists and return them in the context.
        $defaultData = ['message'=>'Description'];
        $form = $this->createFormBuilder($defaultData)
                     ->setAction($this->generateUrl('new_task_list'))
                     ->add('title',
                         TextType::class,[
                            'label' => 'New list:',
                            'label_attr' => ['class' => "col-form-label"],
                            'attr' => ['placeholder' => "List title", 'class' => "form-control"],
                            'constraints' => [new NotBlank()]
                        ])
                     ->getForm();
       
        return $this->render(
            'journal/index.html.twig', [
                'tasklists' => $repo->findAll(),
                'tasklist_form'=> $form->createView()
        ]);
    }

    /**
     * @Route("/new", name="new_task_list")
     */
    public function CreateNewList(Request $request, EntityManagerInterface $manager, LoggerInterface $logger)
    {
        // Check if the ParameterBag has a size.

        $taskList = new TaskList();
        $form = $this->createFormBuilder($taskList)
                     ->add("title")
                     ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $taskList->setDateCreated(new DateTimeImmutable())
                     ->setDateModified(new DateTime());
            $manager->persist($taskList);
            $manager->flush();
            return $this->redirectToRoute("task_list", ['id'=>$taskList->getId()]);
        }

        return $this->render(
            'test.html.twig'
        );
    }

    /**
     * @Route("/delete/{id}", name="delete_list")
     */
    public function DeleteList(TaskList $taskList, EntityManagerInterface $manager)
    {
        $manager->remove($taskList);
        $manager->flush();

        return $this->redirectToRoute("task_list");
    }

    /**
     * @Route("/{id}", name="task_list")
     */
    public function ListTask(TaskList $taskList)
    {
        // Return the id Tasklist and display all tasks.
        return $this->render(
            'journal/tasklist.html.twig', [
                'task_list' => $taskList,
        ]);
    }
}
