<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\ContentEntry;
use App\Entity\ContentRevision;
use App\Entity\Service;
use App\Entity\Setting;
use App\Entity\User;
use App\Service\CsrfRequestGuard;
use App\Service\CmsCatalog;
use App\Service\TextNormalizer;
use App\Service\SiteEditor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin', name: 'api_admin_content_')]
class AdminContentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CsrfRequestGuard $csrfRequestGuard,
        private readonly TextNormalizer $textNormalizer,
        private readonly CmsCatalog $cms,
        private readonly SiteEditor $siteEditor,
    ) {
    }

    #[Route('/content', name: 'content_index', methods: ['GET'])]
    public function listContent(): JsonResponse
    {
        $rows = $this->entityManager->getRepository(ContentEntry::class)->findBy([], ['contentKey' => 'ASC']);

        return $this->json(array_map(static fn (ContentEntry $row) => [
            'id' => $row->getId(),
            'contentKey' => $row->getContentKey(),
            'defaultValue' => $row->getDefaultValue(),
            'customValue' => $row->getCustomValue(),
            'status' => $row->getStatus(),
        ], $rows));
    }

    #[Route('/content/{id}', name: 'content_update', methods: ['PATCH'])]
    public function updateContent(int $id, Request $request): JsonResponse
    {
        if (!$this->csrfRequestGuard->isValid($request)) {
            return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
        }

        $entry = $this->entityManager->getRepository(ContentEntry::class)->find($id);
        if (!$entry instanceof ContentEntry) {
            return $this->json(['message' => 'Contenu introuvable.'], 404);
        }

        $payload = $request->toArray();
        $incoming = $payload['customValue'] ?? null;
        if (!is_string($incoming)) {
            return $this->json(['message' => 'Le contenu doit être un texte.'], 422);
        }
        $normalized = $this->textNormalizer->normalize($incoming);

        $entry->setCustomValue($normalized['value']);

        $revision = new ContentRevision();
        $revision->setEntry($entry);
        $revision->setValue($normalized['value']);
        $editor = $this->getUser();
        if ($editor instanceof User) {
            $revision->setEditor($editor);
        }

        $this->entityManager->persist($revision);
        $this->entityManager->flush();

        return $this->json([
            'value' => $normalized['value'],
            'message' => $normalized['replacedDash']
                ? 'Enregistre. Tiret cadratin remplace.'
                : 'Enregistre.',
        ]);
    }

    #[Route('/settings', name: 'settings_index', methods: ['GET'])]
    public function listSettings(): JsonResponse
    {
        $rows = $this->entityManager->getRepository(Setting::class)->findBy([], ['settingKey' => 'ASC']);

        $rows = array_values(array_filter($rows, static fn (Setting $row) => !str_starts_with($row->getSettingKey(), '_')));
        return $this->json(array_map(static fn (Setting $row) => [
            'id' => $row->getId(),
            'settingKey' => $row->getSettingKey(),
            'settingValue' => $row->getSettingValue(),
        ], $rows));
    }

    #[Route('/settings/{id}', name: 'settings_update', methods: ['PATCH'])]
    public function updateSetting(int $id, Request $request): JsonResponse
    {
        if (!$this->csrfRequestGuard->isValid($request)) {
            return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
        }

        $setting = $this->entityManager->getRepository(Setting::class)->find($id);
        if (!$setting instanceof Setting) {
            return $this->json(['message' => 'Reglage introuvable.'], 404);
        }

        $payload = $request->toArray();
        $incoming = $payload['settingValue'] ?? null;
        if (!is_string($incoming)) {
            return $this->json(['message' => 'Le réglage doit être un texte.'], 422);
        }
        try {
            $value = $this->cms->normalizeSettingValue($setting->getSettingKey(), $incoming);
            if (isset($this->siteEditor->fields()[$setting->getSettingKey()])) {
                $this->siteEditor->save([$setting->getSettingKey() => $incoming]);
            }
        } catch (\InvalidArgumentException $error) {
            return $this->json(['message' => $error->getMessage()], 422);
        }
        $setting->setSettingValue($value);

        $this->entityManager->flush();

        return $this->json([
            'value' => $value,
            'message' => $value !== $incoming
                ? 'Reglage enregistre, tiret cadratin remplace.'
                : 'Reglage enregistre.',
        ]);
    }

    #[Route('/services', name: 'services_index', methods: ['GET'])]
    public function listServices(): JsonResponse
    {
        $rows = $this->entityManager->getRepository(Service::class)->findBy([], ['sortOrder' => 'ASC']);

        return $this->json(array_map(static fn (Service $row) => [
            'id' => $row->getId(),
            'name' => $row->getName(),
            'serviceGroup' => $row->getServiceGroup(),
            'description' => $row->getDescription(),
            'items' => $row->getItems(),
            'isPublished' => $row->isPublished(),
            'sortOrder' => $row->getSortOrder(),
        ], $rows));
    }
}
