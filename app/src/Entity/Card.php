<?php

namespace App\Entity;

use App\Repository\CardRepository;
use App\Enum\CardState;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CardRepository::class)]
class Card
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    // #[ORM\Column(length: 255)]
    // private ?string $state = null;
    // changement pour utiliser une énumération
    #[ORM\Column(length: 20, enumType: CardState::class)]
    private ?CardState $state = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'card')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'card', orphanRemoval: true)]
    private Collection $messages;

    #[ORM\ManyToOne(inversedBy: 'cards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    /**
     * @var Collection<int, Image>
     */
    #[ORM\OneToMany(targetEntity: Image::class, mappedBy: 'card', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $images;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'userFavoriteCards')]
    private Collection $fanUsers;

    /**
     * @var Collection<int, Status>
     */
    #[ORM\ManyToMany(targetEntity: Status::class, inversedBy: 'relatedCards')]
    private Collection $targetStatus;

    /**
     * @var Collection<int, StudyField>
     */
    #[ORM\ManyToMany(targetEntity: StudyField::class, inversedBy: 'relatedCards')]
    private Collection $targetStudyFields;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->fanUsers = new ArrayCollection();
        $this->targetStatus = new ArrayCollection();
        $this->targetStudyFields = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->state = CardState::PUBLISHED;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    // public function getState(): ?string
    public function getState(): ?CardState
    {
        return $this->state;
    }

    // public function setState(string $state): static
    public function setState(CardState $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setCard($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getCard() === $this) {
                $message->setCard(null);
            }
        }

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setCard($this);
        }

        return $this;
    }

    public function removeImage(Image $image): static
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getCard() === $this) {
                $image->setCard(null);
            }
        }

        return $this;
    }

    public function getCoverImage(): ?Image
    {
        return $this->images->first() ?: null;
    }

    /**
     * @return Collection<int, User>
     */
    public function getFanUsers(): Collection
    {
        return $this->fanUsers;
    }

    public function addFanUser(User $fanUser): static
    {
        if (!$this->fanUsers->contains($fanUser)) {
            $this->fanUsers->add($fanUser);
            $fanUser->addUserFavoriteCard($this);
        }

        return $this;
    }

    public function removeFanUser(User $fanUser): static
    {
        if ($this->fanUsers->removeElement($fanUser)) {
            $fanUser->removeUserFavoriteCard($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Status>
     */
    public function getTargetStatus(): Collection
    {
        return $this->targetStatus;
    }

    public function addTargetStatus(Status $targetStatus): static
    {
        if (!$this->targetStatus->contains($targetStatus)) {
            $this->targetStatus->add($targetStatus);
        }

        return $this;
    }

    public function removeTargetStatus(Status $targetStatus): static
    {
        $this->targetStatus->removeElement($targetStatus);

        return $this;
    }

    /**
     * @return Collection<int, StudyField>
     */
    public function getTargetStudyFields(): Collection
    {
        return $this->targetStudyFields;
    }

    public function addTargetStudyField(StudyField $targetStudyField): static
    {
        if (!$this->targetStudyFields->contains($targetStudyField)) {
            $this->targetStudyFields->add($targetStudyField);
        }

        return $this;
    }

    public function removeTargetStudyField(StudyField $targetStudyField): static
    {
        $this->targetStudyFields->removeElement($targetStudyField);

        return $this;
    }
}
