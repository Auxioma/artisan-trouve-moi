<?php

declare(strict_types=1);

namespace App\Repository\Verification;

use App\Entity\Verification\VerificationDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VerificationDocument>
 */
class VerificationDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VerificationDocument::class);
    }
}
