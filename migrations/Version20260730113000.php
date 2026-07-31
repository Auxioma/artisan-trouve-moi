<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260730113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la période de facturation explicite aux abonnements artisan.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE subscription ADD billing_period VARCHAR(20) NOT NULL DEFAULT 'monthly' AFTER status");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subscription DROP billing_period');
    }
}
