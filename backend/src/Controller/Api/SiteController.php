<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Certification;
use App\Entity\FaqItem;
use App\Entity\Partner;
use App\Entity\PhotoSlot;
use App\Entity\MediaAsset;
use App\Entity\RealisationCategory;
use App\Entity\Review;
use App\Entity\Service;
use App\Service\ContentResolver;
use App\Service\CmsCatalog;
use App\Service\SiteEditor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/site', name: 'api_site_')]
class SiteController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContentResolver $contentResolver,
        private readonly CmsCatalog $cms,
        private readonly SiteEditor $editor,
    ) {
    }

    #[Route('/bootstrap', name: 'bootstrap', methods: ['GET'])]
    public function bootstrap(): JsonResponse
    {
        $settings = $this->editor->values();

        $services = $this->entityManager->getRepository(Service::class)->findBy(['isPublished' => true], ['sortOrder' => 'ASC']);
        $categories = $this->entityManager->getRepository(RealisationCategory::class)->findBy(['status' => 'published'], ['sortOrder' => 'ASC']);
        $reviews = $this->entityManager->getRepository(Review::class)->findBy(['isPublished' => true], ['sortOrder' => 'ASC', 'id' => 'ASC']);
        $certifications = $this->entityManager->getRepository(Certification::class)->findBy(['isPublished' => true], ['sortOrder' => 'ASC']);
        $partners = $this->entityManager->getRepository(Partner::class)->findBy(['isPublished' => true], ['sortOrder' => 'ASC']);
        $faq = $this->entityManager->getRepository(FaqItem::class)->findBy(['isPublished' => true], ['sortOrder' => 'ASC']);
        $photoSlots = $this->entityManager->getRepository(PhotoSlot::class)->findAll();
        $pages = array_map(fn ($row) => $this->cms->serialize('pages', $row), $this->cms->rows('pages', true));
        $announcements = $this->cms->activeAnnouncements();

        return $this->json([
            'settings' => $settings,
            'pages' => $pages,
            'announcements' => $announcements,
            'content' => $this->contentResolver->mapByKey(),
            'services' => array_map(static fn (Service $service) => [
                'id' => $service->getId(),
                'name' => $service->getName(),
                'group' => $service->getServiceGroup(),
                'description' => $service->getDescription(),
                'items' => $service->getItems(),
                'mediaAssetId' => $service->getMediaAssetId(),
            ], $services),
            'realisationCategories' => array_map(static fn (RealisationCategory $category) => [
                'id' => $category->getId(),
                'title' => $category->getTitle(),
                'slug' => $category->getSlug(),
                'description' => $category->getDescription(),
                'serviceId' => $category->getTagService()?->getId(),
                'serviceGroup' => $category->getTagService()?->getServiceGroup(),
                'serviceName' => $category->getTagService()?->getName(),
                'gallery' => $category->getGallery(),
            ], $categories),
            'media' => $this->mediaPayload($categories, $services, $photoSlots, $certifications, $partners, $pages, $announcements),
            'reviews' => array_map(static fn (Review $review) => [
                'authorName' => $review->getAuthorName(),
                'city' => $review->getCity(),
                'projectType' => $review->getProjectType(),
                'rating' => $review->getRating(),
                'message' => $review->getMessage(),
            ], $reviews),
            'certifications' => array_map(static fn (Certification $certification) => [
                'name' => $certification->getName(),
                'description' => $certification->getDescription(),
                'verificationUrl' => $certification->getVerificationUrl(),
                'logoMediaId' => $certification->getLogoMediaId(),
            ], $certifications),
            'partners' => array_map(static fn (Partner $partner) => [
                'name' => $partner->getName(),
                'category' => $partner->getCategory(),
                'websiteUrl' => $partner->getWebsiteUrl(),
                'logoMediaId' => $partner->getLogoMediaId(),
            ], $partners),
            'faq' => array_map(static fn (FaqItem $item) => [
                'question' => $item->getQuestion(),
                'answer' => $item->getAnswer(),
            ], $faq),
            'photoSlots' => array_map(static fn (PhotoSlot $slot) => [
                'slotKey' => $slot->getSlotKey(),
                'ratio' => $slot->getRatio(),
                'minimumSize' => $slot->getMinimumSize(),
                'idealDescription' => $slot->getIdealDescription(),
                'mediaAssetId' => $slot->getMediaAssetId(),
            ], $photoSlots),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function mediaPayload(array $categories, array $services, array $photoSlots, array $certifications, array $partners, array $pages, array $announcements): array
    {
        $ids = [];
        foreach ($pages as $page) {
            foreach ($page['sections'] as $section) {
                if (!$section['hidden']) {
                    $ids[] = $section['mediaAssetId'];
                    array_push($ids, ...$section['gallery']);
                }
            }
        }
        foreach ($announcements as $announcement) {
            $ids[] = $announcement['mediaAssetId'];
        }
        foreach ($categories as $category) {
            array_push($ids, ...$category->getGallery());
        }
        foreach ($services as $service) {
            $ids[] = $service->getMediaAssetId();
        }
        foreach ($photoSlots as $slot) {
            $ids[] = $slot->getMediaAssetId();
        }
        foreach ([...$certifications, ...$partners] as $item) {
            $ids[] = $item->getLogoMediaId();
        }

        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return [];
        }

        $out = [];
        foreach ($this->entityManager->getRepository(MediaAsset::class)->findBy(['id' => $ids]) as $asset) {
            $out[$asset->getId()] = [
                'id' => $asset->getId(),
                'version' => substr(hash('sha256', $asset->getPath()), 0, 12),
                'alt' => $asset->getAltText() ?? '',
                'width' => $asset->getWidth(),
                'height' => $asset->getHeight(),
                'focalPoint' => $asset->getFocalPoint(),
                'variants' => array_map(static fn (array $v) => ['w' => $v['w'], 'format' => $v['format']], $asset->getVariants()),
            ];
        }

        return $out;
    }
}
