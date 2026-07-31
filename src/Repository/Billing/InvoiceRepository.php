<?php

declare(strict_types=1);

namespace App\Repository\Billing;

use App\Entity\Billing\Subscription;
use App\Entity\Billing\Invoice;
use App\Entity\Enum\InvoiceStatus;
use App\Entity\Users\ArtisanProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    public function findLatestPaidForSubscription(Subscription $subscription): ?Invoice
    {
        $db = $this->createQueryBuilder('invoice')
            ->andWhere('invoice.subscription = :subscription')
            ->andWhere('invoice.status = :status')
            ->setParameter('subscription', $subscription)
            ->setParameter('status', InvoiceStatus::PAID)
            ->orderBy('invoice.paidAt', 'DESC')
            ->addOrderBy('invoice.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        return $db;
    }

    public function findLatestPaidForArtisan(ArtisanProfile $artisan): ?Invoice
    {
        return $this->createQueryBuilder('invoice')
            ->innerJoin('invoice.subscription', 'subscription')
            ->addSelect('subscription')
            ->innerJoin('subscription.plan', 'plan')
            ->addSelect('plan')
            ->andWhere('subscription.artisanProfile = :artisan')
            ->andWhere('invoice.status = :status')
            ->setParameter('artisan', $artisan)
            ->setParameter('status', InvoiceStatus::PAID)
            ->orderBy('invoice.paidAt', 'DESC')
            ->addOrderBy('invoice.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<Invoice> */
    public function findAllForArtisan(ArtisanProfile $artisan): array
    {
        return $this->createQueryBuilder('invoice')
            ->innerJoin('invoice.subscription', 'subscription')
            ->addSelect('subscription')
            ->innerJoin('subscription.plan', 'plan')
            ->addSelect('plan')
            ->andWhere('subscription.artisanProfile = :artisan')
            ->setParameter('artisan', $artisan)
            ->orderBy('invoice.issuedAt', 'DESC')
            ->addOrderBy('invoice.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

}
