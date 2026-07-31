<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260730120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalise les périodes de facturation vides des abonnements existants.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE subscription SET billing_period = CASE WHEN current_period_starts_at IS NOT NULL AND current_period_ends_at > DATE_ADD(current_period_starts_at, INTERVAL 60 DAY) THEN 'yearly' ELSE 'monthly' END WHERE billing_period IS NULL OR billing_period = ''");
        $this->addSql("ALTER TABLE subscription MODIFY billing_period VARCHAR(20) NOT NULL DEFAULT 'monthly'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE subscription MODIFY billing_period VARCHAR(20) NOT NULL DEFAULT 'monthly'");
    }
}
