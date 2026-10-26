<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\QuoteRequest;
use App\Entity\QuoteAttachment;
use App\Service\CsrfRequestGuard;
use App\Service\QuoteAttachmentStorage;
use App\Service\QuoteMailer;
use App\Service\TextNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/quotes', name: 'api_quotes_')]
class QuoteController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly QuoteMailer $quoteMailer,
        #[Autowire(service: 'limiter.quote')]
        private readonly RateLimiterFactory $quoteLimiter,
        private readonly CsrfRequestGuard $csrfRequestGuard,
        private readonly QuoteAttachmentStorage $attachmentStorage,
        private readonly TextNormalizer $textNormalizer,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $limiter = $this->quoteLimiter->create($request->getClientIp() ?? 'unknown');
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['message' => 'Trop de tentatives, veuillez patienter.'], 429);
        }

        if (!$this->csrfRequestGuard->isValid($request, 'quote')) {
            return $this->json(['message' => 'Session de formulaire expiree, veuillez reessayer.'], 419);
        }

        if (str_starts_with((string) $request->headers->get('Content-Type'), 'multipart/form-data')) {
            $payload = $request->request->all();
            $payload['requestedServices'] = $this->decodeServices($payload['requestedServices'] ?? []);
            $payload['consent'] = filter_var($payload['consent'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $payload['visitRequested'] = filter_var($payload['visitRequested'] ?? false, FILTER_VALIDATE_BOOLEAN);
        } else {
            try {
                $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return $this->json(['message' => 'Charge utile invalide.'], 400);
            }
        }

        $attachments = $request->files->all('photos');
        if (count($attachments) > 5) {
            return $this->json(['message' => 'Cinq photos maximum sont autorisees.'], 422);
        }

        if (!empty($payload['website'])) {
            return $this->json(['message' => 'Demande enregistrée.'], 202);
        }

        $input = [
            'propertyType' => $payload['propertyType'] ?? null,
            'requestedServices' => $payload['requestedServices'] ?? [],
            'description' => $payload['description'] ?? null,
            'city' => $payload['city'] ?? null,
            'postalCode' => $payload['postalCode'] ?? null,
            'budgetRange' => $payload['budgetRange'] ?? null,
            'timeline' => $payload['timeline'] ?? null,
            'firstName' => $payload['firstName'] ?? null,
            'lastName' => $payload['lastName'] ?? null,
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'consent' => $payload['consent'] ?? false,
        ];

        $constraints = new Assert\Collection([
            'propertyType' => [new Assert\NotBlank()],
            'requestedServices' => [new Assert\Count(min: 1)],
            'description' => [new Assert\NotBlank(), new Assert\Length(min: 20)],
            'city' => [new Assert\NotBlank()],
            'postalCode' => [new Assert\Regex('/^[0-9]{5}$/')],
            'budgetRange' => [new Assert\NotBlank()],
            'timeline' => [new Assert\NotBlank()],
            'firstName' => [new Assert\NotBlank()],
            'lastName' => [new Assert\NotBlank()],
            'email' => [new Assert\NotBlank(), new Assert\Email()],
            'phone' => [new Assert\NotBlank()],
            'consent' => [new Assert\IsTrue(message: 'Le consentement est obligatoire.')],
        ]);

        $violations = $this->validator->validate($input, $constraints);
        if (count($violations) > 0) {
            return $this->json(['message' => 'Le formulaire contient des erreurs.'], 422);
        }

        $normalizedDescription = $this->textNormalizer->normalize((string) $input['description']);

        $quote = new QuoteRequest();
        $quote->setPropertyType((string) $input['propertyType']);
        $quote->setRequestedServices((array) $input['requestedServices']);
        $quote->setDescription($normalizedDescription['value']);
        $quote->setCity((string) $input['city']);
        $quote->setPostalCode((string) $input['postalCode']);
        $quote->setBudgetRange((string) $input['budgetRange']);
        $quote->setTimeline((string) $input['timeline']);
        $quote->setVisitRequested((bool) ($payload['visitRequested'] ?? false));
        $quote->setFirstName((string) $input['firstName']);
        $quote->setLastName((string) $input['lastName']);
        $quote->setEmail((string) $input['email']);
        $quote->setPhone((string) $input['phone']);
        $quote->setCallbackTime($payload['callbackTime'] ?? null);

        $this->entityManager->persist($quote);
        $this->entityManager->flush();

        foreach ($attachments as $attachment) {
            if (!$attachment instanceof UploadedFile) {
                return $this->json(['message' => 'Piece jointe invalide.'], 422);
            }

            try {
                $stored = $this->attachmentStorage->store($attachment);
            } catch (\InvalidArgumentException|\RuntimeException $exception) {
                return $this->json(['message' => $exception->getMessage()], 422);
            }

            $quoteAttachment = new QuoteAttachment();
            $quoteAttachment->setQuoteRequest($quote);
            $quoteAttachment->setStoragePath($stored['storagePath']);
            $quoteAttachment->setOriginalName($stored['originalName']);
            $quoteAttachment->setMimeType($stored['mimeType']);
            $quoteAttachment->setSizeBytes($stored['sizeBytes']);
            $this->entityManager->persist($quoteAttachment);
        }
        $this->entityManager->flush();

        $this->quoteMailer->sendForQuote($quote, count($attachments));

        return $this->json([
            'message' => $normalizedDescription['replacedDash']
                ? 'Demande envoyée, tiret cadratin remplacé automatiquement.'
                : 'Demande envoyée. Un e-mail de confirmation vous a été adressé.',
            'id' => $quote->getId(),
        ], 201);
    }

    private function decodeServices(mixed $services): array
    {
        if (is_array($services)) {
            return array_values(array_filter(array_map('strval', $services)));
        }

        if (is_string($services)) {
            $decoded = json_decode($services, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('strval', $decoded)));
            }

            return array_values(array_filter(array_map('trim', explode(',', $services))));
        }

        return [];
    }
}
