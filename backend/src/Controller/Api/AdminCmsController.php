<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\{CmsDocument, RealisationCategory, Service};
use App\Service\{CmsCatalog, CsrfRequestGuard};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/cms', name: 'api_admin_cms_')]
class AdminCmsController extends AbstractController
{
    public function __construct(
        private readonly CmsCatalog $catalog,
        private readonly EntityManagerInterface $em,
        private readonly CsrfRequestGuard $guard,
    ) {
    }

    #[Route('/schema', name: 'schema', methods: ['GET'])]
    public function schema(): JsonResponse
    {
        $definitions = $this->catalog->definitions();
        foreach ($definitions as &$definition) {
            unset($definition['class']);
        }
        return $this->json([
            'resources' => $definitions, 'blocks' => CmsCatalog::BLOCKS,
            'photoSlots' => array_map(fn ($row) => $this->catalog->serialize('photos', $row), $this->catalog->rows('photos')),
        ]);
    }

    #[Route('/{resource}', name: 'list', methods: ['GET'])]
    public function index(string $resource): JsonResponse
    {
        try {
            return $this->json(array_map(fn ($row) => $this->catalog->serialize($resource, $row), $this->catalog->rows($resource)));
        } catch (\InvalidArgumentException $error) {
            return $this->json(['message' => $error->getMessage()], 404);
        }
    }

    #[Route('/{resource}/order', name: 'order', methods: ['PUT'])]
    public function order(string $resource, Request $request): JsonResponse
    {
        if (!$this->guard->isValid($request)) {
            return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
        }
        try {
            $def = $this->catalog->definition($resource);
            if ($def['fixed'] ?? false) {
                throw new \InvalidArgumentException('Ces emplacements ne peuvent pas être réordonnés.');
            }
            $ids = $request->toArray()['ids'] ?? null;
            $rows = $this->catalog->rows($resource);
            $expected = array_map(static fn ($row) => $row->getId(), $rows);
            $sorted = is_array($ids) ? $ids : [];
            sort($sorted);
            sort($expected);
            if (!is_array($ids) || !array_is_list($ids) || $sorted !== $expected) {
                throw new \InvalidArgumentException('L’ordre doit contenir chaque élément une seule fois. Rechargez la liste.');
            }
            foreach ($rows as $row) {
                $row->setSortOrder(array_search($row->getId(), $ids, true));
            }
            $this->em->flush();
            return $this->json(['message' => 'Ordre enregistré.']);
        } catch (\InvalidArgumentException $error) {
            return $this->json(['message' => $error->getMessage()], 422);
        }
    }

    #[Route('/{resource}', name: 'create', methods: ['POST'])]
    #[Route('/{resource}/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function save(string $resource, Request $request, ?int $id = null): JsonResponse
    {
        if (!$this->guard->isValid($request)) {
            return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
        }
        try {
            $def = $this->catalog->definition($resource);
            if (!$id && ($def['fixed'] ?? false)) {
                throw new \InvalidArgumentException('Les emplacements sont prédéfinis. Ajoutez des photos dans les sections des pages.');
            }
            $row = $id ? $this->catalog->find($resource, $id) : new ($def['class'])();
            if (!$row) {
                return $this->json(['message' => 'Élément introuvable.'], 404);
            }
            $this->catalog->apply($resource, $row, $request->toArray());
            if (!$id) {
                $rows = $this->catalog->rows($resource);
                $row->setSortOrder($rows === [] ? 0 : max(array_map(static fn ($item) => $item->getSortOrder(), $rows)) + 1);
                $this->em->persist($row);
            }
            $this->em->flush();
            return $this->json($this->catalog->serialize($resource, $row), $id ? 200 : 201);
        } catch (\InvalidArgumentException $error) {
            return $this->json(['message' => $error->getMessage()], 422);
        }
    }

    #[Route('/{resource}/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(string $resource, int $id, Request $request): JsonResponse
    {
        if (!$this->guard->isValid($request)) {
            return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
        }
        try {
            $def = $this->catalog->definition($resource);
            if ($def['fixed'] ?? false) {
                throw new \InvalidArgumentException('Retirez la photo de cet emplacement plutôt que de supprimer son repère.');
            }
            $row = $this->catalog->find($resource, $id);
            if (!$row) {
                return $this->json(['message' => 'Élément introuvable.'], 404);
            }
            if ($row instanceof Service) {
                foreach ($this->em->getRepository(RealisationCategory::class)->findBy(['tagService' => $row]) as $category) {
                    $category->setTagService(null);
                }
            }
            $this->em->remove($row);
            $this->em->flush();
            return $this->json(['message' => 'Élément supprimé définitivement. Les photos restent dans la médiathèque.']);
        } catch (\InvalidArgumentException $error) {
            return $this->json(['message' => $error->getMessage()], 422);
        }
    }
}
