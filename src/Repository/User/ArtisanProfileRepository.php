<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\Users\ArtisanProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArtisanProfile>
 */
final class ArtisanProfileRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, ArtisanProfile::class);
    }

    public function save(
        ArtisanProfile $artisanProfile,
        bool $flush = false,
    ): void {
        $this->getEntityManager()->persist($artisanProfile);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(
        ArtisanProfile $artisanProfile,
        bool $flush = false,
    ): void {
        $this->getEntityManager()->remove($artisanProfile);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneBySiret(string $siret): ?ArtisanProfile
    {
        $normalizedSiret = preg_replace('/\D/', '', $siret);

        return $this->findOneBy([
            'siret' => $normalizedSiret,
        ]);
    }

    public function findOneBySlug(string $slug): ?ArtisanProfile
    {
        return $this->findOneBy([
            'slug' => mb_strtolower(trim($slug)),
        ]);
    }

    /**
     * @return list<ArtisanProfile>
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('artisan')
            ->andWhere('artisan.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('artisan.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
