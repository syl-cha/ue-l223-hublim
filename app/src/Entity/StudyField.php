<?php

namespace App\Entity;

use App\Repository\StudyFieldRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudyFieldRepository::class)]
class StudyField
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $theme = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'studyField')]
    private Collection $users;

    /**
     * @var Collection<int, Card>
     */
    #[ORM\ManyToMany(targetEntity: Card::class, mappedBy: 'targetStudyFields')]
    private Collection $relatedCards;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->relatedCards = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name; 
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setStudyField($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getStudyField() === $this) {
                $user->setStudyField(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Card>
     */
    public function getRelatedCards(): Collection
    {
        return $this->relatedCards;
    }

    public function addRelatedCard(Card $relatedCard): static
    {
        if (!$this->relatedCards->contains($relatedCard)) {
            $this->relatedCards->add($relatedCard);
            $relatedCard->addTargetStudyField($this);
        }

        return $this;
    }

    public function removeRelatedCard(Card $relatedCard): static
    {
        if ($this->relatedCards->removeElement($relatedCard)) {
            $relatedCard->removeTargetStudyField($this);
        }

        return $this;
    }
}
