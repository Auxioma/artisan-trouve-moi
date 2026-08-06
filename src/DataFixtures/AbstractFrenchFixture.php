<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Billing\SubscriptionPlan;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\HttpFoundation\File\File;

abstract class AbstractFrenchFixture extends Fixture
{
    protected const ENTITY_CLASS = '';
    /** @var list<class-string> */
    protected const DEPENDENCIES = [];
    protected const RECORDS_PER_ENTITY = 6;
    private Generator $faker;

    /** @var list<array{city: string, postalCode: string, department: string, region: string, latitude: string, longitude: string}> */
    private const FRENCH_LOCATIONS = [
        ['city' => 'Lyon', 'postalCode' => '69003', 'department' => 'Rhône', 'region' => 'Auvergne-Rhône-Alpes', 'latitude' => '45.760945', 'longitude' => '4.859833'],
        ['city' => 'Nantes', 'postalCode' => '44000', 'department' => 'Loire-Atlantique', 'region' => 'Pays de la Loire', 'latitude' => '47.218371', 'longitude' => '-1.553621'],
        ['city' => 'Bordeaux', 'postalCode' => '33000', 'department' => 'Gironde', 'region' => 'Nouvelle-Aquitaine', 'latitude' => '44.837789', 'longitude' => '-0.579180'],
        ['city' => 'Rennes', 'postalCode' => '35000', 'department' => 'Ille-et-Vilaine', 'region' => 'Bretagne', 'latitude' => '48.117266', 'longitude' => '-1.677793'],
        ['city' => 'Toulouse', 'postalCode' => '31000', 'department' => 'Haute-Garonne', 'region' => 'Occitanie', 'latitude' => '43.604652', 'longitude' => '1.444209'],
        ['city' => 'Strasbourg', 'postalCode' => '67000', 'department' => 'Bas-Rhin', 'region' => 'Grand Est', 'latitude' => '48.573405', 'longitude' => '7.752111'],
    ];

    /** @var list<array{firstName: string, lastName: string, company: string, domain: string}> */
    private const FRENCH_PROFESSIONALS = [
        ['firstName' => 'Claire', 'lastName' => 'Martin', 'company' => 'Eiffage Énergie Systèmes', 'domain' => 'eiffage.com'],
        ['firstName' => 'Julien', 'lastName' => 'Bernard', 'company' => 'SPIE Building Solutions', 'domain' => 'spie.com'],
        ['firstName' => 'Élodie', 'lastName' => 'Moreau', 'company' => 'Saint-Gobain Distribution Bâtiment', 'domain' => 'saint-gobain.com'],
        ['firstName' => 'Thomas', 'lastName' => 'Petit', 'company' => 'Point.P', 'domain' => 'pointp.fr'],
        ['firstName' => 'Sophie', 'lastName' => 'Robert', 'company' => 'Rexel France', 'domain' => 'rexel.fr'],
        ['firstName' => 'Nicolas', 'lastName' => 'Lefèvre', 'company' => 'Leroy Merlin France', 'domain' => 'leroymerlin.fr'],
    ];

    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
        $this->faker->seed(20260724 + crc32(static::class));
    }

    public function load(ObjectManager $manager): void
    {
        $class = static::ENTITY_CLASS;
        $metadata = $manager->getClassMetadata($class);

        for ($index = 1; $index <= $this->recordCount(); ++$index) {
            $entity = $this->createEntity($manager, $index);
            $this->populateFields($metadata, $entity, $index);
            $this->populateAssociations($metadata, $entity, $index);
            $this->populateUploadFile($entity);
            $this->afterPopulate($entity, $index);
            $manager->persist($entity);
            $this->addReference($this->reference($class, $index), $entity);
        }

        $manager->flush();
    }

    protected function afterPopulate(object $entity, int $index): void
    {
    }

    protected function createEntity(ObjectManager $manager, int $index): object
    {
        $class = static::ENTITY_CLASS;

        return new $class();
    }

    protected function recordCount(): int
    {
        return SubscriptionPlan::class === static::ENTITY_CLASS
            ? count(\App\Entity\Enum\SubscriptionPlanCode::cases())
            : static::RECORDS_PER_ENTITY;
    }

    protected function reference(string $class, int $index): string
    {
        return sprintf('%s.%06d', (new \ReflectionClass($class))->getShortName(), $index);
    }

    private function populateFields(ClassMetadata $metadata, object $entity, int $index): void
    {
        foreach ($metadata->getFieldNames() as $field) {
            $mapping = $metadata->getFieldMapping($field);
            if ($mapping->id || in_array($field, ['password', 'roles'], true) || $this->isRuntimeFileMetadata($field)) {
                continue;
            }
            $metadata->setFieldValue($entity, $field, $this->fieldValue($mapping, $index, $entity::class));
        }
    }

    private function fieldValue(FieldMapping $mapping, int $index, string $entityClass): mixed
    {
        if (null !== $mapping->enumType) {
            $cases = $mapping->enumType::cases();

            return $cases[($index - 1) % count($cases)];
        }

        return match ($mapping->type) {
            'boolean' => $this->booleanValue($mapping->fieldName, $index),
            'integer', 'smallint', 'bigint' => $this->integerValue($mapping->fieldName, $index),
            'decimal', 'float' => $this->decimalValue($mapping, $index),
            'datetime', 'datetime_immutable', 'datetimetz', 'datetimetz_immutable', 'date', 'date_immutable' => $this->dateValue($mapping->fieldName, $index),
            'time', 'time_immutable' => \DateTimeImmutable::createFromMutable($this->faker->dateTime('H:i:s', 'Europe/Paris')),
            'json', 'array', 'simple_array' => $this->arrayValue($mapping->fieldName, $index, $entityClass),
            default => $this->stringValue($mapping, $index, $entityClass),
        };
    }

    private function integerValue(string $field, int $index): int
    {
        return match ($field) {
            'position' => $index,
            'expiresMonth' => (($index + 5) % 12) + 1,
            'expiresYear' => 2028 + ($index % 4),
            'experienceYears' => 5 + $index,
            'progressPercent' => min(100, $index * 15),
            'maxQuotes' => 5,
            'quotesCount' => min(4, $index),
            'viewsCount' => 12 * $index,
            'workDurationDays' => 2 + $index,
            'warrantyMonths' => 12,
            'trialDays' => 30,
            'maxQuotesPerMonth' => 10 + $index * 5,
            'maxCategories' => 2 + $index,
            'maxPhotos' => 5 + $index,
            default => match (true) {
            str_contains(strtolower($field), 'year') => $this->faker->numberBetween(2026, 2034),
            str_contains(strtolower($field), 'month') => $this->faker->numberBetween(1, 12),
            str_contains(strtolower($field), 'rating') => $this->faker->numberBetween(3, 5),
            str_contains(strtolower($field), 'percent') => $this->faker->numberBetween(0, 100),
            str_contains(strtolower($field), 'radius') => $this->faker->numberBetween(10, 80),
            default => $this->faker->numberBetween(1, 50),
            },
        };
    }

    private function booleanValue(string $field, int $index): bool
    {
        return match ($field) {
            'isArchivedByClient', 'isArchivedByArtisan', 'cancelAtPeriodEnd', 'isSystem', 'isCover', 'receivesCustomers' => false,
            'isDefault', 'isBillingAddress', 'isPublic', 'isGeocoded', 'isPublished', 'isActive',
            'isVerified', 'isPhoneVerified', 'hasAcceptedTerms', 'hasAcceptedPrivacyPolicy',
            'newRequestsEnabled', 'urgentRequestsSmsEnabled', 'clientMessagesEnabled', 'newReviewsEnabled',
            'quoteRemindersEnabled', 'weeklySummaryEnabled', 'newQuotesEnabled', 'artisanMessagesEnabled',
            'appointmentRemindersEnabled', 'reviewInvitationsEnabled', 'profileVisibleToArtisans',
            'phoneSharedAfterAcceptance', 'isRegisteredInRne', 'professionalLiabilityInsuranceRequired',
            'hasProfessionalLiabilityInsurance', 'decennialInsuranceRequired', 'hasDecennialInsurance',
            'wouldRecommend' => true,
            'marketingConsent', 'tipsAndNewsEnabled', 'underQualifiedPersonControl' => false,
            'hasUrgentAccess' => $index >= 2,
            'hasPriorityRanking', 'isPopular' => $index >= 3,
            default => 0 !== $index % 2,
        };
    }

    /** @return array<string, mixed>|list<string> */
    private function arrayValue(string $field, int $index, string $entityClass): array
    {
        if ($entityClass === SubscriptionPlan::class && 'features' === $field) {
            return match ($index) {
                1 => ['10 devis par mois', '2 catégories', '5 photos de réalisations'],
                2 => ['30 devis par mois', '5 catégories', '15 photos', 'Accès aux demandes urgentes'],
                default => ['Devis illimités', 'Catégories et photos illimitées', 'Demandes urgentes', 'Mise en avant prioritaire'],
            };
        }

        return ['langue' => 'fr', 'ville' => self::FRENCH_LOCATIONS[($index - 1) % count(self::FRENCH_LOCATIONS)]['city']];
    }

    private function decimalValue(FieldMapping $mapping, int $index): string
    {
        $field = strtolower($mapping->fieldName);
        $amount = 420 + $index * 95;
        $value = match ($field) {
            'vatrate' => 20,
            'quantity' => 1,
            'budgetmin' => 700 + $index * 100,
            'budgetmax' => 1800 + $index * 150,
            'surfacem2' => 35 + $index * 12,
            'monthlypriceht' => [19, 39, 69][($index - 1) % 3],
            'yearlypriceht' => [190, 390, 690][($index - 1) % 3],
            'pricefrom', 'unitpriceht', 'totalht', 'amountht' => $amount,
            'totalvat', 'amountvat' => $amount * .2,
            'totalttc', 'amountttc' => $amount * 1.2,
            'discountht' => 0,
            'depositpercent' => 30,
            default => null,
        };
        if (null !== $value) {
            return number_format($value, $mapping->scale ?? 2, '.', '');
        }

        $maximum = str_contains($field, 'longitude')
            ? 9.7
            : (str_contains($field, 'latitude')
                ? 51.1
                : (str_contains($field, 'vat') || str_contains($field, 'rate') || str_contains($field, 'percent') || str_contains($field, 'commission')
                    ? 20
                    : 2500));
        $minimum = str_contains($field, 'longitude') ? -5.2 : (str_contains($field, 'latitude') ? 41.3 : 0);

        return number_format($this->faker->randomFloat(2, $minimum, $maximum), $mapping->scale ?? 2, '.', '');
    }

    private function stringValue(FieldMapping $mapping, int $index, string $entityClass): string
    {
        $field = strtolower($mapping->fieldName);
        $professional = self::FRENCH_PROFESSIONALS[($index - 1) % count(self::FRENCH_PROFESSIONALS)];
        $location = self::FRENCH_LOCATIONS[($index - 1) % count(self::FRENCH_LOCATIONS)];
        $street = sprintf('%d rue %s', 4 + $index * 3, ['des Tilleuls', 'de la République', 'du Marché', 'des Artisans', 'Jean-Jaurès', 'des Carmes'][($index - 1) % 6]);

        $value = match (true) {
            'email' === $field => sprintf('%s.%s-%d@%s', $this->slugPart($professional['firstName']), $this->slugPart($professional['lastName']), $index, $professional['domain']),
            'businessemail' === $field => sprintf('contact@%s', $professional['domain']),
            'locale' === $field => 'fr',
            'timezone' === $field => 'Europe/Paris',
            str_contains($field, 'phone') => sprintf('+33 6 %02d %02d %02d %02d', 10 + $index, 20 + $index, 30 + $index, 40 + $index),
            str_contains($field, 'postal') => $location['postalCode'],
            str_contains($field, 'countrycode') => 'FR',
            str_contains($field, 'country') => 'France',
            str_contains($field, 'city') || str_contains($field, 'town') || str_contains($field, 'municipality') => $location['city'],
            'department' === $field || 'county' === $field => $location['department'],
            'region' === $field || 'state' === $field => $location['region'],
            str_contains($field, 'address') || str_contains($field, 'road') => $street,
            'housenumber' === $field => (string) (4 + $index * 3),
            'latitude' === $field => $location['latitude'],
            'longitude' === $field => $location['longitude'],
            'firstname' === $field => $professional['firstName'],
            'lastname' === $field => $professional['lastName'],
            in_array($field, ['legalname', 'commercialname', 'companyname', 'billingname'], true) => $professional['company'],
            'holdername' === $field => sprintf('%s %s', $professional['firstName'], $professional['lastName']),
            'slug' === $field => $entityClass === \App\Entity\Catalog\Category::class
                ? ['travaux-renovation', 'plomberie', 'electricite', 'menuiserie', 'peinture', 'couverture'][($index - 1) % 6]
                : sprintf('%s-%s', $this->slugPart($professional['company']), $index),
            'siren' === $field => sprintf('732%06d', $index),
            'siret' === $field => sprintf('732%06d0001%d', $index, $index),
            'vatnumber' === $field => sprintf('FR%02d732%06d', 20 + $index, $index),
            'apecode' === $field => ['43.22A', '43.21A', '43.32A', '43.33Z', '43.34Z', '43.31Z'][($index - 1) % 6],
            'legalform' === $field => 'SARL',
            'stripecustomerid' === $field => sprintf('cus_fr_%06d', $index),
            str_contains($field, 'provider') || str_contains($field, 'sessiontoken') => sprintf('fr_%s_%06d', $field, $index),
            'reference' === $field => sprintf('%s-2026-%04d', $this->referencePrefix($entityClass), $index),
            'brand' === $field => ['Visa', 'Mastercard', 'Carte Bancaire'][($index - 1) % 3],
            'last4' === $field => sprintf('%04d', 1200 + $index),
            'ip' === $field || str_contains($field, 'signatureip') => sprintf('192.0.2.%d', 10 + $index),
            'title' === $field => $this->titleFor($entityClass, $index),
            'name' === $field => match ($entityClass) {
                \App\Entity\Catalog\Category::class => ['Travaux et rénovation', 'Plomberie', 'Électricité', 'Menuiserie', 'Peinture', 'Couverture'][($index - 1) % 6],
                SubscriptionPlan::class => ['Essentiel', 'Premium', 'Excellence'][($index - 1) % 3],
                default => $professional['company'],
            },
            'label' === $field => ['Diagnostic sur place', 'Préparation du chantier', 'Réalisation des travaux', 'Contrôle de finition', 'Livraison du chantier', 'Visite de réception'][($index - 1) % 6],
            'description' === $field || 'content' === $field || 'message' === $field || 'notes' === $field || 'internalnotes' === $field || 'comment' === $field || 'response' === $field || 'termsandconditions' === $field => $this->frenchText($entityClass, $index),
            'formattedaddress' === $field || 'billingaddress' === $field || 'location' === $field || 'osmdisplayname' === $field => sprintf('%s, %s %s, France', $street, $location['postalCode'], $location['city']),
            'district' === $field || 'citydistrict' === $field || 'neighbourhood' === $field => 'Centre-ville',
            'suburb' === $field => 'Quartier Saint-Cyprien',
            'hamlet' === $field || 'village' === $field => 'Les Tilleuls',
            'addresscomplement' === $field => 'Bâtiment B, 2e étage',
            'propertytype' === $field => ['Appartement', 'Maison individuelle', 'Local commercial'][($index - 1) % 3],
            'accessdetails' === $field => 'Interphone Martin, stationnement possible dans la cour intérieure.',
            'availabilitynote' === $field => 'Disponible en semaine après 17 h et le samedi matin.',
            'source' === $field => 'site_web',
            'unit' === $field => 'u',
            'priceunit' === $field => 'forfait',
            'icon' === $field => ['wrench', 'bolt', 'hammer', 'paint-roller', 'trowel', 'house'][($index - 1) % 6],
            'type' === $field => 'HOME',
            'contractreference' === $field => sprintf('CP-2026-%04d', $index),
            'qualificationtitle' === $field => 'CAP Installateur sanitaire',
            'qualificationnumber' === $field => sprintf('CAP-%04d-2020', $index),
            'qualifiedpersonposition' === $field => 'Gérant techniquement qualifié',
            'professionalliabilityinsurer' === $field || 'decennialinsurer' === $field => 'MAAF Pro',
            'professionalliabilitypolicynumber' === $field || 'decennialpolicynumber' === $field => sprintf('POL-FR-2026-%05d', $index),
            'decennialgeographicalcoverage' === $field => 'France métropolitaine',
            'rejectionreason' === $field => 'Document illisible : merci de transmettre une version complète et en cours de validité.',
            'cancellationreason' === $field || 'refusalreason' === $field => 'Le calendrier du client ne permet plus de réaliser les travaux.',
            'travelnote' === $field => 'Déplacement inclus dans un rayon de 25 km ; au-delà, devis préalable.',
            'devicelabel' === $field => ['Chrome sous Windows', 'Safari sur iPhone', 'Firefox sous macOS'][($index - 1) % 3],
            'useragent' === $field => 'Mozilla/5.0 (compatible; ArtisanTrouveMoi/1.0)',
            default => sprintf('information-francaise-%d', $index),
        };

        return null === $mapping->length ? $value : mb_substr($value, 0, $mapping->length);
    }

    private function populateAssociations(ClassMetadata $metadata, object $entity, int $index): void
    {
        foreach ($metadata->getAssociationMappings() as $association) {
            if (!$association->isToOneOwningSide() || !$this->shouldPopulateAssociation($association, $metadata->name)) {
                continue;
            }
            $target = $association->targetEntity;
            $targetCount = SubscriptionPlan::class === $target ? count(\App\Entity\Enum\SubscriptionPlanCode::cases()) : static::RECORDS_PER_ENTITY;
            $targetIndex = (($index - 1) % $targetCount) + 1;
            $metadata->setFieldValue($entity, $association->fieldName, $this->getReference($this->reference($target, $targetIndex), $target));
        }
    }

    private function shouldPopulateAssociation(AssociationMapping $association, string $source): bool
    {
        return $association->targetEntity !== $source
            && !($source === \App\Entity\Requests\ServiceRequest::class && 'awardedQuote' === $association->fieldName);
    }

    private function isRuntimeFileMetadata(string $field): bool
    {
        return in_array($field, [
            'avatarFilename', 'imageName', 'imageSize', 'imageMimeType',
            'documentName', 'documentSize', 'documentMimeType',
            'professionalLiabilityDocumentName', 'professionalLiabilityDocumentSize', 'professionalLiabilityDocumentMimeType',
            'decennialInsuranceDocumentName', 'decennialInsuranceDocumentSize', 'decennialInsuranceDocumentMimeType',
            'pdfFilename',
        ], true);
    }

    private function populateUploadFile(object $entity): void
    {
        $mediaDirectory = dirname(__DIR__, 2).'/fixtures/media/';
        $avatar = $mediaDirectory.'avatar.jpg';
        $worksite = $mediaDirectory.'worksite.jpg';

        if (method_exists($entity, 'setAvatarFile') && is_file($avatar)) {
            $entity->setAvatarFile(new File($avatar));
        }

        foreach ([
            'setImageFile',
            'setDocumentFile',
            'setProfessionalLiabilityDocumentFile',
            'setDecennialInsuranceDocumentFile',
        ] as $method) {
            if (method_exists($entity, $method) && is_file($worksite)) {
                $entity->{$method}(new File($worksite));
            }
        }
    }

    private function dateValue(string $field, int $index): \DateTimeImmutable
    {
        $created = new \DateTimeImmutable(sprintf('2026-01-%02d 09:00:00', min(28, $index + 2)), new \DateTimeZone('Europe/Paris'));

        return match (true) {
            'createdAt' === $field => $created,
            'updatedAt' === $field => $created->modify('+2 days'),
            str_contains(strtolower($field), 'expires') || str_contains(strtolower($field), 'validuntil') || str_contains(strtolower($field), 'dueat') => $created->modify('+30 days'),
            str_contains(strtolower($field), 'endsat') || str_contains(strtolower($field), 'periodends') => $created->modify('+14 days'),
            str_contains(strtolower($field), 'startsat') || str_contains(strtolower($field), 'periodstarts') || str_contains(strtolower($field), 'canstart') || str_contains(strtolower($field), 'desiredstart') => $created->modify('+7 days'),
            str_contains(strtolower($field), 'obtained') => $created->modify('-6 years'),
            default => $created->modify(sprintf('+%d days', $index)),
        };
    }

    private function titleFor(string $entityClass, int $index): string
    {
        return match ($entityClass) {
            \App\Entity\Requests\ServiceRequest::class => ['Remplacement d’un chauffe-eau', 'Mise aux normes du tableau électrique', 'Pose de fenêtres double vitrage', 'Réfection complète de salle de bains', 'Ravalement de façade', 'Recherche de fuite toiture'][($index - 1) % 6],
            \App\Entity\Projects\Project::class => ['Rénovation de la cuisine familiale', 'Sécurisation de l’installation électrique', 'Aménagement de combles', 'Création d’une douche à l’italienne', 'Peinture du séjour', 'Réparation de la couverture'][($index - 1) % 6],
            \App\Entity\Catalog\ArtisanService::class => ['Dépannage plomberie', 'Mise aux normes électrique', 'Pose de menuiseries sur mesure', 'Peinture intérieure soignée', 'Petite maçonnerie', 'Entretien de toiture'][($index - 1) % 6],
            default => ['Visite technique au domicile', 'Échange concernant le devis', 'Point d’avancement du chantier', 'Rendez-vous de réception', 'Conseil avant travaux', 'Intervention planifiée'][($index - 1) % 6],
        };
    }

    private function frenchText(string $entityClass, int $index): string
    {
        return match ($entityClass) {
            \App\Entity\Requests\ServiceRequest::class => 'Je souhaite une intervention sérieuse et un devis détaillé. Le logement est occupé et les travaux devront être réalisés avec soin.',
            \App\Entity\Quotes\Quote::class => 'Merci pour votre demande. Ce devis comprend la fourniture, la pose et le nettoyage du chantier en fin d’intervention.',
            \App\Entity\Messaging\Message::class => 'Bonjour, je vous confirme que le créneau proposé me convient. Pouvez-vous me préciser la durée prévue de l’intervention ?',
            \App\Entity\Reviews\Review::class => 'Travail propre et ponctuel. Les explications étaient claires et le chantier a été laissé impeccable.',
            default => sprintf('Entreprise locale reconnue, intervention soignée et devis transparent pour ce projet n°%d.', $index),
        };
    }

    private function referencePrefix(string $entityClass): string
    {
        return match ($entityClass) {
            \App\Entity\Billing\Invoice::class => 'FAC',
            \App\Entity\Quotes\Quote::class => 'DEV',
            \App\Entity\Projects\Project::class => 'PRJ',
            default => 'REF',
        };
    }

    private function slugPart(string $value): string
    {
        $value = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value));

        return trim((string) preg_replace('/[^a-z0-9]+/', '-', $value), '-');
    }
}
