<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\MediaAsset;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/media', name: 'api_media_')]
class MediaController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(int $id, Request $request): BinaryFileResponse|JsonResponse
    {
        $asset = $this->entityManager->getRepository(MediaAsset::class)->find($id);
        if (!$asset instanceof MediaAsset) {
            return $this->json(['message' => 'Média introuvable.'], 404);
        }

        $relative = $asset->getPath();
        $mime = $asset->getMimeType();

        $width = $request->query->getInt('w');
        $format = (string) $request->query->get('f', '');
        if ($width > 0 && in_array($format, ['webp', 'avif'], true)) {
            foreach ($asset->getVariants() as $variant) {
                if ($variant['w'] === $width && $variant['format'] === $format) {
                    $relative = $variant['path'];
                    $mime = 'image/' . $format;
                    break;
                }
            }
        }

        $root = realpath($this->projectDir . '/var/share/media');
        $path = realpath($this->projectDir . '/' . ltrim($relative, '/'));
        if ($root === false || $path === false || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)) {
            return $this->json(['message' => 'Média indisponible.'], 404);
        }

        return new BinaryFileResponse($path, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
