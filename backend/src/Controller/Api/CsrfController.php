<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/api/admin', name: 'api_admin_csrf_')]
class CsrfController extends AbstractController
{
    public function __construct(private readonly CsrfTokenManagerInterface $csrfTokenManager)
    {
    }

    #[Route('/csrf', name: 'token', methods: ['GET'])]
    public function token(): JsonResponse
    {
        return $this->json([
            'tokens' => [
                'admin' => $this->csrfTokenManager->getToken('admin')->getValue(),
                'quote' => $this->csrfTokenManager->getToken('quote')->getValue(),
            ],
        ]);
    }
}
