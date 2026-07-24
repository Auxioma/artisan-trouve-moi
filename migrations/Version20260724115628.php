<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260724115628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artisan_profile ADD professional_liability_document_name VARCHAR(255) DEFAULT NULL, ADD professional_liability_document_size INT DEFAULT NULL, ADD professional_liability_document_mime_type VARCHAR(100) DEFAULT NULL, ADD decennial_insurance_document_name VARCHAR(255) DEFAULT NULL, ADD decennial_insurance_document_size INT DEFAULT NULL, ADD decennial_insurance_document_mime_type VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artisan_profile DROP professional_liability_document_name, DROP professional_liability_document_size, DROP professional_liability_document_mime_type, DROP decennial_insurance_document_name, DROP decennial_insurance_document_size, DROP decennial_insurance_document_mime_type');
    }
}
