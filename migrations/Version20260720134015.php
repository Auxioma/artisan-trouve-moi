<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720134015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artisan_profile ADD house_number VARCHAR(20) DEFAULT NULL, ADD road VARCHAR(255) DEFAULT NULL, ADD address_complement VARCHAR(255) DEFAULT NULL, ADD neighbourhood VARCHAR(180) DEFAULT NULL, ADD suburb VARCHAR(180) DEFAULT NULL, ADD city_district VARCHAR(180) DEFAULT NULL, ADD hamlet VARCHAR(180) DEFAULT NULL, ADD village VARCHAR(180) DEFAULT NULL, ADD town VARCHAR(180) DEFAULT NULL, ADD city VARCHAR(180) DEFAULT NULL, ADD municipality VARCHAR(180) DEFAULT NULL, ADD county VARCHAR(180) DEFAULT NULL, ADD state_district VARCHAR(180) DEFAULT NULL, ADD state VARCHAR(180) DEFAULT NULL, ADD region VARCHAR(180) DEFAULT NULL, ADD postal_code VARCHAR(20) DEFAULT NULL, ADD country VARCHAR(180) DEFAULT NULL, ADD country_code VARCHAR(2) DEFAULT NULL, ADD osm_display_name VARCHAR(500) DEFAULT NULL, ADD latitude NUMERIC(10, 7) DEFAULT NULL, ADD longitude NUMERIC(10, 7) DEFAULT NULL, ADD osm_id INT DEFAULT NULL, ADD osm_type VARCHAR(20) DEFAULT NULL, ADD osm_category VARCHAR(100) DEFAULT NULL, ADD osm_place_type VARCHAR(100) DEFAULT NULL, ADD nominatim_place_id INT DEFAULT NULL, ADD travel_radius_km INT DEFAULT NULL, ADD works_at_customer_address TINYINT NOT NULL, ADD receives_customers TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artisan_profile DROP house_number, DROP road, DROP address_complement, DROP neighbourhood, DROP suburb, DROP city_district, DROP hamlet, DROP village, DROP town, DROP city, DROP municipality, DROP county, DROP state_district, DROP state, DROP region, DROP postal_code, DROP country, DROP country_code, DROP osm_display_name, DROP latitude, DROP longitude, DROP osm_id, DROP osm_type, DROP osm_category, DROP osm_place_type, DROP nominatim_place_id, DROP travel_radius_km, DROP works_at_customer_address, DROP receives_customers');
    }
}
