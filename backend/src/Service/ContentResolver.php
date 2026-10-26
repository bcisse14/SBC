<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ContentEntry;
use Doctrine\ORM\EntityManagerInterface;

class ContentResolver
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function mapByKey(): array
    {
        $entries = $this->entityManager->getRepository(ContentEntry::class)->findAll();
        $data = [];

        foreach ($entries as $entry) {
            $data[$entry->getContentKey()] = $entry->getResolvedValue();
        }

        return $data;
    }
}
