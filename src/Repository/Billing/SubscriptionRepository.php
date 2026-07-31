<?php

declare(strict_types=1);

namespace App\Repository\Billing;

use App\Entity\Billing\Subscription;
use App\Entity\Users\ArtisanProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    public function findLatestForArtisan(ArtisanProfile $artisan): ?Subscription
    {
        return $this->createQueryBuilder('subscription')
            ->andWhere('subscription.artisanProfile = :artisan')
            ->setParameter('artisan', $artisan)
            // La dernière offre correspond à la souscription la plus récemment
            // commencée, et non à une date de création technique éventuellement
            // antérieure ou incohérente avec l’activation Stripe.
            ->orderBy('subscription.startsAt', 'DESC')
            ->addOrderBy('subscription.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
