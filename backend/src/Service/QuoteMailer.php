<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\ContentEntry;
use App\Entity\QuoteRequest;
use App\Entity\Setting;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class QuoteMailer
{
    private const BUDGETS = [
        'moins_15k' => 'Moins de 15 000 EUR',
        '15k_40k' => '15 000 EUR à 40 000 EUR',
        '40k_80k' => '40 000 EUR à 80 000 EUR',
        'plus_80k' => 'Plus de 80 000 EUR',
    ];

    private const TIMELINES = [
        'rapide' => 'Dans 1 à 2 mois',
        'moyen' => 'Dans 3 à 6 mois',
        'souple' => 'Calendrier souple',
    ];

    public function __construct(
        #[Autowire(lazy: true)]
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%env(DEFAULT_URI)%')]
        private readonly string $publicUrl,
        #[Autowire('%env(ADMIN_PATH)%')]
        private readonly string $adminPath,
        #[Autowire('%env(NOTIFICATION_EMAIL)%')]
        private readonly string $fallbackRecipient,
        #[Autowire('%env(MAIL_FROM)%')]
        private readonly string $fallbackSender,
    ) {
    }

    /** La demande est déjà enregistrée : un échec ici ne doit jamais la faire perdre. */
    public function sendForQuote(QuoteRequest $quote, int $photoCount): void
    {
        try {
            $from = new Address($this->setting('mail_from') ?: $this->fallbackSender, 'SBC');
            $vars = [
                'prenom' => $quote->getFirstName(),
                'nom' => $quote->getLastName(),
                'ville' => $quote->getCity(),
            ];

            $confirmation = (new TemplatedEmail())
                ->from($from)
                ->to($quote->getEmail())
                ->subject($this->text('email.confirmation.subject', $vars))
                ->htmlTemplate('email/confirmation.html.twig')
                ->context([
                    'intro' => $this->text('email.confirmation.intro', $vars),
                    'outro' => $this->text('email.confirmation.outro', $vars),
                    'summary' => $this->summary($quote),
                ]);
            $confirmation->getHeaders()->addTextHeader('X-Quote-Id', (string) $quote->getId());
            $this->mailer->send($confirmation);

            $recipient = $this->setting('notification_email') ?: $this->fallbackRecipient;
            $notification = (new TemplatedEmail())
                ->from($from)
                ->to($recipient)
                ->replyTo(new Address($quote->getEmail(), trim($quote->getFirstName() . ' ' . $quote->getLastName())))
                ->subject($this->text('email.notification.subject', $vars))
                ->htmlTemplate('email/notification.html.twig')
                ->context([
                    'intro' => $this->text('email.notification.intro', $vars),
                    'summary' => $this->summary($quote),
                    'description' => $quote->getDescription(),
                    'contact' => [
                        'E-mail' => $quote->getEmail(),
                        'Téléphone' => $quote->getPhone(),
                        'Rappel souhaité' => $quote->getCallbackTime() ?: 'Non précisé',
                    ],
                    'photoCount' => $photoCount,
                    'link' => rtrim($this->publicUrl, '/') . '/' . trim($this->adminPath, '/') . '?quote=' . $quote->getId(),
                ]);
            $notification->getHeaders()->addTextHeader('X-Quote-Id', (string) $quote->getId());
            $this->mailer->send($notification);
        } catch (\Throwable $exception) {
            $this->recordFailure($quote, $exception->getMessage());
        }
    }

    /** @return array<string, string> */
    private function summary(QuoteRequest $quote): array
    {
        return [
            'Type de bien' => ucfirst($quote->getPropertyType()),
            'Prestations' => implode(', ', $quote->getRequestedServices()),
            'Localisation' => $quote->getPostalCode() . ' ' . $quote->getCity(),
            'Budget indicatif' => self::BUDGETS[$quote->getBudgetRange()] ?? $quote->getBudgetRange(),
            'Délai souhaité' => self::TIMELINES[$quote->getTimeline()] ?? $quote->getTimeline(),
            'Visite sur place' => $quote->isVisitRequested() ? 'Oui' : 'Non',
        ];
    }

    /** @param array<string, string> $vars */
    private function text(string $key, array $vars): string
    {
        $entry = $this->entityManager->getRepository(ContentEntry::class)->findOneBy(['contentKey' => $key]);
        $value = $entry?->getResolvedValue() ?? '';

        foreach ($vars as $name => $replacement) {
            $value = str_replace('{' . $name . '}', $replacement, $value);
        }

        return $value;
    }

    private function setting(string $key): ?string
    {
        $row = $this->entityManager->getRepository(Setting::class)->findOneBy(['settingKey' => $key]);
        $value = trim((string) $row?->getSettingValue());

        return $value === '' ? null : $value;
    }

    private function recordFailure(QuoteRequest $quote, string $reason): void
    {
        $log = new AuditLog();
        $log->setAction('mail.failed');
        $log->setEntityName('QuoteRequest');
        $log->setEntityId($quote->getId());
        $log->setPayload(['stage' => 'dispatch', 'reason' => mb_substr($reason, 0, 500)]);
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
