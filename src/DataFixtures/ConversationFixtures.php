<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Messaging\Conversation
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class ConversationFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = Conversation::class;
    protected const DEPENDENCIES = [UserFixtures::class, ArtisanProfileFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
