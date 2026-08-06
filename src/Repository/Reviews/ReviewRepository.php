<?php

declare(strict_types=1);

namespace App\Repository\Reviews;

use App\Entity\Reviews\Review;
use App\Entity\Users\ArtisanProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * @return array{averageRating: float, reviewCount: int}
     */
    public function getPublishedSummaryForArtisan(
        ArtisanProfile $artisan,
    ): array {
        /** @var array{averageRating: string|null, reviewCount: string} $summary */
        $summary = $this->createQueryBuilder('review')
            ->select('AVG(review.rating) AS averageRating, COUNT(review.id) AS reviewCount')
            ->andWhere('review.artisanProfile = :artisan')
            ->andWhere('review.isPublished = :published')
            ->setParameter('artisan', $artisan)
            ->setParameter('published', true)
            ->getQuery()
            ->getSingleResult();

        return [
            'averageRating' => null === $summary['averageRating']
                ? 0.0
                : (float) $summary['averageRating'],
            'reviewCount' => (int) $summary['reviewCount'],
        ];
    }
}
