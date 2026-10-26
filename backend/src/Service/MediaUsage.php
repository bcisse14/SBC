<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\{Certification, CmsDocument, Partner, PhotoSlot, RealisationCategory, Service, Setting};
use Doctrine\ORM\EntityManagerInterface;

class MediaUsage
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function references(int $id): array
    {
        $references = [];
        foreach (['brand_logo_id' => 'Logo du site', 'brand_icon_id' => 'Icône du navigateur'] as $key => $label) {
            if ($this->em->getRepository(Setting::class)->findOneBy(['settingKey' => $key])?->getSettingValue() === (string) $id) {
                $references[] = $label;
            }
        }
        foreach ([Service::class => 'mediaAssetId', PhotoSlot::class => 'mediaAssetId', Partner::class => 'logoMediaId', Certification::class => 'logoMediaId'] as $class => $field) {
            foreach ($this->em->getRepository($class)->findBy([$field => $id]) as $row) {
                $references[] = $row instanceof PhotoSlot ? 'Emplacement : ' . $row->getSlotKey() : $row->getName();
            }
        }
        foreach ($this->em->getRepository(RealisationCategory::class)->findAll() as $category) {
            if (in_array($id, $category->getGallery(), true)) {
                $references[] = 'Réalisation : ' . $category->getTitle();
            }
        }
        foreach ($this->em->getRepository(CmsDocument::class)->findAll() as $document) {
            $data = $document->getData();
            $used = ($data['mediaAssetId'] ?? null) === $id;
            foreach ($data['sections'] ?? [] as $section) {
                $used = $used || ($section['mediaAssetId'] ?? null) === $id || in_array($id, $section['gallery'] ?? [], true);
            }
            if ($used) {
                $references[] = ($document->getResource() === 'pages' ? 'Page : ' : 'Annonce : ') . ($data['title'] ?? $document->getSlug());
            }
        }
        return array_values(array_unique($references));
    }
}
