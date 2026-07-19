<?php

declare(strict_types=1);

namespace App\Repository\Requests;

use App\Entity\Requests\RequestPhoto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RequestPhoto>
 */
class RequestPhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RequestPhoto::class);
    }
}
