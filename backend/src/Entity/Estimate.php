<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Estimate
{
    use TimestampableTrait;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: QuoteRequest::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?QuoteRequest $request = null;

    #[ORM\Column(type: 'json')]
    private array $data = [];

    public function getId(): ?int { return $this->id; }
    public function getRequest(): ?QuoteRequest { return $this->request; }
    public function setRequest(?QuoteRequest $request): void { $this->request = $request; }
    public function getData(): array { return $this->data; }
    public function setData(array $data): void { $this->data = $data; }
    public function getNumber(): string { return sprintf('DEV-%s-%06d', $this->getCreatedAt()->format('Y'), $this->id); }
}
