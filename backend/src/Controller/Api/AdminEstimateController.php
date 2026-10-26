<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\{Estimate, EstimateProfile, QuoteRequest};
use App\Service\{CsrfRequestGuard, EstimateEditor};
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\{Dompdf, Options};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/estimates', name: 'api_admin_estimates_')]
class AdminEstimateController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EstimateEditor $editor,
        private readonly CsrfRequestGuard $guard,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json(array_map($this->editor->serialize(...), $this->em->getRepository(Estimate::class)->findBy([], ['id' => 'DESC'])));
    }

    #[Route('/seed', name: 'seed', methods: ['GET'])]
    public function seed(Request $request): JsonResponse
    {
        $id = $request->query->get('requestId');
        $quote = $id ? $this->em->find(QuoteRequest::class, $id) : null;
        if ($id && !$quote) return $this->json(['message' => 'Demande introuvable.'], 404);
        return $this->json($this->editor->initial($quote));
    }

    #[Route('/profiles', name: 'profiles', methods: ['GET'])]
    public function profiles(): JsonResponse
    {
        return $this->json(array_map($this->profileRow(...), $this->em->getRepository(EstimateProfile::class)->findBy([], ['name' => 'ASC'])));
    }

    #[Route('/profiles', name: 'profile_create', methods: ['POST'])]
    #[Route('/profiles/{id<\d+>}', name: 'profile_update', methods: ['PUT'])]
    public function saveProfile(Request $request, ?int $id = null): JsonResponse
    {
        if (!$this->guard->isValid($request)) return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
        $profile = $id ? $this->em->find(EstimateProfile::class, $id) : new EstimateProfile();
        if (!$profile) return $this->json(['message' => 'Fiche introuvable.'], 404);
        $input = $request->toArray();
        try {
            if (array_diff(array_keys($input), ['kind', 'data'])) throw new \InvalidArgumentException('Champ de fiche inconnu.');
            $kind = $input['kind'] ?? null;
            if (!in_array($kind, ['company', 'client'], true) || ($id && $profile->getKind() !== $kind)) throw new \InvalidArgumentException('Type de fiche invalide.');
            if (!isset($input['data']) || !is_array($input['data'])) throw new \InvalidArgumentException('Coordonnées invalides.');
            $data = $this->editor->profile($input['data'], $kind);
            if ($data['name'] === '') throw new \InvalidArgumentException('Le nom est obligatoire pour enregistrer une fiche.');
        } catch (\InvalidArgumentException $error) {
            return $this->json(['message' => $error->getMessage()], 422);
        }
        $profile->setKind($kind);
        $profile->setName($data['name']);
        $profile->setData($data);
        $this->em->persist($profile);
        $this->em->flush();
        return $this->json($this->profileRow($profile), $id ? 200 : 201);
    }

    #[Route('/profiles/{id<\d+>}', name: 'profile_delete', methods: ['DELETE'])]
    public function deleteProfile(int $id, Request $request): JsonResponse
    {
        if (!$this->guard->isValid($request)) return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
        $profile = $this->em->find(EstimateProfile::class, $id);
        if (!$profile) return $this->json(['message' => 'Fiche introuvable.'], 404);
        $this->em->remove($profile);
        $this->em->flush();
        return $this->json(['message' => 'Fiche supprimée. Les devis existants sont conservés.']);
    }

    #[Route('/calculate', name: 'calculate', methods: ['POST'])]
    public function calculate(Request $request): JsonResponse
    {
        if (!$this->guard->isValid($request)) return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
        try {
            $data = $this->editor->validate($request->toArray());
            return $this->json(['totals' => $this->editor->totals($data), 'missing' => $this->editor->missing($data)]);
        } catch (\InvalidArgumentException $error) {
            return $this->json(['message' => $error->getMessage()], 422);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[Route('/{id<\d+>}', name: 'update', methods: ['PUT'])]
    public function save(Request $request, ?int $id = null): JsonResponse
    {
        if (!$this->guard->isValid($request)) return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
        $estimate = $id ? $this->em->find(Estimate::class, $id) : new Estimate();
        if (!$estimate) return $this->json(['message' => 'Devis introuvable.'], 404);
        $input = $request->toArray();
        try {
            if (array_diff(array_keys($input), ['requestId', 'data'])) throw new \InvalidArgumentException('Champ de devis inconnu.');
            if (!isset($input['data']) || !is_array($input['data'])) throw new \InvalidArgumentException('Contenu du devis invalide.');
            $data = $this->editor->validate($input['data']);
            $requestId = $input['requestId'] ?? null;
            if ($requestId !== null && (!is_int($requestId) || $requestId < 1)) throw new \InvalidArgumentException('Référence de demande invalide.');
            if ($id && $estimate->getRequest()?->getId() !== $requestId) throw new \InvalidArgumentException('La demande liée ne peut pas être changée.');
            $quote = $requestId ? $this->em->find(QuoteRequest::class, $requestId) : null;
            if ($requestId && !$quote) throw new \InvalidArgumentException('Demande liée introuvable.');
        } catch (\InvalidArgumentException $error) {
            return $this->json(['message' => $error->getMessage()], 422);
        }
        $estimate->setRequest($quote);
        $estimate->setData($data);
        $this->em->persist($estimate);
        $this->em->flush();
        return $this->json($this->editor->serialize($estimate), $id ? 200 : 201);
    }

    #[Route('/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        if (!$this->guard->isValid($request)) return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
        $estimate = $this->em->find(Estimate::class, $id);
        if (!$estimate) return $this->json(['message' => 'Devis introuvable.'], 404);
        $this->em->remove($estimate);
        $this->em->flush();
        return $this->json(['message' => 'Devis supprimé.']);
    }

    #[Route('/{id<\d+>}/pdf', name: 'pdf', methods: ['GET'])]
    public function pdf(int $id): Response
    {
        $estimate = $this->em->find(Estimate::class, $id);
        if (!$estimate) return $this->json(['message' => 'Devis introuvable.'], 404);
        $data = $estimate->getData();
        $missing = $this->editor->missing($data);
        if ($missing) return $this->json(['message' => 'Complétez avant export : '.implode(', ', $missing).'.'], 422);
        $options = new Options();
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setIsJavascriptEnabled(false);
        $options->setDefaultFont('DejaVu Sans');
        $pdf = new Dompdf($options);
        $pdf->loadHtml($this->renderView('estimate/document.html.twig', [
            'estimate' => $estimate, 'data' => $data, 'totals' => $this->editor->totals($data),
        ]));
        $pdf->setPaper('A4');
        $pdf->render();
        $pdf->getCanvas()->page_text(480, 815, '{PAGE_NUM} / {PAGE_COUNT}', null, 8);
        return new Response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$estimate->getNumber().'.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function profileRow(EstimateProfile $profile): array
    {
        return ['id' => $profile->getId(), 'kind' => $profile->getKind(), 'data' => $profile->getData()];
    }
}
