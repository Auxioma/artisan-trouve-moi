<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Scheduling\Appointment;
use App\Entity\Billing\SubscriptionPlan;
use App\Entity\Users\UserProfile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\Persistence\ObjectManager;

final class AppointmentFixtures extends Fixture implements \Doctrine\Common\DataFixtures\DependentFixtureInterface
{
    private const ENTITY_CLASS = Appointment::class;
    private const RECORDS_PER_ENTITY = 1000;
    public function getDependencies(): array
    {
        return [ArtisanProfileFixtures::class, UserFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $metadata = $manager->getClassMetadata(self::ENTITY_CLASS);
        for ($index = 1; $index <= $this->recordCount(); ++$index) {
            $entity = new Appointment();
            $this->populateFields($metadata, $entity, $index);
            $this->populateAssociations($metadata, $entity, $index);
            $manager->persist($entity);
            $this->addReference($this->reference(self::ENTITY_CLASS, $index), $entity);
            if (0 === $index % 100) { $manager->flush(); }
        }
        $manager->flush();
    }

    private function recordCount(): int
    {
        if (SubscriptionPlan::class === self::ENTITY_CLASS) {
            return count(\App\Entity\Enum\SubscriptionPlanCode::cases());
        }

        return self::RECORDS_PER_ENTITY;
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
            'boolean' => 0 === $index % 2,
            'integer', 'smallint', 'bigint' => $index,
            'decimal', 'float' => number_format(10 + ($index / 10), $mapping->scale ?? 2, '.', ''),
            'datetime', 'datetime_immutable', 'datetimetz', 'datetimetz_immutable' => new \DateTimeImmutable(sprintf('2026-01-01 +%d minutes', $index)),
            'date', 'date_immutable' => new \DateTimeImmutable(sprintf('2026-01-01 +%d days', $index)),
            'time', 'time_immutable' => new \DateTime(sprintf('08:%02d:00', $index % 60)),
            'json', 'array', 'simple_array' => ['fixture', (string) $index],
            default => $this->stringValue($mapping, $index),
        };
    }

    private function stringValue(FieldMapping $mapping, int $index): string
    {
        $value = sprintf('%s-%06d', $mapping->fieldName, $index);
        if (str_contains(strtolower($mapping->fieldName), 'email')) {
            $value = sprintf('fixture-%06d@example.test', $index);
        }

        return null === $mapping->length ? $value : substr($value, 0, $mapping->length);
    }

    private function populateAssociations(ClassMetadata $metadata, object $entity, int $index): void
    {
        foreach ($metadata->getAssociationMappings() as $association) {
            if (!$association->isToOneOwningSide() || !$this->shouldPopulateAssociation($association, $metadata->name)) {
                continue;
            }

            $target = $association->targetEntity;
            $targetCount = SubscriptionPlan::class === $target
                ? count(\App\Entity\Enum\SubscriptionPlanCode::cases())
                : self::RECORDS_PER_ENTITY;
            $targetIndex = (($index - 1) % $targetCount) + 1;
            $metadata->setFieldValue($entity, $association->fieldName, $this->getReference($this->reference($target, $targetIndex), $target));
        }
    }

    private function shouldPopulateAssociation(AssociationMapping $association, string $source): bool
    {
        if ($association->targetEntity === $source) {
            return false;
        }

        if (UserProfile::class === self::ENTITY_CLASS && 'user' === $association->fieldName) {
            return true;
        }

        return isset($association->joinColumns[0]) && false === $association->joinColumns[0]->nullable;
    }

    private function reference(string $class, int $index): string
    {
        return sprintf('%s.%06d', (new \ReflectionClass($class))->getShortName(), $index);
    }
}

