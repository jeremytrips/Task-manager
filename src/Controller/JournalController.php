<?php

namespace App\Controller;

use App\Entity\Task;
use DateTime;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

use App\Entity\TaskList;
use App\Repository\TaskListRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\VarDumper\VarDumper;

use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

class JournalController extends AbstractController
{
    /**
     * @Route("/", name="lists")
     */
    public function index(TaskListRepository $repo, LoggerInterface $logger)
    {
        // Return the id Tasklist and display all tasks.
     
        // Index of the website. Retrieve every lists and return them in the context.
        $defaultData = ['message'=>'dunno'];
        $form = $this->createFormBuilder($defaultData)
                     ->setAction($this->generateUrl('new_task_list'))
                     ->add('title',
                            TextType::class,[
                            'label' => 'New list:',
                            'label_attr' => ['class' => "col-form-label"],
                            'attr' => ['placeholder' => "List title", 'class' => "form-control"],
                            'constraints' => [new NotBlank()]
                        ])
                        ->add('description',
                            TextType::class, [
                            'label'=> false,
                            'attr' => ['placeholder' => "Description", 'class' => "form-control"],
                        ])
                     ->getForm();
       
        return $this->render(
            'journal/index.html.twig', [
                'tasklists' => $repo->findAll(),
                'tasklist_form'=> $form->createView()
        ]);
    }

    /**
     * @Route("/q/", name="query")
     */
    public function find(Request $request, TaskListRepository $taskListRepo, TaskRepository $taskRepo ,LoggerInterface $logger)
    {
        $query = $request->request->get("query");
        $taskListQuery = $taskListRepo->findByQuery($query);
        $tasksQuery = $taskRepo->findByQuery($query);
                
        return $this->render(
            'journal/query.html.twig', [
                'tasklists_results' => $taskListQuery,
                'tasks_results' => $tasksQuery
        ]);
    }

    /**
     * @Route("/new", name="new_task_list")
     */
    public function CreateNewList(Request $request, EntityManagerInterface $manager, LoggerInterface $logger)
    {
        // Return the id Tasklist and display all tasks.
        $taskList = new TaskList();
        $form = $this->createFormBuilder($taskList)
                     ->add("title")
                     ->add("description")
                     ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $taskList->setDateCreated(new DateTimeImmutable())
                     ->setDateModified(new DateTime());
            $manager->persist($taskList);
            $manager->flush();
            return $this->redirectToRoute("task_list", ['id'=>$taskList->getId()]);
        }
        // Todo redirect with errors
        return $this->render(
            'test.html.twig'
        );
    }

    /**
     * @Route("/delete/{id}", name="delete_list")
     */
    public function DeleteList($id, TaskListRepository $repo, EntityManagerInterface $manager, LoggerInterface $logger)
    {
        $taskList = $repo->find($id);
        $listTitle = $taskList->getTitle();
        $manager->remove($taskList);
        $manager->flush();
        
        $this->addFlash("notice", sprintf("List %s has been deleted", $listTitle));

        return $this->redirectToRoute("lists");
    }

    /**
     * @Route("/{id}", name="task_list", requirements={"id":"\d+"})
     */
    public function ListTaskOfAList(TaskList $taskList, LoggerInterface $logger)
    {
        $defaultData = ['message'=>'Description'];
        $form = $this->createFormBuilder($defaultData)
                     ->setAction($this->generateUrl('create_task', ['id' => $taskList->getId()]))
                     ->add('description',
                         TextType::class,[
                            'label' => 'New Task:',
                            'label_attr' => ['class' => "col-form-label"],
                            'attr' => ['placeholder' => "Task name", 'class' => "form-control"],
                            'constraints' => [new NotBlank()]
                        ])
                     ->getForm();
        $modifyTaskForm = $this->createFormBuilder()
                            //    ->setAction($this->generateUrl("modify_task",  ['id' => $taskList->getId()]))
                               ->add("Description",
                               TextType::class,[
                                'label' => false,
                                'attr' => ['placeholder' => "New description", 'class' => "form-control",  "style"=>"width: 327%"],
                                'constraints' => [new NotBlank()]
                            ])
                            ->getForm();
        
        return $this->render(
            'journal/tasklist.html.twig', [
                'task_list' => $taskList,
                'task_form' => $form->createView(),
                'modify_task_form' => $modifyTaskForm
        ]);
    }
    
    /**
     * @Route("/task/create/{id}", name="create_task")
     */
    public function NewTask($id, Request $request, TaskListRepository $repo, EntityManagerInterface $manager, LoggerInterface $logger)
    {
        $task = new Task();
        $form = $this->createFormBuilder($task)
                     ->add("description")
                     ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $taskList = $repo->find($id);
            $taskList->setDateModified(new DateTime());
            $manager->flush();
            $task->setDateCreated(new DateTimeImmutable())
                     ->setIsDone(false)
                     ->setTaskList($taskList);
            $manager->persist($task);
            $manager->flush();
            return $this->redirectToRoute("task_list", ['id'=>$id]);
        } else {
            return $this->render('test.html.twig');
        }
    }

    /**
     * @Route("task/modify/{id}", name="modify_task")
     */
    public function ModifyTask($id, Request $request, EntityManagerInterface $manager, TaskRepository $repo, LoggerInterface $logger)
    {
        $task = $repo->find($id);
        $method = $request->getMethod();
        $newDescription = $request->request->get("Description"); 
        if($method == "POST"){
            $task->setDescription($newDescription);
            $manager->persist($task);
            $manager->flush();
            return $this->redirectToRoute("task_list", ['id'=>$task->getTaskList()->getId()]);
        } else {
            return $this->render(
                'test.html.twig',
            );
        }


    }

    /**
     * @Route("task/set/{id}", name="set_reset_task")
     */
    public function SetResetTask($id, EntityManagerInterface $manager, TaskRepository $repo )
    {
        $task = $repo->find($id);
        $listId = $task->getTaskList()->getId(); 
        $task->setIsDone(!$task->getIsDone());
        $manager->flush();
        return $this->redirectToRoute("task_list", ['id'=>$listId]);
    }

    /**
     * @Route("task/delete/{id}", name="delete_task")
     */
    public function DeleteTask(Task $task, EntityManagerInterface $manager)
    {
        $taskList = $task->getTaskList();
        $taskList->setDateModified(new DateTime());
        $manager->flush();
        $manager->remove($task);
        $manager->flush();
        return $this->redirectToRoute("task_list", ['id'=>$taskList->getId()]);
    }

}
