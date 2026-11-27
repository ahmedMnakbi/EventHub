<?php

namespace App\Entity;

use App\Repository\TicketTypeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: TicketTypeRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_ticket_type_event_name', columns: ['event_id', 'name'])]
#[UniqueEntity(fields: ['event', 'name'], message: 'Ce type de billet existe déjà pour cet événement.', errorPath: 'name')]
class TicketType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 60)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 60)]
    private ?string $name = null;

    /** Money is stored as DECIMAL (exact), never as float. Doctrine maps it to a PHP string. */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero(message: 'Le prix ne peut pas être négatif.')]
    #[Assert\LessThanOrEqual(value: 10000, message: 'Le prix ne peut pas dépasser 10 000 DT.')]
    private ?string $price = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Positive(message: 'La quantité doit être supérieure à 0.')]
    #[Assert\LessThanOrEqual(200000)]
    private ?int $quantity = null;

    #[ORM\ManyToOne(inversedBy: 'ticketTypes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Event $event = null;

    /** Physical area of the venue this ticket type gives access to. */
    #[ORM\ManyToOne(inversedBy: 'ticketTypes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Choisissez une zone.')]
    private ?Zone $zone = null;

    #[Assert\Callback]
    public function validateZone(ExecutionContextInterface $context): void
    {
        if ($this->zone === null || $this->event === null) {
            return;
        }

        // The zone must belong to the venue of the event (protects against a tampered form).
        if ($this->zone->getVenue() !== $this->event->getVenue()) {
            $context->buildViolation('Cette zone n\'appartient pas au lieu de l\'événement.')
                ->atPath('zone')
                ->addViolation();

            return;
        }

        // For one event, all ticket types of a zone together must fit in that zone.
        if ($this->quantity !== null) {
            $others = 0;
            foreach ($this->event->getTicketTypes() as $ticketType) {
                if ($ticketType !== $this && $ticketType->getZone() === $this->zone) {
                    $others += (int) $ticketType->getQuantity();
                }
            }
            $available = (int) $this->zone->getCapacity() - $others;

            if ($this->quantity > $available) {
                $context->buildViolation(sprintf(
                    'Capacité de la zone « %s » dépassée : il reste %d place(s) sur %d.',
                    $this->zone->getName(),
                    max(0, $available),
                    (int) $this->zone->getCapacity(),
                ))->atPath('quantity')->addViolation();
            }
        }
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

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function setZone(?Zone $zone): static
    {
        $this->zone = $zone;

        return $this;
    }
}
