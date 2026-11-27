<?php

namespace App\Entity;

use App\Repository\ZoneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/** Part of a venue (seats, lawn, stands…) with its own capacity. */
#[ORM\Entity(repositoryClass: ZoneRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_zone_venue_name', columns: ['venue_id', 'name'])]
#[UniqueEntity(fields: ['venue', 'name'], message: 'Cette zone existe déjà dans ce lieu.', errorPath: 'name')]
class Zone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 60)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 60)]
    private ?string $name = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Positive(message: 'La capacité doit être supérieure à 0.')]
    #[Assert\LessThanOrEqual(200000)]
    private ?int $capacity = null;

    #[ORM\ManyToOne(inversedBy: 'zones')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Venue $venue = null;

    /** @var Collection<int, TicketType> */
    #[ORM\OneToMany(targetEntity: TicketType::class, mappedBy: 'zone')]
    private Collection $ticketTypes;

    public function __construct()
    {
        $this->ticketTypes = new ArrayCollection();
    }

    public function __toString(): string
    {
        return sprintf('%s (%d places)', $this->name, (int) $this->capacity);
    }

    #[Assert\Callback]
    public function validateCapacity(ExecutionContextInterface $context): void
    {
        if ($this->capacity === null || $this->venue === null) {
            return;
        }

        // All zones together must fit in the venue.
        $others = 0;
        foreach ($this->venue->getZones() as $zone) {
            if ($zone !== $this) {
                $others += (int) $zone->getCapacity();
            }
        }
        $available = (int) $this->venue->getCapacity() - $others;
        if ($this->capacity > $available) {
            $context->buildViolation(sprintf(
                'Capacité du lieu dépassée : il reste %d place(s) à répartir sur %d.',
                max(0, $available),
                (int) $this->venue->getCapacity(),
            ))->atPath('capacity')->addViolation();
        }

        // The zone cannot shrink below the tickets already put on sale in it for one event.
        $maxAllocated = max([0, ...array_values($this->getAllocatedPerEvent())]);
        if ($this->capacity < $maxAllocated) {
            $context->buildViolation(sprintf('%d billets sont déjà mis en vente dans cette zone pour un événement.', $maxAllocated))
                ->atPath('capacity')
                ->addViolation();
        }
    }

    /** @return array<int, int> event id => tickets put on sale in this zone */
    private function getAllocatedPerEvent(): array
    {
        $allocated = [];
        foreach ($this->ticketTypes as $ticketType) {
            $eventId = (int) $ticketType->getEvent()?->getId();
            $allocated[$eventId] = ($allocated[$eventId] ?? 0) + (int) $ticketType->getQuantity();
        }

        return $allocated;
    }

    public function isUsed(): bool
    {
        return !$this->ticketTypes->isEmpty();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name !== null ? trim($name) : null;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): static
    {
        $this->capacity = $capacity;

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

    /** @return Collection<int, TicketType> */
    public function getTicketTypes(): Collection
    {
        return $this->ticketTypes;
    }
}
