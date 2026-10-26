<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Certification
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 140)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $verificationUrl = null;

    #[ORM\Column(nullable: true)]
    private ?int $logoMediaId = null;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column(options: ['default' => false])]
    private bool $isPublished = false;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getVerificationUrl(): ?string
    {
        return $this->verificationUrl;
    }

    public function setVerificationUrl(?string $verificationUrl): void
    {
        $this->verificationUrl = $verificationUrl;
    }

    public function getLogoMediaId(): ?int
    {
        return $this->logoMediaId;
    }

    public function setLogoMediaId(?int $logoMediaId): void
    {
        $this->logoMediaId = $logoMediaId;
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
