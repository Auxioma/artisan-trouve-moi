<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\CompanyLookupService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class CompanyLookupController extends AbstractController
{
    #[Route(
        '/api/entreprises/siret/{siret}',
        name: 'api_company_lookup_siret',
        requirements: ['siret' => '\\d{14}'],
        methods: ['GET'],
    )]
    public function __invoke(
        string $siret,
        CompanyLookupService $companyLookup,
    ): JsonResponse {
        try {
            return $this->json([
                'success' => true,
                'company' => $companyLookup->findBySiret($siret),
            ]);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 404);
        } catch (\Throwable) {
            return $this->json([
                'success' => false,
                'message' => 'Impossible de vérifier ce SIRET pour le moment.',
            ], 503);
        }
    }
}
