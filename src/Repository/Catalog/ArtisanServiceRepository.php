<?php

declare(strict_types=1);

namespace App\Repository\Catalog;

use App\Entity\Catalog\ArtisanService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArtisanService>
 */
class ArtisanServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArtisanService::class);
    }
}
