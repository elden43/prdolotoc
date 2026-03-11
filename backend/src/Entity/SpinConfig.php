<?php

namespace App\Entity;

use App\Repository\SpinConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SpinConfigRepository::class)]
#[ORM\Table(name: 'spin_config')]
#[ORM\UniqueConstraint(name: 'uniq_spin_config_slug', columns: ['slug'])]
class SpinConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 160)]
    private string $slug;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $options = [];

    #[ORM\Column]
    private bool $removeAfterPick = true;

    #[ORM\Column(length: 16)]
    private string $visualMode = 'classic';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /** @return list<string> */
    public function getOptions(): array
    {
        return $this->options;
    }

    /** @param list<string> $options */
    public function setOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function isRemoveAfterPick(): bool
    {
        return $this->removeAfterPick;
    }

    public function setRemoveAfterPick(bool $removeAfterPick): static
    {
        $this->removeAfterPick = $removeAfterPick;

        return $this;
    }

    public function getVisualMode(): string
    {
        return $this->visualMode;
    }

    public function setVisualMode(string $visualMode): static
    {
        $this->visualMode = $visualMode;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
