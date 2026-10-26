<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class PhotoSlot
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120, unique: true)]
    private string $slotKey;

    #[ORM\Column(length: 30)]
    private string $ratio;

    #[ORM\Column(length: 100)]
    private string $minimumSize;

    #[ORM\Column(length: 255)]
    private string $idealDescription;

    #[ORM\Column(nullable: true)]
    private ?int $mediaAssetId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlotKey(): string
    {
        return $this->slotKey;
    }

    public function setSlotKey(string $slotKey): void
    {
        $this->slotKey = $slotKey;
    }

    public function getRatio(): string
    {
        return $this->ratio;
    }

    public function setRatio(string $ratio): void
    {
        $this->ratio = $ratio;
    }

    public function getMinimumSize(): string
    {
        return $this->minimumSize;
    }

    public function setMinimumSize(string $minimumSize): void
    {
        $this->minimumSize = $minimumSize;
    }

    public function getIdealDescription(): string
    {
        return $this->idealDescription;
    }

    public function setIdealDescription(string $idealDescription): void
    {
        $this->idealDescription = $idealDescription;
    }

    public function getMediaAssetId(): ?int
    {
        return $this->mediaAssetId;
    }

    public function setMediaAssetId(?int $mediaAssetId): void
    {
        $this->mediaAssetId = $mediaAssetId;
    }
}
