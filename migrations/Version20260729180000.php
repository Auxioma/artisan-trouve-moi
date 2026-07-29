<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260729180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute l’identifiant client Stripe à l’utilisateur.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD stripe_customer_id VARCHAR(100) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_APP_USER_STRIPE_CUSTOMER_ID ON app_user (stripe_customer_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_APP_USER_STRIPE_CUSTOMER_ID ON app_user');
        $this->addSql('ALTER TABLE app_user DROP stripe_customer_id');
    }
}
