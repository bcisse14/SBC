<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener(event: LogoutEvent::class, priority: 128)]
class ApiLogoutListener
{
    public function __invoke(LogoutEvent $event): void
    {
        if ($event->getRequest()->getPathInfo() === '/api/admin/logout') {
            $event->setResponse(new JsonResponse(['message' => 'Deconnexion reussie.']));
        }
    }
}
