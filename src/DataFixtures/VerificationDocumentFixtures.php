<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Verification\VerificationDocument
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class VerificationDocumentFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = VerificationDocument::class;
    protected const DEPENDENCIES = [ArtisanProfileFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
