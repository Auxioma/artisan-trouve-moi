<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Enum\UserType;
use App\Entity\Users\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixtures extends AbstractFrenchFixture
{
    protected const ENTITY_CLASS = User::class;
    protected const RECORDS_PER_ENTITY = 24;

    /** @var array<int, array{email: string, password: string, type: UserType}> */
    private const TEST_ACCOUNTS = [
        1 => ['email' => 'claire.martin@artisan-trouve-moi.fr', 'password' => 'c', 'type' => UserType::CUSTOMER],
        2 => ['email' => 'julien.bernard@atelier-duval.fr', 'password' => 'artisan', 'type' => UserType::ARTISAN],
        3 => ['email' => 'admin@artisan-trouve-moi.fr', 'password' => 'admin', 'type' => UserType::CUSTOMER],
        4 => ['email' => 'partenaires@renovation-robert.fr', 'password' => 'commercial', 'type' => UserType::COMMERCIAL_PARTNER],
    ];

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
    }

    protected function afterPopulate(object $entity, int $index): void
    {
        \assert($entity instanceof User);
        $account = self::TEST_ACCOUNTS[$index] ?? null;
        if (null !== $account) {
            $entity->setEmail($account['email']);
            $entity->setType($account['type']);
        } else {
            $entity->setType([UserType::CUSTOMER, UserType::ARTISAN, UserType::COMMERCIAL_PARTNER][($index - 1) % 3]);
        }

        $entity->setPassword($this->passwordHasher->hashPassword($entity, $account['password'] ?? 'fixture'));
        $roles = ['ROLE_USER', $entity->getType()->securityRole()];
        if (3 === $index) {
            $roles[] = 'ROLE_ADMIN';
        }
        $entity->setRoles(array_values(array_unique($roles)));
    }
}
