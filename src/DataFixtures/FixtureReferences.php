<?php

declare(strict_types=1);

namespace App\DataFixtures;

final class FixtureReferences
{
    public const MAX_TOTAL_RECORDS = 200;

    public const USER_COUNT = 50;
    public const ARTISAN_USER_COUNT = 15;
    public const COMMERCIAL_PARTNER_USER_COUNT = 5;
    public const CUSTOMER_USER_COUNT = 26;
    public const TEST_USER_COUNT = 4;

    public const ARTISAN_PROFILE_COUNT = 15;
    public const COMMERCIAL_PARTNER_PROFILE_COUNT = 5;
    public const RESET_PASSWORD_REQUEST_COUNT = 3;
    public const MARKETPLACE_RECORD_COUNT = 127;

    public const PUBLISHED_ARTISAN_PROFILE_COUNT = 12;
    public const VALIDATED_COMMERCIAL_PARTNER_PROFILE_COUNT = 4;

    public const USER_FIXTURES_SEED = 20260717;
    public const ARTISAN_PROFILE_FIXTURES_SEED = 20260718;
    public const COMMERCIAL_PARTNER_PROFILE_FIXTURES_SEED = 20260719;

    private function __construct()
    {
    }

    public static function customerUser(int $index): string
    {
        return sprintf('user.customer.%02d', $index);
    }

    public static function artisanUser(int $index): string
    {
        return sprintf('user.artisan.%02d', $index);
    }

    public static function commercialPartnerUser(int $index): string
    {
        return sprintf('user.partner.%02d', $index);
    }

    public static function testUser(string $name): string
    {
        return sprintf('user.test.%s', $name);
    }

    public static function artisanProfile(int $index): string
    {
        return sprintf('artisan_profile.%02d', $index);
    }

    public static function commercialPartnerProfile(int $index): string
    {
        return sprintf('commercial_partner_profile.%02d', $index);
    }

    public static function totalRecordCount(): int
    {
        return self::USER_COUNT
            + self::ARTISAN_PROFILE_COUNT
            + self::COMMERCIAL_PARTNER_PROFILE_COUNT
            + self::RESET_PASSWORD_REQUEST_COUNT
            + self::MARKETPLACE_RECORD_COUNT;
    }

    public static function assertLimits(): void
    {
        if (
            self::USER_COUNT
            !== self::CUSTOMER_USER_COUNT
                + self::ARTISAN_USER_COUNT
                + self::COMMERCIAL_PARTNER_USER_COUNT
                + self::TEST_USER_COUNT
        ) {
            throw new \LogicException('La repartition des utilisateurs est invalide.');
        }

        if (self::ARTISAN_PROFILE_COUNT > self::ARTISAN_USER_COUNT) {
            throw new \LogicException('Trop de profils artisan pour les utilisateurs artisan.');
        }

        if (
            self::COMMERCIAL_PARTNER_PROFILE_COUNT
            > self::COMMERCIAL_PARTNER_USER_COUNT
        ) {
            throw new \LogicException('Trop de profils partenaires pour les utilisateurs partenaires.');
        }

        if (self::RESET_PASSWORD_REQUEST_COUNT > self::CUSTOMER_USER_COUNT) {
            throw new \LogicException('Trop de demandes de reinitialisation pour les clients.');
        }

        if (self::totalRecordCount() > self::MAX_TOTAL_RECORDS) {
            throw new \LogicException('Le jeu de fixtures depasse la limite maximale de 200 donnees.');
        }
    }
}
