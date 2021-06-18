<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\TaskList;
use Psr\Log\LoggerInterface;
use App\Repository\TaskRepository;
use App\Repository\TaskListRepository;
use DateTimeImmutable;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class ApiJournalController extends AbstractController
{
    /**
     * @Route("/api/journal", name="api_journal", methods={"GET"})
     */
    public function index(TaskListRepository $taskListRepository, LoggerInterface $logger)
    {
        return $this->json(
            $taskListRepository->findAll(), 
            200, 
            [],
            ['groups'=>'tasklist_read']);
    }

    /**
     * @Route("/api/tasks/{id}", name="api_tasks", methods={"GET"})
     */
    public function getTasks($id, TaskListRepository $taskListRepository, LoggerInterface $logger){
        $taskList = $taskListRepository->find($id);
        return $this->json(
            $taskList->getTasks(),
            200,
            [],
            ['groups'=>'tasks_read']
        );

    }

    /**
     * @Route("/api/journal/create", name="api_create_taskList", methods={"POST","OPTIONS"})
     */
    public function createTaskList(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, ValidatorInterface $validator, LoggerInterface $l){
        if ($request->isMethod('OPTIONS')) {
            return $this->json(
                [], 
                200, 
                ["Access-Control-Allow-Origin" => "*", "Access-Control-Allow-Methods" => "GET, PUT, POST, DELETE"]
            );
        }
        try {
            $rcvData = $request->getContent();
            $taskList = $serializer->deserialize($rcvData, TaskList::class, 'json');
            $taskList->setDateCreated(new DateTimeImmutable());
            $taskList->setDateModified(new DateTime());
            $errors = $validator->validate($taskList);
            if(count($errors) > 0){
                return $this->json(
                    $errors, 
                    400, 
                    ["Access-Control-Allow-Origin" => "*", "Access-Control-Allow-Methods" => "GET, PUT, POST, DELETE"],
                    []
                );
            } else {
                $em->persist($taskList);
                $em->flush();
                return $this->json(
                    $taskList, 
                    201, 
                    ["Access-Control-Allow-Origin" => "*", "Access-Control-Allow-Methods" => "GET, PUT, POST, DELETE"],
                    []
                );
            }

            
        } catch (NotEncodableValueException $e) {
            return $this->json([
                'status'=> 400,
                'message'=> $e->getMessage()
            ], 400, ["Access-Control-Allow-Origin" => "*", "Access-Control-Allow-Methods" => "GET, PUT, POST, DELETE"]);
        }
    }

    /**
     * @Route("/api/journal/delete/{id}", name="api_delete_taskList", methods={"DELETE"}, requirements={"id":"\d+"})
     */
    public function deleteTaskList($id, Request $request, TaskListRepository $repo, EntityManagerInterface $manager, LoggerInterface $l){
        try {
            $taskList = $repo->find($id);
            $l->info($taskList->getTitle());    
            $manager->remove($taskList);
            $manager->flush();
            return $this->json("", 200, ["Access-Control-Allow-Origin"=>"*"], []);
        } catch (NotEncodableValueException $e) {
            return $this->json([
                'status'=> 400,
                'message'=> $e->getMessage(),
            ], 400, ["Access-Control-Allow-Origin"=>"*"]);
        }
    }

    /**
     * @Route("/api/task/create/{id}", name="api_create_task", methods={"POST","OPTIONS"})
     */
    public function createTask($id, Request $request, TaskListRepository $repo, EntityManagerInterface $em, SerializerInterface $serializer, ValidatorInterface $validator){
        if ($request->isMethod('OPTIONS')) {
            return $this->json([], 200, ["Access-Control-Allow-Origin" => "*", "Access-Control-Allow-Headers" => "*", "Access-Control-Allow-Methods" => "*"]);
        }
        try {
            $taskList = $repo->find($id);
            $task = $serializer->deserialize($request->getContent(), Task::class, 'json');
            $task->setDateCreated(new DateTimeImmutable());
            $task->setIsDone(false);
            $task->setTaskList($taskList);
            $errors = $validator->validate($task);
            if(count($errors) > 0){
                return $this->json($errors, 400, ["Access-Control-Allow-Origin" => "*", "Access-Control-Allow-Headers" => "*", "Access-Control-Allow-Methods" => "*"]);
            } else {
                $taskList = $task->getTaskList();
                $taskList->setDateModified(new DateTime());
                $em->flush();
                $em->persist($task);
                $em->flush();
                return $this->json($task, 201,  ["Access-Control-Allow-Origin" => "*", "Access-Control-Allow-Headers" => "*", "Access-Control-Allow-Methods" => "*"], ['groups'=>'tasks_read']);
            }
   
        } catch (NotEncodableValueException $e) {
            return $this->json([
                'status'=> 400,
                'message'=> $e->getMessage()
            ], 400, ["Access-Control-Allow-Origin"=>"*"]);
        }
    }


    /**
     * @Route("/api/task/get/{id}", name="api_get_task", methods={"GET"})
     */
    public function getTaskList($id, TaskListRepository $taskListRepo){
        $taskListData = $taskListRepo->find($id);
        return $this->json($taskListData, 200, [], ['groups'=>'tasklist_read']);
    }


    /**
     * @Route("/api/task/update", name="api_update_task", methods={"PUT"})
     */
    public function updateTask(Request $request, TaskRepository $repo, EntityManagerInterface $em){
        try {
            $data = json_decode($request->getContent(), true);
            $task = $repo->find($data["id"]);
            $task->setDescription($data["description"]);
            $taskList = $task->getTaskList();
            $taskList->setDateModified(new DateTime());
            $em->flush();
            $em->persist($task);
            $em->flush();
            return $this->json($task, 200, ["Access-Control-Allow-Origin"=>"*"], ['groups'=>'tasklist_read']);
        } catch (NotEncodableValueException $e) {
            return $this->json([
                'status'=> 400,
                'message'=> $e->getMessage()
            ], 400, ["Access-Control-Allow-Origin"=>"*"]);
        }
    }

    /**
     * @Route("/api/task/set", name="api_set_reset_task", methods={"PUT"})
     */
    public function setResetTask(Request $request, TaskRepository $repo, EntityManagerInterface $em, LoggerInterface $logger){
        try {
            $data = json_decode($request->getContent(), true);
            $task = $repo->find($data["id"]);
            $task->setIsDone(!$task->getIsDone());
            $taskList = $task->getTaskList();
            $taskList->setDateModified(new DateTime());
            $em->flush();
            $em->persist($task);
            $em->flush();
            return $this->json($task->getIsDone(), 200, ["Access-Control-Allow-Origin"=>"*"], []);
        } catch (NotEncodableValueException $e) {
            return $this->json([
                'status'=> 400,
                'message'=> $e->getMessage()
            ], 400, ["Access-Control-Allow-Origin"=>"*"]);
        }
    }

    /**
     * @Route("/api/task/delete/{id}", name="api_delete_task", methods={"DELETE"}, requirements={"id":"\d+"})
     */
    public function DeleteTask($id, Request $request, TaskRepository $repo, EntityManagerInterface $manager, LoggerInterface $l){
        try {
            // $data = json_decode($request->getContent(), true);
            $task = $repo->find($id);
            $l->warning($id);
            $taskList = $task->getTaskList();
            $taskList->setDateModified(new DateTime());
            $manager->flush();
            $manager->remove($task);
            $manager->flush();
            return $this->json("", 200, ["Access-Control-Allow-Origin"=>"*"], );
        } catch (NotEncodableValueException $e) {
            return $this->json([
                'status'=> 400,
                'message'=> $e->getMessage()
            ], 400, ["Access-Control-Allow-Origin"=>"*"]);
        }
    }

    /**
     * @Route("/api/q/{toQuerry}", name="api_query", methods={"GET"})
     */
    public function query(string $toQuerry, TaskRepository $taskRepo, TaskListRepository $taskListRepo, LoggerInterface $l){        
        $taskListQuery = $taskListRepo->findByQuery($toQuerry);
        $tasksQuery = $taskRepo->findByQuery($toQuerry);
        return $this->json(["taskLists"=>$taskListQuery, "tasks"=>$tasksQuery], 200, [], ['groups'=>'query']);
    }

}
