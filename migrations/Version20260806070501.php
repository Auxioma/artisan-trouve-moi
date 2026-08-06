<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260806070501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Associe les demandes de service à l’artisan destinataire.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service_request ADD artisan_profile_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_request ADD CONSTRAINT FK_F413DD03A02F3B25 FOREIGN KEY (artisan_profile_id) REFERENCES artisan_profile (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_F413DD03A02F3B25 ON service_request (artisan_profile_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service_request DROP FOREIGN KEY FK_F413DD03A02F3B25');
        $this->addSql('DROP INDEX IDX_F413DD03A02F3B25 ON service_request');
        $this->addSql('ALTER TABLE service_request DROP artisan_profile_id');
    }
}
