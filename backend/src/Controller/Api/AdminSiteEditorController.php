<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\{CsrfRequestGuard, SiteEditor};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/site-editor')]
class AdminSiteEditorController extends AbstractController
{
    #[Route('', name: 'api_admin_site_editor', methods: ['GET', 'PUT'])]
    public function editor(Request $request, SiteEditor $editor, CsrfRequestGuard $guard): JsonResponse
    {
        if ($request->isMethod('PUT')) {
            if (!$guard->isValid($request)) {
                return $this->json(['message' => 'Jeton CSRF invalide.'], 419);
            }
            try {
                $editor->save($request->toArray());
            } catch (\InvalidArgumentException $error) {
                return $this->json(['message' => $error->getMessage()], 422);
            }
        }
        return $this->json(['fields' => $editor->fields(), 'values' => $editor->values()]);
    }
}
