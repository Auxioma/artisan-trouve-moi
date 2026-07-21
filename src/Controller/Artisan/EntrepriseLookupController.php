<?php

declare(strict_types=1);

namespace App\Controller\Artisan;

use Psr\Cache\CacheItemInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Endpoint AJAX : à partir d'un SIRET à 14 chiffres, renvoie
 * toutes les données de l'entreprise ET tous les champs
 * postaux / géographiques de l'entité ArtisanProfile.
 *
 * Les appels aux API externes (recherche-entreprises + Nominatim)
 * sont effectués côté serveur : aucun problème CORS, User-Agent
 * conforme à la politique Nominatim, et mise en cache.
 */
#[IsGranted('ROLE_ARTISAN')]
final class EntrepriseLookupController extends AbstractController
{
    private const ENTREPRISE_SEARCH_API =
        'https://recherche-entreprises.api.gouv.fr/search';

    private const NOMINATIM_REVERSE_API =
        'https://nominatim.openstreetmap.org/reverse';

    private const NOMINATIM_SEARCH_API =
        'https://nominatim.openstreetmap.org/search';

    /**
     * Obligatoire pour Nominatim : identifie l'application.
     */
    private const NOMINATIM_USER_AGENT =
        'TrouveMoi/1.0 (contact@trouvemoi.com)';

    private const CACHE_TTL = 86400; // 24 h

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
    ) {
    }

    #[Route(
        '/espace-prestataire/api/entreprise/{siret}',
        name: 'app_artisan_api_entreprise',
        requirements: ['siret' => '\d{14}'],
        methods: ['GET']
    )]
    public function __invoke(string $siret): JsonResponse
    {
        try {
            $payload = $this->cache->get(
                'artisan_siret_lookup_'.$siret,
                function (CacheItemInterface $item) use ($siret): array {
                    $item->expiresAfter(self::CACHE_TTL);

                    return $this->lookup($siret);
                }
            );
        } catch (\RuntimeException $exception) {
            return $this->json(
                [
                    'success' => false,
                    'message' => $exception->getMessage(),
                ],
                404
            );
        } catch (\Throwable) {
            return $this->json(
                [
                    'success' => false,
                    'message' => 'Le service de recherche d’entreprise est momentanément indisponible.',
                ],
                502
            );
        }

        return $this->json($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function lookup(string $siret): array
    {
        [$company, $establishment] = $this->fetchCompany($siret);

        $companyData = $this->buildCompanyData(
            $company,
            $establishment,
            $siret
        );

        $addressData = $this->buildAddressData($establishment);

        return [
            'success' => true,
            'company' => $companyData,
            'address' => $addressData,
        ];
    }

    /**
     * Interroge l'API recherche-entreprises et sélectionne
     * l'établissement correspondant exactement au SIRET.
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    private function fetchCompany(string $siret): array
    {
        $response = $this->httpClient->request(
            'GET',
            self::ENTREPRISE_SEARCH_API,
            [
                'query' => [
                    'q' => $siret,
                    'page' => 1,
                    'per_page' => 25,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
            ]
        );

        $payload = $response->toArray();
        $companies = $payload['results'] ?? [];

        if (!\is_array($companies) || [] === $companies) {
            throw new \RuntimeException(sprintf('Aucune entreprise trouvée pour le SIRET %s.', $siret));
        }

        $siren = substr($siret, 0, 9);

        foreach ($companies as $company) {
            // 1. Établissement correspondant exactement au SIRET.
            foreach ($company['matching_etablissements'] ?? [] as $establishment) {
                if ($this->normalizeSiret($establishment['siret'] ?? '') === $siret) {
                    return [$company, $establishment];
                }
            }

            // 2. Le siège correspond au SIRET.
            if ($this->normalizeSiret($company['siege']['siret'] ?? '') === $siret) {
                return [$company, $company['siege']];
            }

            // 3. Même SIREN : on retombe sur le siège.
            if (($company['siren'] ?? '') === $siren) {
                return [
                    $company,
                    $company['siege']
                        ?? ($company['matching_etablissements'][0] ?? null),
                ];
            }
        }

        $company = $companies[0];

        return [
            $company,
            $company['siege']
                ?? ($company['matching_etablissements'][0] ?? null),
        ];
    }

    /**
     * @param array<string, mixed>      $company
     * @param array<string, mixed>|null $establishment
     *
     * @return array<string, string>
     */
    private function buildCompanyData(
        array $company,
        ?array $establishment,
        string $siret,
    ): array {
        $siren = (string) ($company['siren'] ?? substr($siret, 0, 9));

        return [
            'legalName' => $this->firstNonEmpty(
                $company['nom_raison_sociale'] ?? null,
                $company['nom_complet'] ?? null,
            ),
            'commercialName' => $this->firstNonEmpty(
                $establishment['nom_commercial'] ?? null,
                $company['sigle'] ?? null,
            ),
            'siret' => $siret,
            'siren' => $siren,
            'apeCode' => $this->firstNonEmpty(
                $establishment['activite_principale'] ?? null,
                $company['activite_principale'] ?? null,
            ),
            'legalForm' => $this->firstNonEmpty(
                $company['libelle_nature_juridique'] ?? null,
                $company['nature_juridique'] ?? null,
            ),
            'vatNumber' => $this->computeFrenchVatNumber($siren),
        ];
    }

    /**
     * Construit l'ensemble des champs postaux et géographiques
     * de l'entité à partir de l'établissement, complétés par
     * Nominatim (reverse geocoding en priorité).
     *
     * @param array<string, mixed>|null $establishment
     *
     * @return array<string, string>
     */
    private function buildAddressData(?array $establishment): array
    {
        $latitude = $establishment['latitude'] ?? null;
        $longitude = $establishment['longitude'] ?? null;

        $nominatim = null;

        // 1. Reverse geocoding : le plus fiable, l'API Entreprise
        //    fournit déjà les coordonnées de l'établissement.
        if ($this->isCoordinate($latitude) && $this->isCoordinate($longitude)) {
            $nominatim = $this->nominatimReverse(
                (float) $latitude,
                (float) $longitude
            );
        }

        // 2. Repli : recherche texte sur l'adresse INSEE.
        if (null === $nominatim && null !== $establishment) {
            $address = $this->buildEstablishmentAddress($establishment);

            if ('' !== $address) {
                $nominatim = $this->nominatimSearch($address);
            }
        }

        $osmAddress = $nominatim['address'] ?? [];

        return [
            'houseNumber' => $this->firstNonEmpty(
                $osmAddress['house_number'] ?? null,
                $establishment['numero_voie'] ?? null,
            ),
            'road' => $this->firstNonEmpty(
                $osmAddress['road'] ?? null,
                trim(sprintf(
                    '%s %s',
                    $establishment['type_voie'] ?? '',
                    $establishment['libelle_voie'] ?? ''
                )),
            ),
            'addressComplement' => (string) (
                $establishment['complement_adresse'] ?? ''
            ),
            'neighbourhood' => $this->firstNonEmpty(
                $osmAddress['neighbourhood'] ?? null,
                $osmAddress['quarter'] ?? null,
            ),
            'suburb' => (string) ($osmAddress['suburb'] ?? ''),
            'cityDistrict' => $this->firstNonEmpty(
                $osmAddress['city_district'] ?? null,
                $osmAddress['borough'] ?? null,
            ),
            'hamlet' => (string) ($osmAddress['hamlet'] ?? ''),
            'village' => (string) ($osmAddress['village'] ?? ''),
            'town' => (string) ($osmAddress['town'] ?? ''),
            'city' => $this->firstNonEmpty(
                $osmAddress['city'] ?? null,
                $osmAddress['town'] ?? null,
                $osmAddress['village'] ?? null,
                $osmAddress['municipality'] ?? null,
                $osmAddress['hamlet'] ?? null,
                $establishment['libelle_commune'] ?? null,
            ),
            'municipality' => (string) ($osmAddress['municipality'] ?? ''),
            'county' => (string) ($osmAddress['county'] ?? ''),
            'stateDistrict' => (string) ($osmAddress['state_district'] ?? ''),
            'state' => (string) ($osmAddress['state'] ?? ''),
            'region' => $this->firstNonEmpty(
                $osmAddress['region'] ?? null,
                $osmAddress['state'] ?? null,
            ),
            'postalCode' => $this->firstNonEmpty(
                $osmAddress['postcode'] ?? null,
                $establishment['code_postal'] ?? null,
            ),
            'country' => $this->firstNonEmpty(
                $osmAddress['country'] ?? null,
                'France',
            ),
            'countryCode' => strtoupper($this->firstNonEmpty(
                $osmAddress['country_code'] ?? null,
                'FR',
            )),
            'osmDisplayName' => $this->firstNonEmpty(
                $nominatim['display_name'] ?? null,
                null !== $establishment
                    ? $this->buildEstablishmentAddress($establishment)
                    : null,
            ),
            'latitude' => $this->firstNonEmpty(
                $nominatim['lat'] ?? null,
                $latitude,
            ),
            'longitude' => $this->firstNonEmpty(
                $nominatim['lon'] ?? null,
                $longitude,
            ),
            'osmId' => (string) ($nominatim['osm_id'] ?? ''),
            'osmType' => (string) ($nominatim['osm_type'] ?? ''),
            'osmCategory' => $this->firstNonEmpty(
                $nominatim['category'] ?? null,
                $nominatim['class'] ?? null,
            ),
            'osmPlaceType' => $this->firstNonEmpty(
                $nominatim['addresstype'] ?? null,
                $nominatim['type'] ?? null,
            ),
            'nominatimPlaceId' => (string) ($nominatim['place_id'] ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function nominatimReverse(
        float $latitude,
        float $longitude,
    ): ?array {
        try {
            $response = $this->httpClient->request(
                'GET',
                self::NOMINATIM_REVERSE_API,
                [
                    'query' => [
                        'lat' => (string) $latitude,
                        'lon' => (string) $longitude,
                        'format' => 'jsonv2',
                        'addressdetails' => '1',
                        'zoom' => '18',
                        'accept-language' => 'fr',
                    ],
                    'headers' => [
                        'Accept' => 'application/json',
                        'User-Agent' => self::NOMINATIM_USER_AGENT,
                    ],
                    'timeout' => 10,
                ]
            );

            $payload = $response->toArray();

            return isset($payload['error']) ? null : $payload;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function nominatimSearch(string $query): ?array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                self::NOMINATIM_SEARCH_API,
                [
                    'query' => [
                        'q' => $query,
                        'format' => 'jsonv2',
                        'addressdetails' => '1',
                        'limit' => '1',
                        'countrycodes' => 'fr',
                        'accept-language' => 'fr',
                    ],
                    'headers' => [
                        'Accept' => 'application/json',
                        'User-Agent' => self::NOMINATIM_USER_AGENT,
                    ],
                    'timeout' => 10,
                ]
            );

            $results = $response->toArray();

            return \is_array($results) ? ($results[0] ?? null) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $establishment
     */
    private function buildEstablishmentAddress(array $establishment): string
    {
        $adresse = trim((string) ($establishment['adresse'] ?? ''));

        if ('' !== $adresse) {
            return $adresse;
        }

        return trim(preg_replace(
            '/\s+/',
            ' ',
            implode(' ', array_filter([
                $establishment['numero_voie'] ?? null,
                $establishment['indice_repetition'] ?? null,
                $establishment['type_voie'] ?? null,
                $establishment['libelle_voie'] ?? null,
                $establishment['code_postal'] ?? null,
                $establishment['libelle_commune'] ?? null,
            ]))
        ) ?? '');
    }

    private function computeFrenchVatNumber(string $siren): string
    {
        $normalized = preg_replace('/\D/', '', $siren) ?? '';

        if (9 !== \strlen($normalized)) {
            return '';
        }

        $key = (12 + 3 * ((int) $normalized % 97)) % 97;

        return sprintf('FR%02d%s', $key, $normalized);
    }

    private function normalizeSiret(mixed $value): string
    {
        return substr(
            preg_replace('/\D/', '', (string) $value) ?? '',
            0,
            14
        );
    }

    private function isCoordinate(mixed $value): bool
    {
        return null !== $value
            && '' !== $value
            && is_numeric($value);
    }

    private function firstNonEmpty(mixed ...$values): string
    {
        foreach ($values as $value) {
            if (null !== $value && '' !== trim((string) $value)) {
                return trim((string) $value);
            }
        }

        return '';
    }
}
