<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class QuoteAttachment
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private QuoteRequest $quoteRequest;

    #[ORM\Column(length: 255)]
    private string $storagePath;

    #[ORM\Column(length: 255)]
    private string $originalName;

    #[ORM\Column(length: 80)]
    private string $mimeType;

    #[ORM\Column]
    private int $sizeBytes;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuoteRequest(): QuoteRequest
    {
        return $this->quoteRequest;
    }

    public function setQuoteRequest(QuoteRequest $quoteRequest): void
    {
        $this->quoteRequest = $quoteRequest;
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    public function setStoragePath(string $storagePath): void
    {
        $this->storagePath = $storagePath;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): void
    {
        $this->originalName = $originalName;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    public function getSizeBytes(): int
    {
        return $this->sizeBytes;
    }

    public function setSizeBytes(int $sizeBytes): void
    {
        $this->sizeBytes = $sizeBytes;
    }
}
