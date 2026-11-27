<?php

namespace App\Entity;

use App\Enum\EventStatus;
use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Index(columns: ['start_at'], name: 'idx_event_start_at')]
#[ORM\Index(columns: ['status'], name: 'idx_event_status')]
class Event
{
    public const MAX_IMAGES = 8;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 150)]
    private ?string $title = null;

    /** Derived from the title, used in public URLs together with the id (so it does not need to be unique). */
    #[ORM\Column(length: 170)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 20, max: 5000)]
    private ?string $description = null;

    /**
     * File names of the uploaded images (stored in public/uploads/events).
     * The first one is the main poster.
     *
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $images = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull(message: 'Indiquez la date de début.')]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull(message: 'Indiquez la date de fin.')]
    #[Assert\GreaterThan(propertyPath: 'startAt', message: 'La date de fin doit être après la date de début.')]
    private ?\DateTimeImmutable $endAt = null;

    #[ORM\Column(length: 20, enumType: EventStatus::class)]
    #[Assert\NotNull]
    private ?EventStatus $status = EventStatus::Draft;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Choisissez une catégorie.')]
    private ?Category $category = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Choisissez un lieu.')]
    private ?Venue $venue = null;

    /** Composition: ticket types are deleted with their event. */
    #[ORM\OneToMany(targetEntity: TicketType::class, mappedBy: 'event', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['price' => 'ASC'])]
    private Collection $ticketTypes;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->ticketTypes = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->title;
    }

    #[Assert\Callback]
    public function validateBusinessRules(ExecutionContextInterface $context): void
    {
        // Business rule: an event can only be published once it has at least one ticket type.
        if ($this->status === EventStatus::Published && $this->ticketTypes->isEmpty()) {
            $context->buildViolation('Ajoutez au moins un type de billet avant de publier l\'événement.')
                ->atPath('status')
                ->addViolation();
        }

        // Ticket types use zones of the venue: the venue cannot change while tickets exist.
        foreach ($this->ticketTypes as $ticketType) {
            if ($ticketType->getZone() !== null && $ticketType->getZone()->getVenue() !== $this->venue) {
                $context->buildViolation('Le lieu ne peut pas changer : des types de billets utilisent les zones du lieu actuel. Supprimez-les d\'abord.')
                    ->atPath('venue')
                    ->addViolation();
                break;
            }
        }
    }

    public function isPast(): bool
    {
        return $this->endAt !== null && $this->endAt < new \DateTimeImmutable();
    }

    /** Cheapest ticket price ("à partir de"), as a decimal string, or null when no ticket exists. */
    public function getMinPrice(): ?string
    {
        $min = null;
        foreach ($this->ticketTypes as $ticketType) {
            if ($min === null || bccomp($ticketType->getPrice(), $min, 2) < 0) {
                $min = $ticketType->getPrice();
            }
        }

        return $min;
    }

    /** Total number of tickets put on sale (all types). */
    public function getTotalQuantity(): int
    {
        return array_sum($this->ticketTypes->map(fn (TicketType $t) => (int) $t->getQuantity())->toArray());
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title !== null ? trim($title) : null;
        // Always derived from the title, never from user input. Invalid UTF-8 is
        // rejected by validation; just don't crash before it runs.
        $slug = mb_check_encoding((string) $this->title, 'UTF-8')
            ? (new AsciiSlugger('fr'))->slug((string) $this->title)->lower()->truncate(160, '', false)->toString()
            : '';
        $this->slug = $slug !== '' ? $slug : 'evenement';

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /** @return list<string> */
    public function getImages(): array
    {
        return $this->images;
    }

    /** Main poster: the first image. */
    public function getMainImage(): ?string
    {
        return $this->images[0] ?? null;
    }

    public function addImage(string $fileName): static
    {
        $this->images[] = $fileName;

        return $this;
    }

    public function removeImage(string $fileName): static
    {
        $this->images = array_values(array_filter($this->images, fn (string $f) => $f !== $fileName));

        return $this;
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(?\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(?\DateTimeImmutable $endAt): static
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getStatus(): ?EventStatus
    {
        return $this->status;
    }

    // Nullable so that a missing or tampered field gives a validation error (NotNull), not a 500.
    public function setStatus(?EventStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->status === EventStatus::Published;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
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

    public function getVenue(): ?Venue
    {
        return $this->venue;
    }

    public function setVenue(?Venue $venue): static
    {
        $this->venue = $venue;

        return $this;
    }

    /**
     * @return Collection<int, TicketType>
     */
    public function getTicketTypes(): Collection
    {
        return $this->ticketTypes;
    }

    public function addTicketType(TicketType $ticketType): static
    {
        if (!$this->ticketTypes->contains($ticketType)) {
            $this->ticketTypes->add($ticketType);
            $ticketType->setEvent($this);
        }

        return $this;
    }

    public function removeTicketType(TicketType $ticketType): static
    {
        $this->ticketTypes->removeElement($ticketType);

        return $this;
    }
}
