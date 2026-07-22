<?php

declare(strict_types=1);

namespace App\Controller\Users;

use App\Entity\Users\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

final class RegistrationController extends AbstractController
{
    private const COMPANY_API_URL =
        'https://recherche-entreprises.api.gouv.fr/search';

    private const NOMINATIM_SEARCH_URL =
        'https://nominatim.openstreetmap.org/search';

    public function __construct(
        private readonly EmailVerifier $emailVerifier,
    ) {
    }

    /**
     * Inscription d’un particulier ou d’un professionnel.
     */
    #[Route(
        '/register',
        name: 'app_register',
        methods: ['GET', 'POST']
    )]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = new User();

        $form = $this->createForm(
            RegistrationFormType::class,
            $user
        );

        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $accountType = '';

            if ($form->has('accountType')) {
                $accountType = (string) $form
                    ->get('accountType')
                    ->getData();
            }

            /*
             * Un particulier ne doit pas conserver
             * un profil professionnel.
             */
            if (
                'client' === $accountType
                && method_exists($user, 'setArtisanProfile')
            ) {
                $user->setArtisanProfile(null);
            }

            /*
             * Vérification du profil professionnel.
             */
            if (
                'pro' === $accountType
                && method_exists($user, 'getArtisanProfile')
                && null === $user->getArtisanProfile()
            ) {
                $this->addFlash(
                    'error',
                    'Les informations professionnelles sont obligatoires.'
                );

                return $this->render(
                    'auth/registration/register.html.twig',
                    [
                        'registrationForm' => $form,
                    ]
                );
            }

            $plainPassword = '';

            if ($form->has('plainPassword')) {
                $plainPassword = (string) $form
                    ->get('plainPassword')
                    ->getData();
            }

            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $plainPassword
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(
                        new Address(
                            'hello@trouvemoi.com',
                            'TrouveMoi'
                        )
                    )
                    ->to((string) $user->getEmail())
                    ->subject(
                        'Confirmez votre adresse e-mail'
                    )
                    ->htmlTemplate(
                        'email/auth/confirmation_email.html.twig'
                    )
            );

            $this->addFlash(
                'success',
                'Votre compte a été créé. Consultez votre boîte e-mail pour confirmer votre inscription.'
            );

            return $this->redirectToRoute(
                'app_login'
            );
        }

        return $this->render(
            'auth/registration/register.html.twig',
            [
                'registrationForm' => $form,
            ]
        );
    }

    /**
     * Recherche d’une entreprise française à partir d’un SIRET.
     */
    #[Route(
        '/api/entreprise/siret/{siret}',
        name: 'app_company_lookup_siret',
        methods: ['GET']
    )]
    public function companyLookupBySiret(
        string $siret,
        HttpClientInterface $httpClient,
    ): JsonResponse {
        $normalizedSiret = $this->normalizeIdentifier(
            $siret
        );

        if (14 !== strlen($normalizedSiret)) {
            return $this->json(
                [
                    'success' => false,
                    'message' =>
                        'Le numéro SIRET doit contenir exactement 14 chiffres.',
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $governmentResponse = $httpClient->request(
                'GET',
                self::COMPANY_API_URL,
                [
                    'query' => [
                        'q' => $normalizedSiret,
                        'page' => 1,
                        'per_page' => 10,
                    ],
                    'headers' => [
                        'Accept' => 'application/json',
                        'Accept-Language' =>
                            'fr-FR,fr;q=0.9',
                        'User-Agent' =>
                            'TrouveMoi/1.0 (contact@trouvemoi.com)',
                    ],
                    'timeout' => 20,
                    'max_duration' => 30,
                ]
            );

            $statusCode =
                $governmentResponse->getStatusCode();

            /*
             * false empêche Symfony de lancer une exception
             * automatiquement sur les statuts 4xx et 5xx.
             */
            $rawContent =
                $governmentResponse->getContent(false);

            $governmentPayload =
                $this->decodeJsonResponse($rawContent);

            if (
                Response::HTTP_TOO_MANY_REQUESTS
                === $statusCode
            ) {
                return $this->json(
                    [
                        'success' => false,
                        'message' =>
                            'Trop de recherches ont été effectuées. Réessayez dans quelques secondes.',
                        'apiStatus' => $statusCode,
                    ],
                    Response::HTTP_TOO_MANY_REQUESTS
                );
            }

            if (
                Response::HTTP_BAD_REQUEST
                === $statusCode
            ) {
                return $this->json(
                    [
                        'success' => false,
                        'message' =>
                            $this->extractApiErrorMessage(
                                $governmentPayload,
                                'La requête envoyée à l’API gouvernementale est invalide.'
                            ),
                        'apiStatus' => $statusCode,
                        'apiResponse' =>
                            $this->isDebug()
                                ? $governmentPayload
                                : null,
                    ],
                    Response::HTTP_BAD_GATEWAY
                );
            }

            if (
                $statusCode < 200
                || $statusCode >= 300
            ) {
                return $this->json(
                    [
                        'success' => false,
                        'message' => sprintf(
                            'L’API gouvernementale a renvoyé une erreur HTTP %d.',
                            $statusCode
                        ),
                        'apiStatus' => $statusCode,
                        'apiResponse' =>
                            $this->isDebug()
                                ? $governmentPayload
                                : null,
                    ],
                    Response::HTTP_BAD_GATEWAY
                );
            }

            if ([] === $governmentPayload) {
                return $this->json(
                    [
                        'success' => false,
                        'message' =>
                            'L’API gouvernementale a renvoyé une réponse vide ou invalide.',
                    ],
                    Response::HTTP_BAD_GATEWAY
                );
            }

            $results = is_array(
                $governmentPayload['results'] ?? null
            )
                ? $governmentPayload['results']
                : [];

            if ([] === $results) {
                return $this->json(
                    [
                        'success' => false,
                        'message' =>
                            'Aucune entreprise ne correspond à ce numéro SIRET.',
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            [$company, $establishment] =
                $this->findCompanyAndEstablishment(
                    $results,
                    $normalizedSiret
                );

            if (!is_array($company)) {
                return $this->json(
                    [
                        'success' => false,
                        'message' =>
                            'L’entreprise correspondant à ce SIRET n’a pas été trouvée.',
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            if (!is_array($establishment)) {
                $establishment = [];
            }

            $legalName = $this->firstNonEmpty([
                $company['nom_complet'] ?? null,
                $company['nom_raison_sociale'] ?? null,
                $company['denomination'] ?? null,
                $company['nom'] ?? null,
            ]);

            $commercialName = $this->firstNonEmpty([
                $establishment['nom_commercial'] ?? null,
                $this->firstArrayValue(
                    $establishment['liste_enseignes']
                        ?? null
                ),
                $company['nom_commercial'] ?? null,
            ]);

            /*
             * Lorsque le nom commercial est absent,
             * on conserve la raison sociale.
             */
            if ('' === $commercialName) {
                $commercialName = $legalName;
            }

            $siren = $this->firstNonEmpty([
                $company['siren'] ?? null,
                substr($normalizedSiret, 0, 9),
            ]);

            $vatNumber = $this->firstNonEmpty([
                $company[
                    'numero_tva_intracommunautaire'
                ] ?? null,
                $company['numero_tva'] ?? null,
            ]);

            $apeCode = $this->firstNonEmpty([
                $establishment[
                    'activite_principale'
                ] ?? null,
                $establishment['code_naf'] ?? null,
                $company['activite_principale']
                    ?? null,
                $company['code_naf'] ?? null,
            ]);

            $legalForm = $this->firstNonEmpty([
                $company['libelle_nature_juridique']
                    ?? null,
                $company['nature_juridique'] ?? null,
                $company['categorie_juridique']
                    ?? null,
            ]);

            $companyStatus = $this->firstNonEmpty([
                $establishment[
                    'etat_administratif'
                ] ?? null,
                $company['etat_administratif']
                    ?? null,
            ]);

            $manager = $this->extractManager(
                $company['dirigeants'] ?? []
            );

            $governmentAddress =
                $this->extractGovernmentAddress(
                    $establishment
                );

            $officialFullAddress =
                $governmentAddress['fullAddress'];

            $postalCode =
                $governmentAddress['postalCode'];

            $city =
                $governmentAddress['city'];

            $department =
                $governmentAddress['department'];

            $region =
                $governmentAddress['region'];

            $latitude =
                $governmentAddress['latitude'];

            $longitude =
                $governmentAddress['longitude'];

            /*
             * L’adresse officielle est d’abord décomposée
             * localement afin de ne pas dépendre entièrement
             * de Nominatim.
             */
            $parsedStreet =
                $this->parseStreetFromFullAddress(
                    $officialFullAddress,
                    $postalCode,
                    $city
                );

            $houseNumber =
                $parsedStreet['houseNumber'];

            $road =
                $parsedStreet['road'];

            $addressComplement =
                $this->firstNonEmpty([
                    $establishment['complement_adresse']
                        ?? null,
                    $establishment[
                        'complement_address'
                    ] ?? null,
                    $establishment['complements']
                        ?? null,
                ]);

            /*
             * Nominatim complète uniquement la décomposition
             * géographique. Son échec ne bloque jamais
             * la récupération de l’entreprise.
             */
            $nominatimResult = [];

            if ('' !== $officialFullAddress) {
                $nominatimResult =
                    $this->searchAddressWithNominatim(
                        $httpClient,
                        $officialFullAddress,
                        $postalCode,
                        $city
                    );
            }

            $osmAddress = is_array(
                $nominatimResult['address'] ?? null
            )
                ? $nominatimResult['address']
                : [];

            $houseNumber = $this->firstNonEmpty([
                $houseNumber,
                $osmAddress['house_number'] ?? null,
            ]);

            $road = $this->firstNonEmpty([
                $road,
                $osmAddress['road'] ?? null,
                $osmAddress['pedestrian'] ?? null,
                $osmAddress['residential'] ?? null,
                $osmAddress['footway'] ?? null,
                $osmAddress['path'] ?? null,
            ]);

            $addressComplement =
                $this->firstNonEmpty([
                    $addressComplement,
                    $osmAddress['building'] ?? null,
                    $osmAddress['house_name'] ?? null,
                ]);

            $postalCode = $this->firstNonEmpty([
                $postalCode,
                $osmAddress['postcode'] ?? null,
            ]);

            $city = $this->firstNonEmpty([
                $city,
                $osmAddress['city'] ?? null,
                $osmAddress['town'] ?? null,
                $osmAddress['village'] ?? null,
                $osmAddress['municipality'] ?? null,
                $osmAddress['hamlet'] ?? null,
            ]);

            $department = $this->firstNonEmpty([
                $department,
                $osmAddress['county'] ?? null,
            ]);

            $region = $this->firstNonEmpty([
                $region,
                $osmAddress['region'] ?? null,
                $osmAddress['state'] ?? null,
            ]);

            $country = $this->firstNonEmpty([
                $osmAddress['country'] ?? null,
                'France',
            ]);

            $countryCode = strtoupper(
                $this->firstNonEmpty([
                    $osmAddress['country_code'] ?? null,
                    'FR',
                ])
            );

            $latitude = $this->firstNonEmpty([
                $latitude,
                $nominatimResult['lat'] ?? null,
            ]);

            $longitude = $this->firstNonEmpty([
                $longitude,
                $nominatimResult['lon'] ?? null,
            ]);

            $nominatimDisplayName =
                $this->firstNonEmpty([
                    $nominatimResult['display_name']
                        ?? null,
                ]);

            $fullAddress = $this->firstNonEmpty([
                $officialFullAddress,
                $nominatimDisplayName,
            ]);

            return $this->json([
                'success' => true,

                'company' => [
                    /*
                     * Entreprise.
                     */
                    'legalName' => $legalName,
                    'commercialName' => $commercialName,
                    'siren' => $siren,
                    'siret' => $normalizedSiret,
                    'vatNumber' => $vatNumber,
                    'apeCode' => $apeCode,
                    'legalForm' => $legalForm,
                    'companyStatus' => $companyStatus,

                    /*
                     * Dirigeant.
                     */
                    'managerFirstName' =>
                        $manager['firstName'],

                    'managerLastName' =>
                        $manager['lastName'],

                    'managerQuality' =>
                        $manager['quality'],

                    'managerType' =>
                        $manager['type'],

                    /*
                     * Adresse officielle.
                     */
                    'houseNumber' => $houseNumber,
                    'road' => $road,

                    'addressComplement' =>
                        $addressComplement,

                    'fullAddress' => $fullAddress,

                    /*
                     * Informations géographiques.
                     */
                    'neighbourhood' =>
                        $this->firstNonEmpty([
                            $osmAddress['neighbourhood']
                                ?? null,
                            $osmAddress['quarter']
                                ?? null,
                        ]),

                    'suburb' =>
                        $this->firstNonEmpty([
                            $osmAddress['suburb'] ?? null,
                        ]),

                    'cityDistrict' =>
                        $this->firstNonEmpty([
                            $osmAddress['city_district']
                                ?? null,
                            $osmAddress['borough']
                                ?? null,
                        ]),

                    'hamlet' =>
                        $this->firstNonEmpty([
                            $osmAddress['hamlet'] ?? null,
                        ]),

                    'village' =>
                        $this->firstNonEmpty([
                            $osmAddress['village'] ?? null,
                        ]),

                    'town' =>
                        $this->firstNonEmpty([
                            $osmAddress['town'] ?? null,
                        ]),

                    'city' => $city,

                    'municipality' =>
                        $this->firstNonEmpty([
                            $osmAddress['municipality']
                                ?? null,
                        ]),

                    'county' => $department,

                    'department' => $department,

                    'stateDistrict' =>
                        $this->firstNonEmpty([
                            $osmAddress['state_district']
                                ?? null,
                        ]),

                    'state' =>
                        $this->firstNonEmpty([
                            $osmAddress['state'] ?? null,
                        ]),

                    'region' => $region,
                    'postalCode' => $postalCode,
                    'country' => $country,
                    'countryCode' => $countryCode,

                    /*
                     * Géolocalisation.
                     */
                    'latitude' =>
                        '' !== $latitude
                            ? $latitude
                            : null,

                    'longitude' =>
                        '' !== $longitude
                            ? $longitude
                            : null,

                    /*
                     * Informations OpenStreetMap.
                     */
                    'osmDisplayName' =>
                        $this->firstNonEmpty([
                            $nominatimDisplayName,
                            $fullAddress,
                        ]),

                    'osmId' =>
                        isset($nominatimResult['osm_id'])
                            ? (string) $nominatimResult[
                                'osm_id'
                            ]
                            : null,

                    'osmType' =>
                        $this->firstNonEmpty([
                            $nominatimResult['osm_type']
                                ?? null,
                        ]),

                    'osmCategory' =>
                        $this->firstNonEmpty([
                            $nominatimResult['category']
                                ?? null,
                            $nominatimResult['class']
                                ?? null,
                        ]),

                    'osmPlaceType' =>
                        $this->firstNonEmpty([
                            $nominatimResult['type']
                                ?? null,
                            $nominatimResult[
                                'addresstype'
                            ] ?? null,
                        ]),

                    'nominatimPlaceId' =>
                        isset($nominatimResult['place_id'])
                            ? (string) $nominatimResult[
                                'place_id'
                            ]
                            : null,
                ],
            ]);
        } catch (TransportExceptionInterface $exception) {
            return $this->json(
                [
                    'success' => false,
                    'message' =>
                        'Impossible de contacter l’API gouvernementale. Vérifiez la connexion internet du serveur, le certificat SSL et l’extension cURL de PHP.',
                    'error' =>
                        $this->isDebug()
                            ? $exception->getMessage()
                            : null,
                ],
                Response::HTTP_BAD_GATEWAY
            );
        } catch (\Throwable $exception) {
            return $this->json(
                [
                    'success' => false,
                    'message' =>
                        'Une erreur est survenue pendant la récupération de l’entreprise.',
                    'error' =>
                        $this->isDebug()
                            ? $exception->getMessage()
                            : null,
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Vérification de l’adresse e-mail.
     */
    #[Route(
        '/verify/email',
        name: 'app_verify_email',
        methods: ['GET']
    )]
    public function verifyUserEmail(
        Request $request,
        TranslatorInterface $translator,
    ): Response {
        $this->denyAccessUnlessGranted(
            'IS_AUTHENTICATED_FULLY'
        );

        try {
            $user = $this->getUser();

            if (!$user instanceof User) {
                throw $this->createAccessDeniedException(
                    'Utilisateur introuvable.'
                );
            }

            $this->emailVerifier
                ->handleEmailConfirmation(
                    $request,
                    $user
                );
        } catch (
            VerifyEmailExceptionInterface $exception
        ) {
            $this->addFlash(
                'verify_email_error',
                $translator->trans(
                    $exception->getReason(),
                    [],
                    'VerifyEmailBundle'
                )
            );

            return $this->redirectToRoute(
                'app_register'
            );
        }

        $this->addFlash(
            'success',
            'Votre adresse e-mail a été vérifiée.'
        );

        return $this->redirectToRoute(
            'app_login'
        );
    }

    /**
     * Recherche l’entreprise et l’établissement
     * correspondant exactement au SIRET.
     *
     * @param array<int, mixed> $results
     *
     * @return array{
     *     0: array<string, mixed>|null,
     *     1: array<string, mixed>|null
     * }
     */
    private function findCompanyAndEstablishment(
        array $results,
        string $normalizedSiret,
    ): array {
        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }

            $headOffice = is_array(
                $result['siege'] ?? null
            )
                ? $result['siege']
                : [];

            $headOfficeSiret =
                $this->normalizeIdentifier(
                    $headOffice['siret'] ?? null
                );

            if ($headOfficeSiret === $normalizedSiret) {
                return [
                    $result,
                    $headOffice,
                ];
            }

            $matchingEstablishments = is_array(
                $result['matching_etablissements']
                    ?? null
            )
                ? $result['matching_etablissements']
                : [];

            foreach (
                $matchingEstablishments
                as $matchingEstablishment
            ) {
                if (!is_array($matchingEstablishment)) {
                    continue;
                }

                $matchingSiret =
                    $this->normalizeIdentifier(
                        $matchingEstablishment['siret']
                            ?? null
                    );

                if ($matchingSiret === $normalizedSiret) {
                    return [
                        $result,
                        $matchingEstablishment,
                    ];
                }
            }
        }

        /*
         * Repli sur le premier résultat uniquement
         * si son SIREN correspond au SIRET recherché.
         */
        $firstResult = $results[0] ?? null;

        if (!is_array($firstResult)) {
            return [
                null,
                null,
            ];
        }

        $expectedSiren = substr(
            $normalizedSiret,
            0,
            9
        );

        $resultSiren =
            $this->normalizeIdentifier(
                $firstResult['siren'] ?? null
            );

        if ($resultSiren !== $expectedSiren) {
            return [
                null,
                null,
            ];
        }

        $headOffice = is_array(
            $firstResult['siege'] ?? null
        )
            ? $firstResult['siege']
            : [];

        return [
            $firstResult,
            $headOffice,
        ];
    }

    /**
     * Extrait l’adresse gouvernementale.
     *
     * @param array<string, mixed> $establishment
     *
     * @return array{
     *     fullAddress: string,
     *     postalCode: string,
     *     city: string,
     *     department: string,
     *     region: string,
     *     latitude: string,
     *     longitude: string
     * }
     */
    private function extractGovernmentAddress(
        array $establishment,
    ): array {
        $fullAddress = $this->firstNonEmpty([
            $establishment['adresse'] ?? null,
            $establishment['adresse_complete'] ?? null,
            $establishment['geo_adresse'] ?? null,
        ]);

        $postalCode = $this->firstNonEmpty([
            $establishment['code_postal'] ?? null,
            $establishment['postcode'] ?? null,
        ]);

        $city = $this->firstNonEmpty([
            $establishment['libelle_commune'] ?? null,
            $establishment['commune'] ?? null,
            $establishment['ville'] ?? null,
        ]);

        $department = $this->firstNonEmpty([
            $establishment['departement'] ?? null,
            $establishment['libelle_departement']
                ?? null,
        ]);

        $region = $this->firstNonEmpty([
            $establishment['region'] ?? null,
            $establishment['libelle_region'] ?? null,
        ]);

        $latitude = $this->firstNonEmpty([
            $establishment['latitude'] ?? null,
        ]);

        $longitude = $this->firstNonEmpty([
            $establishment['longitude'] ?? null,
        ]);

        if ('' === $fullAddress) {
            $fullAddress = trim(
                implode(
                    ' ',
                    array_filter(
                        [
                            $postalCode,
                            $city,
                        ],
                        static fn (
                            mixed $value
                        ): bool => '' !== trim(
                            (string) $value
                        )
                    )
                )
            );
        }

        return [
            'fullAddress' => $fullAddress,
            'postalCode' => $postalCode,
            'city' => $city,
            'department' => $department,
            'region' => $region,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    /**
     * Recherche l’adresse complète dans Nominatim.
     *
     * @return array<string, mixed>
     */
    private function searchAddressWithNominatim(
        HttpClientInterface $httpClient,
        string $fullAddress,
        string $postalCode,
        string $city,
    ): array {
        $result = $this->performNominatimSearch(
            $httpClient,
            [
                'q' => $fullAddress,
                'format' => 'jsonv2',
                'addressdetails' => 1,
                'limit' => 1,
                'countrycodes' => 'fr',
                'accept-language' => 'fr',
                'email' => 'contact@trouvemoi.com',
            ]
        );

        if ([] !== $result) {
            return $result;
        }

        $structuredQuery = [
            'format' => 'jsonv2',
            'addressdetails' => 1,
            'limit' => 1,
            'countrycodes' => 'fr',
            'accept-language' => 'fr',
            'email' => 'contact@trouvemoi.com',
        ];

        if ('' !== $postalCode) {
            $structuredQuery['postalcode'] =
                $postalCode;
        }

        if ('' !== $city) {
            $structuredQuery['city'] = $city;
        }

        $street = $this->removeCityFromAddress(
            $fullAddress,
            $postalCode,
            $city
        );

        if ('' !== $street) {
            $structuredQuery['street'] = $street;
        }

        return $this->performNominatimSearch(
            $httpClient,
            $structuredQuery
        );
    }

    /**
     * Appelle Nominatim.
     *
     * @param array<string, int|string> $query
     *
     * @return array<string, mixed>
     */
    private function performNominatimSearch(
        HttpClientInterface $httpClient,
        array $query,
    ): array {
        try {
            $response = $httpClient->request(
                'GET',
                self::NOMINATIM_SEARCH_URL,
                [
                    'query' => $query,
                    'headers' => [
                        'Accept' => 'application/json',
                        'Accept-Language' =>
                            'fr-FR,fr;q=0.9',
                        'User-Agent' =>
                            'TrouveMoi/1.0 (contact@trouvemoi.com)',
                        'Referer' =>
                            'https://trouvemoi.com',
                    ],
                    'timeout' => 15,
                    'max_duration' => 20,
                ]
            );

            $statusCode =
                $response->getStatusCode();

            if (
                $statusCode < 200
                || $statusCode >= 300
            ) {
                $response->getContent(false);

                return [];
            }

            $rawContent =
                $response->getContent(false);

            $results =
                $this->decodeJsonResponse(
                    $rawContent
                );

            if (
                !isset($results[0])
                || !is_array($results[0])
            ) {
                return [];
            }

            return $results[0];
        } catch (\Throwable) {
            /*
             * Une erreur Nominatim ne doit pas empêcher
             * l’inscription.
             */
            return [];
        }
    }

    /**
     * Extrait le dirigeant personne physique.
     *
     * @return array{
     *     firstName: string,
     *     lastName: string,
     *     quality: string,
     *     type: string
     * }
     */
    private function extractManager(
        mixed $directors,
    ): array {
        $emptyManager = [
            'firstName' => '',
            'lastName' => '',
            'quality' => '',
            'type' => '',
        ];

        if (!is_array($directors)) {
            return $emptyManager;
        }

        foreach ($directors as $director) {
            if (!is_array($director)) {
                continue;
            }

            $firstNames = $this->firstNonEmpty([
                $director['prenoms'] ?? null,
                $director['prenom'] ?? null,
            ]);

            $lastName = $this->firstNonEmpty([
                $director['nom'] ?? null,
                $director['nom_usage'] ?? null,
            ]);

            $directorType = $this->firstNonEmpty([
                $director['type_dirigeant'] ?? null,
                $director['type'] ?? null,
            ]);

            $normalizedDirectorType =
                mb_strtolower($directorType);

            $isPhysicalPerson =
                str_contains(
                    $normalizedDirectorType,
                    'physique'
                )
                || (
                    '' !== $firstNames
                    && '' !== $lastName
                );

            if (!$isPhysicalPerson) {
                continue;
            }

            return [
                'firstName' =>
                    $this->extractFirstName(
                        $firstNames
                    ),

                'lastName' => $lastName,

                'quality' =>
                    $this->firstNonEmpty([
                        $director['qualite'] ?? null,
                        $director['fonction'] ?? null,
                    ]),

                'type' => $directorType,
            ];
        }

        return $emptyManager;
    }

    /**
     * Conserve le premier prénom complet.
     */
    private function extractFirstName(
        string $firstNames,
    ): string {
        $firstNames = trim($firstNames);

        if ('' === $firstNames) {
            return '';
        }

        /*
         * Les prénoms peuvent être séparés par des virgules
         * ou des points-virgules. On ne coupe pas les prénoms
         * composés contenant un tiret.
         */
        $parts = preg_split(
            '/[,;\/]+/u',
            $firstNames
        );

        if (
            !is_array($parts)
            || !isset($parts[0])
        ) {
            return $firstNames;
        }

        return trim($parts[0]);
    }

    /**
     * Décompose simplement le numéro et la rue
     * depuis l’adresse officielle.
     *
     * @return array{
     *     houseNumber: string,
     *     road: string
     * }
     */
    private function parseStreetFromFullAddress(
        string $fullAddress,
        string $postalCode,
        string $city,
    ): array {
        $street = $this->removeCityFromAddress(
            $fullAddress,
            $postalCode,
            $city
        );

        if ('' === $street) {
            return [
                'houseNumber' => '',
                'road' => '',
            ];
        }

        if (
            1 === preg_match(
                '/^(\d+(?:\s*(?:bis|ter|quater))?)\s+(.+)$/iu',
                $street,
                $matches
            )
        ) {
            return [
                'houseNumber' =>
                    trim((string) $matches[1]),

                'road' =>
                    trim((string) $matches[2]),
            ];
        }

        return [
            'houseNumber' => '',
            'road' => $street,
        ];
    }

    /**
     * Retire le code postal, la ville et le pays
     * de l’adresse complète.
     */
    private function removeCityFromAddress(
        string $fullAddress,
        string $postalCode,
        string $city,
    ): string {
        $street = trim($fullAddress);

        if ('' !== $postalCode) {
            $street = str_ireplace(
                $postalCode,
                '',
                $street
            );
        }

        if ('' !== $city) {
            $street = str_ireplace(
                $city,
                '',
                $street
            );
        }

        $street = str_ireplace(
            [
                ', France',
                ' France',
                'France',
            ],
            '',
            $street
        );

        $street = preg_replace(
            '/\s+/u',
            ' ',
            $street
        );

        return trim(
            (string) $street,
            " \t\n\r\0\x0B,"
        );
    }

    /**
     * Décode une réponse JSON sans produire d’exception.
     *
     * @return array<string|int, mixed>
     */
    private function decodeJsonResponse(
        string $content,
    ): array {
        if ('' === trim($content)) {
            return [];
        }

        try {
            $decoded = json_decode(
                $content,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            return [];
        }

        return is_array($decoded)
            ? $decoded
            : [];
    }

    /**
     * Extrait le message retourné par une API.
     *
     * @param array<string|int, mixed> $payload
     */
    private function extractApiErrorMessage(
        array $payload,
        string $defaultMessage,
    ): string {
        return $this->firstNonEmpty([
            $payload['message'] ?? null,
            $payload['detail'] ?? null,
            $payload['error'] ?? null,
            $payload['title'] ?? null,
            $defaultMessage,
        ]);
    }

    /**
     * Normalise un SIREN ou un SIRET.
     */
    private function normalizeIdentifier(
        mixed $value,
    ): string {
        if (
            null === $value
            || is_array($value)
            || is_object($value)
        ) {
            return '';
        }

        $identifier = preg_replace(
            '/\D+/',
            '',
            (string) $value
        );

        return is_string($identifier)
            ? $identifier
            : '';
    }

    /**
     * Retourne la première valeur textuelle non vide.
     *
     * @param array<int, mixed> $values
     */
    private function firstNonEmpty(
        array $values,
    ): string {
        foreach ($values as $value) {
            if (
                null === $value
                || is_array($value)
                || is_object($value)
                || is_bool($value)
            ) {
                continue;
            }

            $normalizedValue = trim(
                (string) $value
            );

            if ('' !== $normalizedValue) {
                return $normalizedValue;
            }
        }

        return '';
    }

    /**
     * Retourne la première chaîne non vide d’un tableau.
     */
    private function firstArrayValue(
        mixed $values,
    ): string {
        if (!is_array($values)) {
            return '';
        }

        foreach ($values as $value) {
            if (
                null === $value
                || is_array($value)
                || is_object($value)
                || is_bool($value)
            ) {
                continue;
            }

            $normalizedValue = trim(
                (string) $value
            );

            if ('' !== $normalizedValue) {
                return $normalizedValue;
            }
        }

        return '';
    }

    /**
     * Indique si Symfony est en mode debug.
     */
    private function isDebug(): bool
    {
        return true === $this->getParameter(
            'kernel.debug'
        );
    }
}