<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\AuditLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

/** Trace l'échec définitif d'un envoi (après toutes les tentatives) pour relance manuelle. */
#[AsEventListener]
class MailFailureListener
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(WorkerMessageFailedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();
        if ($event->willRetry() || !$message instanceof SendEmailMessage) {
            return;
        }

        $recipients = array_map(static fn ($a) => $a->getAddress(), $message->getEnvelope()?->getRecipients() ?? []);

        $log = new AuditLog();
        $log->setAction('mail.failed');
        $log->setEntityName('QuoteRequest');
        $header = $message->getMessage() instanceof \Symfony\Component\Mime\Email ? $message->getMessage()->getHeaders()->get('X-Quote-Id') : null;
        $log->setEntityId($header ? (int) $header->getBodyAsString() : null);
        $log->setPayload([
            'stage' => 'delivery',
            'recipients' => $recipients,
            'reason' => mb_substr($event->getThrowable()->getMessage(), 0, 500),
        ]);
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
