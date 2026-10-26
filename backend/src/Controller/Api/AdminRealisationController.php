<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\MediaAsset;
use App\Entity\RealisationCategory;
use App\Entity\Service;
use App\Service\CsrfRequestGuard;
use App\Service\TextNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/api/admin/realisations', name: 'api_admin_realisations_')]
class AdminRealisationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CsrfRequestGuard $csrfRequestGuard,
        private readonly TextNormalizer $textNormalizer,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $rows = $this->entityManager->getRepository(RealisationCategory::class)->findBy([], ['sortOrder' => 'ASC']);

        return $this->json(array_map($this->serialize(...), $rows));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        if (!$this->csrfRequestGuard->isValid($request)) {
            return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
        }

        $category = new RealisationCategory();
        $category->setSortOrder(count($this->entityManager->getRepository(RealisationCategory::class)->findAll()) + 1);
        $error = $this->apply($category, json_decode($request->getContent(), true) ?: []);
        if ($error !== null) {
            return $this->json(['message' => $error], 422);
        }

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $this->json($this->serialize($category), 201);
    }

    #[Route('/order', name: 'order', methods: ['PUT'])]
    public function order(Request $request): JsonResponse
    {
        if (!$this->csrfRequestGuard->isValid($request)) {
            return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
        }

        $ids = (json_decode($request->getContent(), true) ?: [])['ids'] ?? [];
        $repository = $this->entityManager->getRepository(RealisationCategory::class);
        foreach (array_values((array) $ids) as $position => $id) {
            $category = $repository->find((int) $id);
            if ($category instanceof RealisationCategory) {
                $category->setSortOrder($position + 1);
            }
        }
        $this->entityManager->flush();

        return $this->json(['message' => 'Ordre enregistré.']);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        if (!$this->csrfRequestGuard->isValid($request)) {
            return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
        }

        $category = $this->entityManager->getRepository(RealisationCategory::class)->find($id);
        if (!$category instanceof RealisationCategory) {
            return $this->json(['message' => 'Catégorie introuvable.'], 404);
        }

        $error = $this->apply($category, json_decode($request->getContent(), true) ?: []);
        if ($error !== null) {
            return $this->json(['message' => $error], 422);
        }
        $this->entityManager->flush();

        return $this->json($this->serialize($category));
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        if (!$this->csrfRequestGuard->isValid($request)) {
            return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
        }

        $category = $this->entityManager->getRepository(RealisationCategory::class)->find($id);
        if ($category instanceof RealisationCategory) {
            $this->entityManager->remove($category);
            $this->entityManager->flush();
        }

        return $this->json(['message' => 'Catégorie supprimée.']);
    }

    /** @param array<string, mixed> $data */
    private function apply(RealisationCategory $category, array $data): ?string
    {
        if (array_key_exists('title', $data)) {
            $title = trim($this->textNormalizer->normalize((string) $data['title'])['value']);
            if ($title === '') {
                return 'Le titre est obligatoire.';
            }
            $category->setTitle($title);
            if ($category->getId() === null) {
                $category->setSlug($this->uniqueSlug($title));
            }
        }

        if (array_key_exists('description', $data)) {
            $category->setDescription($this->textNormalizer->normalize((string) $data['description'])['value']);
        }

        if (array_key_exists('status', $data)) {
            if (!in_array($data['status'], ['draft', 'published'], true)) {
                return 'Statut invalide.';
            }
            $category->setStatus((string) $data['status']);
        }

        if (array_key_exists('serviceId', $data)) {
            $service = $data['serviceId'] ? $this->entityManager->getRepository(Service::class)->find((int) $data['serviceId']) : null;
            $category->setTagService($service instanceof Service ? $service : null);
        }

        if (array_key_exists('gallery', $data)) {
            $ids = array_values(array_unique(array_map('intval', (array) $data['gallery'])));
            $existing = array_map(
                static fn (MediaAsset $asset) => $asset->getId(),
                $ids === [] ? [] : $this->entityManager->getRepository(MediaAsset::class)->findBy(['id' => $ids]),
            );
            $category->setGallery(array_values(array_intersect($ids, $existing)));
        }

        return null;
    }

    private function uniqueSlug(string $title): string
    {
        $base = (new AsciiSlugger('fr'))->slug($title)->lower()->toString() ?: 'categorie';
        $slug = $base;
        $repository = $this->entityManager->getRepository(RealisationCategory::class);
        for ($i = 2; $repository->findOneBy(['slug' => $slug]) !== null; ++$i) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }

    /** @return array<string, mixed> */
    private function serialize(RealisationCategory $category): array
    {
        return [
            'id' => $category->getId(),
            'title' => $category->getTitle(),
            'slug' => $category->getSlug(),
            'description' => $category->getDescription(),
            'serviceId' => $category->getTagService()?->getId(),
            'gallery' => $category->getGallery(),
            'sortOrder' => $category->getSortOrder(),
            'status' => $category->getStatus(),
        ];
    }
}
