<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260729211639 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artisan_profile RENAME INDEX uniq_artisan_profile_stripe_customer_id TO UNIQ_B5D83F1E708DC647');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artisan_profile RENAME INDEX uniq_b5d83f1e708dc647 TO UNIQ_ARTISAN_PROFILE_STRIPE_CUSTOMER_ID');
    }
}
