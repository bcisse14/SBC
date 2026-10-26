<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['status', 'sort_order'])]
class RealisationCategory
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $title;

    #[ORM\Column(length: 160, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Service $tagService = null;

    /** @var list<int> identifiants MediaAsset, dans l'ordre d'affichage */
    #[ORM\Column(type: 'json')]
    private array $gallery = [];

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column(length: 16, options: ['default' => 'draft'])]
    private string $status = 'draft';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getTagService(): ?Service
    {
        return $this->tagService;
    }

    public function setTagService(?Service $tagService): void
    {
        $this->tagService = $tagService;
    }

    /** @return list<int> */
    public function getGallery(): array
    {
        return $this->gallery;
    }

    /** @param list<int> $gallery */
    public function setGallery(array $gallery): void
    {
        $this->gallery = array_values(array_map('intval', $gallery));
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
