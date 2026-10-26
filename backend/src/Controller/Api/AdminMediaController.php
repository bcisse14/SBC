<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\MediaAsset;
use App\Service\CsrfRequestGuard;
use App\Service\MediaStorage;
use App\Service\MediaUsage;
use App\Service\TextNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/media', name: 'api_admin_media_')]
class AdminMediaController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CsrfRequestGuard $csrfRequestGuard,
        private readonly MediaStorage $mediaStorage,
        private readonly TextNormalizer $textNormalizer,
        private readonly MediaUsage $mediaUsage,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $assets = $this->entityManager->getRepository(MediaAsset::class)->findBy([], ['createdAt' => 'DESC']);

        return $this->json(array_map($this->serialize(...), $assets));
    }

    #[Route('', name: 'upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        if (!$this->csrfRequestGuard->isValid($request)) {
            return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
        }

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return $this->json(['message' => 'Fichier manquant.'], 422);
        }

        $alt = trim((string) $request->request->get('altText', ''));
        if ($alt === '') {
            $alt = $this->altFromFilename($file->getClientOriginalName());
        }
        if (mb_strlen($alt) > 255 || mb_strlen($file->getClientOriginalName()) > 255) {
            return $this->json(['message' => 'Le nom du fichier et sa description doivent contenir au maximum 255 caractères.'], 422);
        }

        try {
            $stored = $this->mediaStorage->store($file, $this->textNormalizer->normalize($alt)['value']);
        } catch (\ImagickException) {
            return $this->json(['message' => 'Cette image est illisible ou endommagée. Choisissez un autre fichier.'], 422);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            return $this->json(['message' => $exception->getMessage()], 422);
        }

        $asset = new MediaAsset();
        $asset->setPath($stored['path']);
        $asset->setOriginalName($this->textNormalizer->normalize($stored['originalName'])['value']);
        $asset->setMimeType($stored['mimeType']);
        $asset->setAltText($stored['altText']);
        $asset->setWidth($stored['width']);
        $asset->setHeight($stored['height']);
        $asset->setVariants($stored['variants']);
        $this->entityManager->persist($asset);
        $this->entityManager->flush();

        return $this->json($this->serialize($asset), 201);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        if (!$this->csrfRequestGuard->isValid($request)) {
            return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
        }

        $asset = $this->entityManager->getRepository(MediaAsset::class)->find($id);
        if (!$asset instanceof MediaAsset) {
            return $this->json(['message' => 'Média introuvable.'], 404);
        }

        $payload = json_decode($request->getContent(), true) ?: [];
        $alt = trim((string) ($payload['altText'] ?? ''));
        if ($alt === '' || mb_strlen($alt) > 255) {
            return $this->json(['message' => 'La description doit contenir entre 1 et 255 caractères.'], 422);
        }

        $asset->setAltText($this->textNormalizer->normalize($alt)['value']);
        $this->entityManager->flush();

        return $this->json($this->serialize($asset));
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        if (!$this->csrfRequestGuard->isValid($request)) {
            return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
        }
        $asset = $this->entityManager->find(MediaAsset::class, $id);
        if (!$asset) {
            return $this->json(['message' => 'Média introuvable.'], 404);
        }
        $usage = $this->mediaUsage->references($id);
        if ($usage !== []) {
            return $this->json(['message' => 'Retirez d’abord ce média de : ' . implode(', ', $usage), 'usage' => $usage], 409);
        }
        try {
            $this->mediaStorage->deleteFiles($asset->getPath(), $asset->getVariants());
        } catch (\RuntimeException $error) {
            return $this->json(['message' => $error->getMessage()], 500);
        }
        $this->entityManager->remove($asset);
        $this->entityManager->flush();
        return $this->json(['message' => 'Média et fichiers supprimés définitivement.']);
    }

    /** @return array<string, mixed> */
    private function serialize(MediaAsset $asset): array
    {
        return [
            'id' => $asset->getId(),
            'originalName' => $asset->getOriginalName(),
            'mimeType' => $asset->getMimeType(),
            'altText' => $asset->getAltText(),
            'width' => $asset->getWidth(),
            'height' => $asset->getHeight(),
            'url' => '/api/media/' . $asset->getId() . '?v=' . substr(hash('sha256', $asset->getPath()), 0, 12),
            'usage' => $this->mediaUsage->references($asset->getId()),
        ];
    }

    private function altFromFilename(string $name): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);

        return ucfirst(trim((string) preg_replace('/[-_\s]+/', ' ', $base)));
    }
}
