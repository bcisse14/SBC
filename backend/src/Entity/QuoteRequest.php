<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class QuoteRequest
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private string $propertyType;

    #[ORM\Column(type: 'json')]
    private array $requestedServices = [];

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(length: 120)]
    private string $city;

    #[ORM\Column(length: 20)]
    private string $postalCode;

    #[ORM\Column(length: 40)]
    private string $budgetRange;

    #[ORM\Column(length: 50)]
    private string $timeline;

    #[ORM\Column(options: ['default' => false])]
    private bool $visitRequested = false;

    #[ORM\Column(length: 80)]
    private string $firstName;

    #[ORM\Column(length: 80)]
    private string $lastName;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(length: 40)]
    private string $phone;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $callbackTime = null;

    #[ORM\Column(options: ['default' => 'new'])]
    private string $status = 'new';

    #[ORM\Column(options: ['default' => false])]
    private bool $isSpam = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPropertyType(): string
    {
        return $this->propertyType;
    }

    public function setPropertyType(string $propertyType): void
    {
        $this->propertyType = $propertyType;
    }

    public function getRequestedServices(): array
    {
        return $this->requestedServices;
    }

    public function setRequestedServices(array $requestedServices): void
    {
        $this->requestedServices = $requestedServices;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    public function getBudgetRange(): string
    {
        return $this->budgetRange;
    }

    public function setBudgetRange(string $budgetRange): void
    {
        $this->budgetRange = $budgetRange;
    }

    public function getTimeline(): string
    {
        return $this->timeline;
    }

    public function setTimeline(string $timeline): void
    {
        $this->timeline = $timeline;
    }

    public function isVisitRequested(): bool
    {
        return $this->visitRequested;
    }

    public function setVisitRequested(bool $visitRequested): void
    {
        $this->visitRequested = $visitRequested;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = mb_strtolower(trim($email));
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getCallbackTime(): ?string
    {
        return $this->callbackTime;
    }

    public function setCallbackTime(?string $callbackTime): void
    {
        $this->callbackTime = $callbackTime;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function isSpam(): bool
    {
        return $this->isSpam;
    }

    public function setIsSpam(bool $isSpam): void
    {
        $this->isSpam = $isSpam;
    }
}
