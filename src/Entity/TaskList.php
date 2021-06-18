<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\TaskListRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=TaskListRepository::class)
 */
class TaskList
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"tasklist_read", "query"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"tasklist_read", "query"})
     * @Assert\NotBlank
     */
    private $Title;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"tasklist_read", "query"})
     */
    private $DateCreated;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"tasklist_read", "query"})
     */
    private $DateModified;

    /**
     * @ORM\OneToMany(targetEntity=Task::class, mappedBy="taskList", cascade={"remove"})
     */
    //  * @Groups({"tasklist_read"})
    private $Tasks;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"tasklist_read", "query"})
     * @Assert\NotBlank
     */
    private $Description;

    public function __construct()
    {
        $this->Tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->Title;
    }

    public function setTitle(string $Title): self
    {
        $this->Title = $Title;

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

    public function getDateModified(): ?\DateTimeInterface
    {
        return $this->DateModified;
    }

    public function setDateModified(\DateTimeInterface $DateModified): self
    {
        $this->DateModified = $DateModified;

        return $this;
    }

    /**
     * @return Collection|Task[]
     */
    public function getTasks(): Collection
    {
        return $this->Tasks;
    }

    public function addTask(Task $task): self
    {
        if (!$this->Tasks->contains($task)) {
            $this->Tasks[] = $task;
            $task->setTaskList($this);
        }

        return $this;
    }

    public function removeTask(Task $task): self
    {
        if ($this->Tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getTaskList() === $this) {
                $task->setTaskList(null);
            }
        }

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function setDescription(?string $Description): self
    {
        $this->Description = $Description;

        return $this;
    }
}
