<?php

namespace App\Entity;

use App\Repository\StatusRepository;
use App\Enum\StatusLabel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StatusRepository::class)]
class Status
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    // private ?string $label = null;
    private ?StatusLabel $label = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'status', orphanRemoval: true)]
    private Collection $users;

    /**
     * @var Collection<int, Card>
     */
    #[ORM\ManyToMany(targetEntity: Card::class, mappedBy: 'targetStatus')]
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

    // public function getLabel(): ?string
    public function getLabel(): ?StatusLabel
    {
        return $this->label;
    }

    // public function setLabel(string $label): static
    public function setLabel(StatusLabel $label): static
    {
        $this->label = $label;

        return $this;
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
            $user->setStatus($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getStatus() === $this) {
                $user->setStatus(null);
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
            $relatedCard->addTargetStatus($this);
        }

        return $this;
    }

    public function removeRelatedCard(Card $relatedCard): static
    {
        if ($this->relatedCards->removeElement($relatedCard)) {
            $relatedCard->removeTargetStatus($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->label ? $this->label->value : '';
    }
}
