<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Proxy de géocodage OpenStreetMap (Nominatim) pour l'admin.
 *
 * Passe par le serveur pour :
 * - respecter la politique Nominatim (User-Agent identifiable, 1 req/s)
 * - mettre en cache les résultats 24 h
 * - normaliser la réponse vers les champs de UserProfile
 *
 * En environnement dev, le détail d'une éventuelle erreur Nominatim
 * est renvoyé dans la réponse JSON (clé "error") pour le diagnostic.
 */
#[IsGranted('ROLE_ADMIN')]
final class AdminGeocodeController extends AbstractController
{
    private const NOMINATIM_ENDPOINT =
        'https://nominatim.openstreetmap.org/search';

    private const CACHE_TTL = 86400; // 24 h

    private const MAX_RESULTS = 5;

    private ?string $lastError = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.debug%')]
        private readonly bool $isDebug,
    ) {
    }

    #[Route(
        path: '/admin/geocode',
        name: 'admin_geocode',
        methods: ['GET'],
    )]
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));

        if (mb_strlen($query) < 3) {
            return $this->json([
                'results' => [],
            ]);
        }

        $cacheKey = 'admin_geocode_'.md5(mb_strtolower($query));

        $results = $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($query): array {
                $results = $this->fetchNominatim($query);

                /*
                 * Ne met en cache que les réponses réussies :
                 * un échec réseau ne doit pas bloquer la recherche
                 * pendant 24 h.
                 */
                $item->expiresAfter(
                    null === $this->lastError
                        ? self::CACHE_TTL
                        : 0
                );

                return $results;
            }
        );

        $payload = [
            'results' => $results,
        ];

        if ($this->isDebug && null !== $this->lastError) {
            $payload['error'] = $this->lastError;
        }

        return $this->json($payload);
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function fetchNominatim(string $query): array
    {
        $this->lastError = null;

        try {
            $response = $this->httpClient->request(
                'GET',
                self::NOMINATIM_ENDPOINT,
                [
                    'query' => [
                        'q' => $query,
                        'format' => 'jsonv2',
                        'addressdetails' => 1,
                        'limit' => self::MAX_RESULTS,
                        'countrycodes' => 'fr',
                        'accept-language' => 'fr',
                    ],
                    'headers' => [
                        // Obligatoire : la politique Nominatim exige
                        // un User-Agent identifiant l'application.
                        'User-Agent' =>
                            'TrouveMoi-Admin/1.0 (contact@trouvemoi.com)',
                    ],
                    'timeout' => 8,
                ]
            );

            $data = $response->toArray(false);
        } catch (\Throwable $exception) {
            $this->lastError = $exception->getMessage();

            $this->logger->error(
                'Échec de la requête Nominatim : {message}',
                [
                    'message' => $exception->getMessage(),
                    'query' => $query,
                ]
            );

            return [];
        }

        if (!is_array($data)) {
            $this->lastError = 'Réponse Nominatim invalide.';

            return [];
        }

        $results = [];

        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }

            $address = is_array($item['address'] ?? null)
                ? $item['address']
                : [];

            $results[] = $this->normalizeResult($item, $address);
        }

        return $results;
    }

    /**
     * Normalise un résultat Nominatim vers les champs de UserProfile.
     *
     * @param array<string, mixed> $item
     * @param array<string, mixed> $address
     *
     * @return array<string, string|null>
     */
    private function normalizeResult(array $item, array $address): array
    {
        $houseNumber = $this->stringOrNull($address['house_number'] ?? null);
        $road = $this->stringOrNull($address['road'] ?? null);

        $addressLine1 = trim(
            sprintf('%s %s', $houseNumber ?? '', $road ?? '')
        );

        $city = $this->stringOrNull($address['city'] ?? null)
            ?? $this->stringOrNull($address['town'] ?? null)
            ?? $this->stringOrNull($address['village'] ?? null)
            ?? $this->stringOrNull($address['municipality'] ?? null);

        $district = $this->stringOrNull($address['suburb'] ?? null)
            ?? $this->stringOrNull($address['city_district'] ?? null)
            ?? $this->stringOrNull($address['neighbourhood'] ?? null);

        $region = $this->stringOrNull($address['state'] ?? null);

        $department = $this->stringOrNull($address['county'] ?? null)
            ?? $this->stringOrNull($address['state_district'] ?? null);

        $countryCode = $this->stringOrNull(
            $address['country_code'] ?? null
        );

        $osmType = $this->stringOrNull($item['osm_type'] ?? null);
        $osmId = isset($item['osm_id'])
            ? (string) $item['osm_id']
            : null;

        $placeId = (null !== $osmType && null !== $osmId)
            ? sprintf('%s/%s', $osmType, $osmId)
            : null;

        return [
            'label' => $this->buildShortLabel(
                $addressLine1,
                $city
            ),
            'displayName' => $this->stringOrNull(
                $item['display_name'] ?? null
            ),
            'addressLine1' => '' !== $addressLine1
                ? $addressLine1
                : null,
            'postalCode' => $this->stringOrNull(
                $address['postcode'] ?? null
            ),
            'city' => $city,
            'district' => $district,
            'region' => $region,
            'department' => $department,
            'countryCode' => null !== $countryCode
                ? strtoupper($countryCode)
                : null,
            'latitude' => $this->stringOrNull($item['lat'] ?? null),
            'longitude' => $this->stringOrNull($item['lon'] ?? null),
            'providerPlaceId' => $placeId,
            'providerName' => 'OSM',
        ];
    }

    private function buildShortLabel(
        string $addressLine1,
        ?string $city,
    ): ?string {
        $parts = array_filter([
            '' !== $addressLine1 ? $addressLine1 : null,
            $city,
        ]);

        if ([] === $parts) {
            return null;
        }

        return implode(', ', $parts);
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
