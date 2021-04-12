<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\TaskRepository;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=TaskRepository::class)
 */
class Task
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"tasklist_read"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"tasklist_read"})
     * 
     */
    private $Description;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"tasklist_read"})
     */
    private $DateCreated;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"tasklist_read"})
     */
    private $IsDone;

    /**
     * @ORM\ManyToOne(targetEntity=TaskList::class, inversedBy="Tasks")
     */
    private $taskList;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function setDescription(string $Description): self
    {
        $this->Description = $Description;

        return $this;
    }

    public function getDateCreated(): ?\DateTimeImmutable
    {
        return $this->DateCreated;
    }

    public function setDateCreated(\DateTimeImmutable $DateCreated): self
    {
        $this->DateCreated = $DateCreated;

        return $this;
    }

    public function getIsDone(): ?bool
    {
        return $this->IsDone;
    }

    public function setIsDone(bool $IsDone): self
    {
        $this->IsDone = $IsDone;

        return $this;
    }

    public function getTaskList(): ?TaskList
    {
        return $this->taskList;
    }

    public function setTaskList(?TaskList $taskList): self
    {
        $this->taskList = $taskList;

        return $this;
    }
}
