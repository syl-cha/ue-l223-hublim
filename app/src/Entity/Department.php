<?php

namespace App\Entity;

use App\Repository\DepartmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepartmentRepository::class)]
class Department
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column(length: 10)]
    private ?string $color = null;

    /**
     * @var Collection<int, StudyField>
     */
    #[ORM\OneToMany(targetEntity: StudyField::class, mappedBy: 'department')]
    private Collection $studyFields;

    public function __construct()
    {
        $this->studyFields = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return Collection<int, StudyField>
     */
    public function getStudyFields(): Collection
    {
        return $this->studyFields;
    }

    public function addStudyField(StudyField $studyField): static
    {
        if (!$this->studyFields->contains($studyField)) {
            $this->studyFields->add($studyField);
            $studyField->setDepartment($this);
        }

        return $this;
    }

    public function removeStudyField(StudyField $studyField): static
    {
        if ($this->studyFields->removeElement($studyField)) {
            // set the owning side to null (unless already changed)
            if ($studyField->getDepartment() === $this) {
                $studyField->setDepartment(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->label;
    }
}
