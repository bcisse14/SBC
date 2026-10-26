<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfRequestGuard
{
    public function __construct(private readonly CsrfTokenManagerInterface $tokenManager)
    {
    }

    public function isValid(Request $request, string $tokenId = 'admin'): bool
    {
        $token = $request->headers->get('X-CSRF-TOKEN') ?? $request->headers->get('X-CSRF-Token');

        return is_string($token) && $this->tokenManager->isTokenValid(new CsrfToken($tokenId, $token));
    }
}
