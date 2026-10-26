<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Service
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\Column(length: 30)]
    private string $serviceGroup;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'json')]
    private array $items = [];

    #[ORM\Column(nullable: true)]
    private ?int $mediaAssetId = null;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $isPublished = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getServiceGroup(): string
    {
        return $this->serviceGroup;
    }

    public function setServiceGroup(string $serviceGroup): void
    {
        $this->serviceGroup = $serviceGroup;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function getMediaAssetId(): ?int
    {
        return $this->mediaAssetId;
    }

    public function setMediaAssetId(?int $mediaAssetId): void
    {
        $this->mediaAssetId = $mediaAssetId;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): void
    {
        $this->isPublished = $isPublished;
    }
}
