<?php

namespace App\Entity;

use App\Repository\VenueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: VenueRepository::class)]
class Venue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 120)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 80)]
    #[Assert\Regex(pattern: "/^[\\p{L}][\\p{L} '\\-]*$/u", message: 'Nom de ville invalide.')]
    private ?string $city = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Positive(message: 'La capacité doit être supérieure à 0.')]
    #[Assert\LessThanOrEqual(200000)]
    private ?int $capacity = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * Composition: zones are deleted with their venue.
     *
     * @var Collection<int, Zone>
     */
    #[ORM\OneToMany(targetEntity: Zone::class, mappedBy: 'venue', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $zones;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->zones = new ArrayCollection();
    }

    #[Assert\Callback]
    public function validateZones(ExecutionContextInterface $context): void
    {
        // The venue capacity cannot become smaller than the sum of its zones.
        if ($this->capacity !== null && $this->getZonesCapacity() > $this->capacity) {
            $context->buildViolation(sprintf('Les zones de ce lieu totalisent déjà %d places.', $this->getZonesCapacity()))
                ->atPath('capacity')
                ->addViolation();
        }
    }

    /** @return Collection<int, Zone> */
    public function getZones(): Collection
    {
        return $this->zones;
    }

    public function addZone(Zone $zone): static
    {
        if (!$this->zones->contains($zone)) {
            $this->zones->add($zone);
            $zone->setVenue($this);
        }

        return $this;
    }

    public function removeZone(Zone $zone): static
    {
        $this->zones->removeElement($zone);

        return $this;
    }

    public function getZonesCapacity(): int
    {
        return array_sum($this->zones->map(fn (Zone $z) => (int) $z->getCapacity())->toArray());
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->name, $this->city);
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address !== null ? trim($address) : null;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city !== null ? trim($city) : null;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
