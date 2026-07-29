<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260729181000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Déplace l’identifiant client Stripe de l’utilisateur vers le profil artisan.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE artisan_profile ADD stripe_customer_id VARCHAR(100) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ARTISAN_PROFILE_STRIPE_CUSTOMER_ID ON artisan_profile (stripe_customer_id)');
        $this->addSql('UPDATE artisan_profile INNER JOIN app_user ON artisan_profile.user_id = app_user.id SET artisan_profile.stripe_customer_id = app_user.stripe_customer_id WHERE app_user.stripe_customer_id IS NOT NULL');
        $this->addSql('DROP INDEX UNIQ_APP_USER_STRIPE_CUSTOMER_ID ON app_user');
        $this->addSql('ALTER TABLE app_user DROP stripe_customer_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD stripe_customer_id VARCHAR(100) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_APP_USER_STRIPE_CUSTOMER_ID ON app_user (stripe_customer_id)');
        $this->addSql('UPDATE app_user INNER JOIN artisan_profile ON artisan_profile.user_id = app_user.id SET app_user.stripe_customer_id = artisan_profile.stripe_customer_id WHERE artisan_profile.stripe_customer_id IS NOT NULL');
        $this->addSql('DROP INDEX UNIQ_ARTISAN_PROFILE_STRIPE_CUSTOMER_ID ON artisan_profile');
        $this->addSql('ALTER TABLE artisan_profile DROP stripe_customer_id');
    }
}
