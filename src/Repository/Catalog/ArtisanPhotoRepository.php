<?php

declare(strict_types=1);

namespace App\Repository\Catalog;

use App\Entity\Catalog\ArtisanPhoto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArtisanPhoto>
 */
class ArtisanPhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArtisanPhoto::class);
    }
}
