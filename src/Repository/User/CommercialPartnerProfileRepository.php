<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\Users\CommercialPartnerProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommercialPartnerProfile>
 */
final class CommercialPartnerProfileRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, CommercialPartnerProfile::class);
    }

    public function save(
        CommercialPartnerProfile $partnerProfile,
        bool $flush = false,
    ): void {
        $this->getEntityManager()->persist($partnerProfile);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(
        CommercialPartnerProfile $partnerProfile,
        bool $flush = false,
    ): void {
        $this->getEntityManager()->remove($partnerProfile);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return list<CommercialPartnerProfile>
     */
    public function findActivePartners(): array
    {
        return $this->createQueryBuilder('partner')
            ->andWhere('partner.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('partner.companyName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
