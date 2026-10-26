<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\QuoteRequest;
use App\Entity\QuoteAttachment;
use App\Service\CsrfRequestGuard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/quotes', name: 'api_admin_quotes_')]
class AdminQuoteController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CsrfRequestGuard $csrfRequestGuard,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    )
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $rows = $this->entityManager->getRepository(QuoteRequest::class)->findBy([], ['createdAt' => 'DESC']);

        return $this->json(array_map(fn (QuoteRequest $quote) => [
            'id' => $quote->getId(),
            'status' => $quote->getStatus(),
            'propertyType' => $quote->getPropertyType(),
            'requestedServices' => $quote->getRequestedServices(),
            'description' => $quote->getDescription(),
            'city' => $quote->getCity(),
            'postalCode' => $quote->getPostalCode(),
            'budgetRange' => $quote->getBudgetRange(),
            'timeline' => $quote->getTimeline(),
            'visitRequested' => $quote->isVisitRequested(),
            'firstName' => $quote->getFirstName(),
            'lastName' => $quote->getLastName(),
            'email' => $quote->getEmail(),
            'phone' => $quote->getPhone(),
            'callbackTime' => $quote->getCallbackTime(),
            'createdAt' => $quote->getCreatedAt()->format(DATE_ATOM),
            'isSpam' => $quote->isSpam(),
            'attachments' => array_map(static fn (QuoteAttachment $attachment) => [
                'id' => $attachment->getId(), 'originalName' => $attachment->getOriginalName(),
            ], $this->entityManager->getRepository(QuoteAttachment::class)->findBy(['quoteRequest' => $quote])),
        ], $rows));
    }

    #[Route('/{id}/status', name: 'update_status', methods: ['PATCH'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        if (!$this->csrfRequestGuard->isValid($request)) {
            return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
        }

        $quote = $this->entityManager->getRepository(QuoteRequest::class)->find($id);
        if (!$quote instanceof QuoteRequest) {
            return $this->json(['message' => 'Demande introuvable.'], 404);
        }

        $payload = json_decode($request->getContent(), true);
        $status = (string) ($payload['status'] ?? '');
        $allowed = ['new', 'contacted', 'visit_planned', 'quote_sent', 'won', 'lost', 'archived'];

        if (!in_array($status, $allowed, true)) {
            return $this->json(['message' => 'Statut invalide.'], 422);
        }

        $quote->setStatus($status);
        $this->entityManager->flush();

        return $this->json(['message' => 'Statut mis a jour.']);
    }

    #[Route('/{quoteId}/attachments/{attachmentId}', name: 'attachment', methods: ['GET'])]
    public function attachment(int $quoteId, int $attachmentId): BinaryFileResponse|JsonResponse
    {
        $attachment = $this->entityManager->getRepository(QuoteAttachment::class)->find($attachmentId);
        if (!$attachment instanceof QuoteAttachment || $attachment->getQuoteRequest()->getId() !== $quoteId) {
            return $this->json(['message' => 'Piece jointe introuvable.'], 404);
        }

        $path = $this->projectDir . '/' . $attachment->getStoragePath();
        if (!is_file($path)) {
            return $this->json(['message' => 'Fichier indisponible.'], 404);
        }

        return new BinaryFileResponse($path, 200, [
            'Content-Type' => $attachment->getMimeType(),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
