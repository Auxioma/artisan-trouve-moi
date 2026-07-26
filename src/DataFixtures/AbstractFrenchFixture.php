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

abstract class AbstractFrenchFixture extends Fixture
{
    protected const ENTITY_CLASS = '';
    /** @var list<class-string> */
    protected const DEPENDENCIES = [];
    protected const RECORDS_PER_ENTITY = 6;
    private Generator $faker;

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
            if ($mapping->id || in_array($field, ['password', 'roles'], true)) {
                continue;
            }
            $metadata->setFieldValue($entity, $field, $this->fieldValue($mapping, $index));
        }
    }

    private function fieldValue(FieldMapping $mapping, int $index): mixed
    {
        if (null !== $mapping->enumType) {
            $cases = $mapping->enumType::cases();

            return $cases[($index - 1) % count($cases)];
        }

        return match ($mapping->type) {
            'boolean' => $this->faker->boolean(75),
            'integer', 'smallint', 'bigint' => $this->integerValue($mapping->fieldName),
            'decimal', 'float' => $this->decimalValue($mapping),
            'datetime', 'datetime_immutable', 'datetimetz', 'datetimetz_immutable' => \DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-18 months', '+6 months', 'Europe/Paris')),
            'date', 'date_immutable' => \DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-18 months', '+6 months', 'Europe/Paris')),
            'time', 'time_immutable' => \DateTimeImmutable::createFromMutable($this->faker->dateTime('H:i:s', 'Europe/Paris')),
            'json', 'array', 'simple_array' => ['langue' => 'fr', 'ville' => $this->faker->city()],
            default => $this->stringValue($mapping, $index),
        };
    }

    private function integerValue(string $field): int
    {
        return match (true) {
            str_contains(strtolower($field), 'year') => $this->faker->numberBetween(2026, 2034),
            str_contains(strtolower($field), 'month') => $this->faker->numberBetween(1, 12),
            str_contains(strtolower($field), 'rating') => $this->faker->numberBetween(3, 5),
            str_contains(strtolower($field), 'percent') => $this->faker->numberBetween(0, 100),
            str_contains(strtolower($field), 'radius') => $this->faker->numberBetween(10, 80),
            default => $this->faker->numberBetween(1, 50),
        };
    }

    private function decimalValue(FieldMapping $mapping): string
    {
        $field = strtolower($mapping->fieldName);
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

    private function stringValue(FieldMapping $mapping, int $index): string
    {
        $field = strtolower($mapping->fieldName);
        $value = match (true) {
            str_contains($field, 'email') => sprintf('demo.%s.%d@artisan-trouve-moi.fr', $this->faker->userName(), $index),
            'locale' === $field => 'fr',
            'timezone' === $field => 'Europe/Paris',
            str_contains($field, 'phone') => $this->faker->e164PhoneNumber(),
            str_contains($field, 'postal') => $this->faker->postcode(),
            str_contains($field, 'countrycode') => 'FR',
            str_contains($field, 'country') => 'France',
            str_contains($field, 'city') || str_contains($field, 'town') || str_contains($field, 'municipality') => $this->faker->city(),
            str_contains($field, 'address') || str_contains($field, 'road') => $this->faker->streetAddress(),
            str_contains($field, 'region') => $this->faker->region(),
            str_contains($field, 'latitude') => (string) $this->faker->latitude(41.3, 51.1),
            str_contains($field, 'longitude') => (string) $this->faker->longitude(-5.2, 9.7),
            str_contains($field, 'name') || str_contains($field, 'title') || str_contains($field, 'label') => $this->faker->sentence(3),
            str_contains($field, 'description') || str_contains($field, 'content') || str_contains($field, 'message') || str_contains($field, 'note') || str_contains($field, 'comment') => $this->faker->paragraph(2),
            str_contains($field, 'first') => $this->faker->firstName(),
            str_contains($field, 'last') => $this->faker->lastName(),
            str_contains($field, 'siren') => (string) $this->faker->numberBetween(100000000, 999999999),
            str_contains($field, 'siret') => (string) $this->faker->numberBetween(10000000000000, 99999999999999),
            str_contains($field, 'ip') => $this->faker->ipv4(),
            default => sprintf('%s-%s', $this->faker->word(), $index),
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
        return $association->targetEntity !== $source && isset($association->joinColumns[0]) && false === $association->joinColumns[0]->nullable;
    }
}
