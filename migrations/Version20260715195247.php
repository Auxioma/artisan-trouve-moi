<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260715195247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE app_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone_number VARCHAR(30) DEFAULT NULL, locale VARCHAR(10) NOT NULL, country_code VARCHAR(2) NOT NULL, timezone VARCHAR(100) NOT NULL, avatar_filename VARCHAR(255) DEFAULT NULL, is_verified TINYINT NOT NULL, is_phone_verified TINYINT NOT NULL, has_accepted_terms TINYINT NOT NULL, terms_accepted_at DATETIME DEFAULT NULL, terms_version VARCHAR(30) DEFAULT NULL, has_accepted_privacy_policy TINYINT NOT NULL, privacy_policy_accepted_at DATETIME DEFAULT NULL, privacy_policy_version VARCHAR(30) DEFAULT NULL, marketing_consent TINYINT NOT NULL, marketing_consent_at DATETIME DEFAULT NULL, last_login_at DATETIME DEFAULT NULL, suspended_at DATETIME DEFAULT NULL, suspension_reason LONGTEXT DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_USER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE artisan_profile (id INT AUTO_INCREMENT NOT NULL, legal_name VARCHAR(180) NOT NULL, commercial_name VARCHAR(180) DEFAULT NULL, slug VARCHAR(180) NOT NULL, siren VARCHAR(9) DEFAULT NULL, siret VARCHAR(14) DEFAULT NULL, vat_number VARCHAR(20) DEFAULT NULL, ape_code VARCHAR(10) DEFAULT NULL, legal_form VARCHAR(100) DEFAULT NULL, identity_verification_status VARCHAR(255) NOT NULL, company_verification_status VARCHAR(255) NOT NULL, rne_verification_status VARCHAR(255) NOT NULL, is_registered_in_rne TINYINT NOT NULL, rne_verified_at DATETIME DEFAULT NULL, qualification_type VARCHAR(255) DEFAULT NULL, qualification_title VARCHAR(180) DEFAULT NULL, qualification_number VARCHAR(100) DEFAULT NULL, qualification_obtained_at DATETIME DEFAULT NULL, qualification_verification_status VARCHAR(255) NOT NULL, under_qualified_person_control TINYINT NOT NULL, qualified_person_first_name VARCHAR(100) DEFAULT NULL, qualified_person_last_name VARCHAR(100) DEFAULT NULL, qualified_person_position VARCHAR(150) DEFAULT NULL, experience_years INT NOT NULL, description LONGTEXT DEFAULT NULL, professional_liability_insurance_required TINYINT NOT NULL, has_professional_liability_insurance TINYINT NOT NULL, professional_liability_insurer VARCHAR(180) DEFAULT NULL, professional_liability_policy_number VARCHAR(100) DEFAULT NULL, professional_liability_starts_at DATETIME DEFAULT NULL, professional_liability_expires_at DATETIME DEFAULT NULL, professional_liability_verification_status VARCHAR(255) NOT NULL, decennial_insurance_required TINYINT NOT NULL, has_decennial_insurance TINYINT NOT NULL, decennial_insurer VARCHAR(180) DEFAULT NULL, decennial_policy_number VARCHAR(100) DEFAULT NULL, decennial_insurance_starts_at DATETIME DEFAULT NULL, decennial_insurance_expires_at DATETIME DEFAULT NULL, decennial_geographical_coverage VARCHAR(255) DEFAULT NULL, decennial_insurance_verification_status VARCHAR(255) NOT NULL, is_published TINYINT NOT NULL, published_at DATETIME DEFAULT NULL, validated_at DATETIME DEFAULT NULL, rejection_reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_B5D83F1E989D9B62 (slug), UNIQUE INDEX UNIQ_B5D83F1E26E94372 (siret), UNIQUE INDEX UNIQ_B5D83F1EA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commercial_partner_profile (id INT AUTO_INCREMENT NOT NULL, company_name VARCHAR(180) NOT NULL, contact_job_title VARCHAR(180) DEFAULT NULL, business_email VARCHAR(180) DEFAULT NULL, business_phone VARCHAR(30) DEFAULT NULL, siren VARCHAR(9) DEFAULT NULL, siret VARCHAR(14) DEFAULT NULL, vat_number VARCHAR(20) DEFAULT NULL, country_code VARCHAR(2) NOT NULL, description LONGTEXT DEFAULT NULL, commercial_area VARCHAR(100) DEFAULT NULL, verification_status VARCHAR(255) NOT NULL, is_active TINYINT NOT NULL, contract_starts_at DATETIME DEFAULT NULL, contract_ends_at DATETIME DEFAULT NULL, contract_reference VARCHAR(100) DEFAULT NULL, commission_rate NUMERIC(5, 2) NOT NULL, validated_at DATETIME DEFAULT NULL, internal_notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_48FB43AD99739BCB (business_email), UNIQUE INDEX UNIQ_48FB43ADA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE artisan_profile ADD CONSTRAINT FK_B5D83F1EA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commercial_partner_profile ADD CONSTRAINT FK_48FB43ADA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY `FK_7CE748AA76ED395`');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id)');
        $this->addSql('DROP TABLE user');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, roles JSON NOT NULL, password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, is_verified TINYINT NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT `FK_7CE748AA76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE artisan_profile DROP FOREIGN KEY FK_B5D83F1EA76ED395');
        $this->addSql('ALTER TABLE commercial_partner_profile DROP FOREIGN KEY FK_48FB43ADA76ED395');
        $this->addSql('DROP TABLE artisan_profile');
        $this->addSql('DROP TABLE commercial_partner_profile');
        $this->addSql('DROP TABLE app_user');
    }
}
