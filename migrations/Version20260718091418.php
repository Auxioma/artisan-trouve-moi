<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260718091418 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_profile (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(30) NOT NULL, label VARCHAR(100) DEFAULT NULL, address_line1 VARCHAR(255) NOT NULL, address_line2 VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(20) NOT NULL, city VARCHAR(150) NOT NULL, district VARCHAR(150) DEFAULT NULL, region VARCHAR(150) DEFAULT NULL, department VARCHAR(150) DEFAULT NULL, country_code VARCHAR(2) NOT NULL, formatted_address VARCHAR(255) DEFAULT NULL, provider_place_id VARCHAR(255) DEFAULT NULL, provider_name VARCHAR(30) DEFAULT NULL, latitude NUMERIC(10, 7) DEFAULT NULL, longitude NUMERIC(10, 7) DEFAULT NULL, is_default TINYINT NOT NULL, is_billing_address TINYINT NOT NULL, is_public TINYINT NOT NULL, is_geocoded TINYINT NOT NULL, geocoded_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_D95AB405A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_profile ADD CONSTRAINT FK_D95AB405A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_profile DROP FOREIGN KEY FK_D95AB405A76ED395');
        $this->addSql('DROP TABLE user_profile');
    }
}
