<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\Users\ArtisanNotificationPreferences;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArtisanNotificationPreferences>
 */
class ArtisanNotificationPreferencesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ArtisanNotificationPreferences::class
        );
    }
}
