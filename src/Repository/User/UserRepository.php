<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\Enum\UserStatus;
use App\Entity\Enum\UserType;
use App\Entity\Users\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
final class UserRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, User::class);
    }

    public function save(User $user, bool $flush = false): void
    {
        $this->getEntityManager()->persist($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $user, bool $flush = false): void
    {
        $this->getEntityManager()->remove($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function upgradePassword(
        User $user,
        string $newHashedPassword,
    ): void {
        $user->setPassword($newHashedPassword);

        $this->save($user, true);
    }

    public function changePassword(
        User $user,
        string $plainPassword,
        UserPasswordHasherInterface $passwordHasher,
    ): void {
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $plainPassword
        );

        $user->setPassword($hashedPassword);

        $this->save($user, true);
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('user')
            ->andWhere('LOWER(user.email) = :email')
            ->setParameter('email', mb_strtolower(trim($email)))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<User>
     */
    public function findActiveByType(UserType $type): array
    {
        return $this->createQueryBuilder('user')
            ->andWhere('user.type = :type')
            ->andWhere('user.status = :status')
            ->andWhere('user.deletedAt IS NULL')
            ->setParameter('type', $type)
            ->setParameter('status', UserStatus::ACTIVE)
            ->orderBy('user.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
