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

    /** Number of tickets sold, updated by reservations only (never editable in a form). */
    #[ORM\Column(options: ['default' => 0])]
    private int $sold = 0;

    #[ORM\ManyToOne(inversedBy: 'ticketTypes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Event $event = null;

    #[Assert\Callback]
    public function validateQuantity(ExecutionContextInterface $context): void
    {
        if ($this->quantity === null) {
            return;
        }

        // Tickets already sold cannot disappear.
        if ($this->quantity < $this->sold) {
            $context->buildViolation(sprintf('%d billets ont déjà été vendus : la quantité ne peut pas être inférieure.', $this->sold))
                ->atPath('quantity')
                ->addViolation();
        }

        // All ticket types of an event together must fit in the venue.
        $venue = $this->event?->getVenue();
        if ($venue !== null) {
            $others = 0;
            foreach ($this->event->getTicketTypes() as $ticketType) {
                if ($ticketType !== $this) {
                    $others += (int) $ticketType->getQuantity();
                }
            }
            $available = $venue->getCapacity() - $others;

            if ($this->quantity > $available) {
                $context->buildViolation(sprintf(
                    'Capacité du lieu dépassée : il reste %d place(s) disponible(s) sur %d.',
                    max(0, $available),
                    $venue->getCapacity(),
                ))->atPath('quantity')->addViolation();
            }
        }
    }

    public function getRemaining(): int
    {
        return max(0, (int) $this->quantity - $this->sold);
    }

    public function isSoldOut(): bool
    {
        return $this->getRemaining() === 0;
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

    public function getSold(): int
    {
        return $this->sold;
    }

    public function setSold(int $sold): static
    {
        $this->sold = $sold;

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
}
