<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class EstimateProfile
{
    use TimestampableTrait;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private string $kind;

    #[ORM\Column(length: 180)]
    private string $name;

    #[ORM\Column(type: 'json')]
    private array $data = [];

    public function getId(): ?int { return $this->id; }
    public function getKind(): string { return $this->kind; }
    public function setKind(string $kind): void { $this->kind = $kind; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
    public function getData(): array { return $this->data; }
    public function setData(array $data): void { $this->data = $data; }
}
