<?php

declare(strict_types=1);

namespace App\Repository\Geo;

use App\Entity\Geo\InterventionCity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InterventionCity>
 */
class InterventionCityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InterventionCity::class);
    }
}
