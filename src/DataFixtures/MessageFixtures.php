<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Messaging\Message
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class MessageFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = Message::class;
    protected const DEPENDENCIES = [ConversationFixtures::class, UserFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
