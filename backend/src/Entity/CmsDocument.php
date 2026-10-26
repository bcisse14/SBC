<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'cms_resource_slug', columns: ['resource', 'slug'])]
class CmsDocument
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private string $resource;

    #[ORM\Column(length: 120)]
    private string $slug = '';

    #[ORM\Column(type: 'json')]
    private array $data = [];

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column]
    private bool $isPublished = false;

    public function getId(): ?int { return $this->id; }
    public function getResource(): string { return $this->resource; }
    public function setResource(string $resource): void { $this->resource = $resource; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): void { $this->slug = $slug; }
    public function getData(): array { return $this->data; }
    public function setData(array $data): void { $this->data = $data; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): void { $this->sortOrder = $sortOrder; }
    public function isPublished(): bool { return $this->isPublished; }
    public function setIsPublished(bool $isPublished): void { $this->isPublished = $isPublished; }
}
