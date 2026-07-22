<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260722090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow OpenStreetMap identifiers larger than a 32-bit integer.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE artisan_profile MODIFY osm_id BIGINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE artisan_profile MODIFY osm_id INT DEFAULT NULL');
    }
}
