<?php

declare(strict_types=1);

namespace App\Repository\Geo;

use App\Entity\Geo\InterventionZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InterventionZone>
 */
class InterventionZoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InterventionZone::class);
    }
}
