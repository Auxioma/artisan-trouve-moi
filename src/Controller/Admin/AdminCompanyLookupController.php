<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Proxy admin vers l'API publique Recherche d'entreprises de data.gouv.fr.
 */
final class AdminCompanyLookupController extends AbstractController
{
    private const API_ENDPOINT = 'https://recherche-entreprises.api.gouv.fr/search';

    private const CACHE_TTL = 86400;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(
        path: '/admin/company-lookup',
        name: 'admin_company_lookup',
        methods: ['GET'],
    )]
    public function search(Request $request): JsonResponse
    {
        $identifier = preg_replace('/\D/', '', (string) $request->query->get('siren', ''));

        if (!\in_array(strlen($identifier), [9, 14], true)) {
            return $this->json([
                'message' => 'Le SIREN doit contenir 9 chiffres ou le SIRET 14 chiffres.',
            ], 422);
        }

        try {
            $company = $this->cache->get(
                'admin_company_lookup_v4_'.$identifier,
                function (ItemInterface $item) use ($identifier): ?array {
                    $item->expiresAfter(self::CACHE_TTL);

                    return $this->fetchCompany($identifier);
                },
            );
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Échec de la recherche entreprise : {message}',
                [
                    'message' => $exception->getMessage(),
                    'identifier' => $identifier,
                ],
            );

            return $this->json([
                'message' => 'Le service de recherche des entreprises est indisponible.',
            ], 502);
        }

        if (null === $company) {
            return $this->json([
                'message' => 'Aucune entreprise diffusible n’a été trouvée pour ce SIREN ou SIRET.',
            ], 404);
        }

        return $this->json([
            'company' => $company,
        ]);
    }

    /**
     * @return array<string, string|bool|null>|null
     */
    private function fetchCompany(string $identifier): ?array
    {
        $response = $this->httpClient->request(
            'GET',
            self::API_ENDPOINT,
            [
                'query' => [
                    'q' => $identifier,
                    'per_page' => 1,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'ArtisanTrouveMoi-Admin/1.0',
                ],
                'timeout' => 8,
            ],
        );

        $data = $response->toArray(false);
        $result = $data['results'][0] ?? null;

        if (!is_array($result)) {
            return null;
        }

        $siege = is_array($result['siege'] ?? null) ? $result['siege'] : [];
        $director = is_array($result['dirigeants'][0] ?? null)
            ? $result['dirigeants'][0]
            : [];
        $road = trim(implode(' ', array_filter([
            $this->stringOrNull($siege['type_voie'] ?? null),
            $this->stringOrNull($siege['libelle_voie'] ?? null),
        ])));

        $activityDescription = $this->stringOrNull(
            $result['libelle_activite_principale'] ?? null,
        ) ?? $this->stringOrNull($result['activite_principale'] ?? null);

        return [
            'legalName' => $this->stringOrNull($result['nom_raison_sociale'] ?? null)
                ?? $this->stringOrNull($result['nom_complet'] ?? null),
            'commercialName' => $this->stringOrNull($siege['nom_commercial'] ?? null)
                ?? $this->stringOrNull($result['sigle'] ?? null),
            'siren' => $this->stringOrNull($result['siren'] ?? null),
            'siret' => $this->stringOrNull($siege['siret'] ?? null),
            'vatNumber' => $this->stringOrNull(
                $result['numero_tva_intracommunautaire'] ?? null,
            ) ?? $this->stringOrNull(
                $result['tva'] ?? null,
            ),
            'apeCode' => $this->stringOrNull($result['activite_principale'] ?? null),
            'activityDescription' => $activityDescription,
            'description' => $activityDescription,
            'legalForm' => $this->stringOrNull(
                $result['libelle_nature_juridique'] ?? null,
            ) ?? $this->stringOrNull($result['nature_juridique'] ?? null),
            'isRegisteredInRne' => null !== $this->stringOrNull(
                $result['date_mise_a_jour_rne'] ?? null,
            ),
            'representativeFirstName' => $this->stringOrNull(
                $director['prenoms'] ?? null,
            ),
            'representativeLastName' => $this->stringOrNull(
                $director['nom'] ?? null,
            ),
            'representativeJobTitle' => $this->stringOrNull(
                $director['qualite'] ?? null,
            ),
            'firstName' => $this->stringOrNull($director['prenoms'] ?? null),
            'lastName' => $this->stringOrNull($director['nom'] ?? null),
            'qualifiedPersonFirstName' => $this->stringOrNull(
                $director['prenoms'] ?? null,
            ),
            'qualifiedPersonLastName' => $this->stringOrNull(
                $director['nom'] ?? null,
            ),
            'qualifiedPersonPosition' => $this->stringOrNull(
                $director['qualite'] ?? null,
            ),
            'houseNumber' => $this->stringOrNull($siege['numero_voie'] ?? null),
            'road' => '' !== $road ? $road : null,
            'addressComplement' => $this->stringOrNull(
                $siege['complement_adresse'] ?? null,
            ),
            'postalCode' => $this->stringOrNull($siege['code_postal'] ?? null),
            'city' => $this->stringOrNull($siege['libelle_commune'] ?? null),
            'commercialArea' => $this->stringOrNull($siege['libelle_commune'] ?? null),
            'region' => $this->stringOrNull($siege['region'] ?? null),
            'country' => 'France',
            'countryCode' => 'FR',
            'latitude' => $this->stringOrNull($siege['latitude'] ?? null),
            'longitude' => $this->stringOrNull($siege['longitude'] ?? null),
            'isActive' => 'A' === $this->stringOrNull(
                $result['etat_administratif'] ?? null,
            ),
        ];
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return '' !== $value ? $value : null;
    }
}
