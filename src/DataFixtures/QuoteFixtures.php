<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Quotes\Quote
;
use App\Entity\Requests\ServiceRequest;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class QuoteFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = Quote::class;
    protected const DEPENDENCIES = [ServiceRequestFixtures::class, ArtisanProfileFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }

    public function load(ObjectManager $manager): void
    {
        parent::load($manager);

        for ($index = 1; $index <= self::RECORDS_PER_ENTITY; ++$index) {
            /** @var ServiceRequest $request */
            $request = $this->getReference($this->reference(ServiceRequest::class, $index), ServiceRequest::class);
            /** @var Quote $quote */
            $quote = $this->getReference($this->reference(Quote::class, $index), Quote::class);
            $request->setAwardedQuote($quote);
        }

        $manager->flush();
    }
}
