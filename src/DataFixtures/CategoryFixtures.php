<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Catalog\Category
;

final class CategoryFixtures extends AbstractFrenchFixture
{
    protected const ENTITY_CLASS = Category::class;
    protected const DEPENDENCIES = [];
}
