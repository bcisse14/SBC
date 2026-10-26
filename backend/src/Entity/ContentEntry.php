<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class ContentEntry
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150, unique: true)]
    private string $contentKey;

    #[ORM\Column(type: 'text')]
    private string $defaultValue;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $customValue = null;

    #[ORM\Column(length: 16, options: ['default' => 'published'])]
    private string $status = 'published';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContentKey(): string
    {
        return $this->contentKey;
    }

    public function setContentKey(string $contentKey): void
    {
        $this->contentKey = $contentKey;
    }

    public function getDefaultValue(): string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(string $defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    public function getCustomValue(): ?string
    {
        return $this->customValue;
    }

    public function setCustomValue(?string $customValue): void
    {
        $this->customValue = $customValue;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getResolvedValue(): string
    {
        return $this->customValue ?? $this->defaultValue;
    }
}
