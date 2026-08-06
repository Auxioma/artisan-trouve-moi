<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Catalog\Category
;
use Doctrine\Persistence\ObjectManager;

final class CategoryFixtures extends AbstractFrenchFixture
{
    protected const ENTITY_CLASS = Category::class;
    protected const DEPENDENCIES = [];

    public function load(ObjectManager $manager): void
    {
        parent::load($manager);

        /** @var Category $root */
        $root = $this->getReference($this->reference(Category::class, 1), Category::class);
        for ($index = 2; $index <= self::RECORDS_PER_ENTITY; ++$index) {
            /** @var Category $category */
            $category = $this->getReference($this->reference(Category::class, $index), Category::class);
            $category->setParent($root);
        }

        $manager->flush();
    }
}
