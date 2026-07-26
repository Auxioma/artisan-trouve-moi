<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Messaging\MessageAttachment
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class MessageAttachmentFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = MessageAttachment::class;
    protected const DEPENDENCIES = [MessageFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
