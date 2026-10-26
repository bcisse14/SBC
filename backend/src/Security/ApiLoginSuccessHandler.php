<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class ApiLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        $user = $token->getUser();

        return new JsonResponse([
            'message' => 'Connexion reussie.',
            'user' => [
                'email' => method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : null,
                'roles' => method_exists($user, 'getRoles') ? $user->getRoles() : [],
            ],
        ]);
    }
}
