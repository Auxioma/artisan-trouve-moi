<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260718184858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE appointment (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, title VARCHAR(180) NOT NULL, starts_at DATETIME NOT NULL, ends_at DATETIME DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, reminder_sent_at DATETIME DEFAULT NULL, cancelled_at DATETIME DEFAULT NULL, cancellation_reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, artisan_profile_id INT NOT NULL, client_id INT NOT NULL, project_id INT DEFAULT NULL, service_request_id INT DEFAULT NULL, INDEX IDX_FE38F844A02F3B25 (artisan_profile_id), INDEX IDX_FE38F84419EB6921 (client_id), INDEX IDX_FE38F844166D1F9C (project_id), INDEX IDX_FE38F844D42F8111 (service_request_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE artisan_photo (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) DEFAULT NULL, city VARCHAR(150) DEFAULT NULL, completed_at DATETIME DEFAULT NULL, position INT NOT NULL, is_cover TINYINT NOT NULL, image_name VARCHAR(255) DEFAULT NULL, image_size INT DEFAULT NULL, image_mime_type VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, artisan_profile_id INT NOT NULL, INDEX IDX_56C2F553A02F3B25 (artisan_profile_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE artisan_service (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, description LONGTEXT DEFAULT NULL, price_from NUMERIC(10, 2) DEFAULT NULL, price_unit VARCHAR(255) NOT NULL, estimated_duration_hours INT DEFAULT NULL, position INT NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, artisan_profile_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_D5120FC3A02F3B25 (artisan_profile_id), INDEX IDX_D5120FC312469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, slug VARCHAR(140) NOT NULL, icon VARCHAR(60) DEFAULT NULL, description LONGTEXT DEFAULT NULL, position INT NOT NULL, is_active TINYINT NOT NULL, meta_title VARCHAR(180) DEFAULT NULL, meta_description VARCHAR(300) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, parent_id INT DEFAULT NULL, INDEX IDX_64C19C1727ACA70 (parent_id), UNIQUE INDEX uniq_category_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE conversation (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(180) DEFAULT NULL, last_message_at DATETIME DEFAULT NULL, client_read_at DATETIME DEFAULT NULL, artisan_read_at DATETIME DEFAULT NULL, is_archived_by_client TINYINT NOT NULL, is_archived_by_artisan TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, client_id INT NOT NULL, artisan_profile_id INT NOT NULL, service_request_id INT DEFAULT NULL, INDEX IDX_8A8E26E919EB6921 (client_id), INDEX IDX_8A8E26E9A02F3B25 (artisan_profile_id), INDEX IDX_8A8E26E9D42F8111 (service_request_id), UNIQUE INDEX uniq_conversation_participants_request (client_id, artisan_profile_id, service_request_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE favorite (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, artisan_profile_id INT NOT NULL, INDEX IDX_68C58ED9A76ED395 (user_id), INDEX IDX_68C58ED9A02F3B25 (artisan_profile_id), UNIQUE INDEX uniq_favorite_user_artisan (user_id, artisan_profile_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE intervention_city (id INT AUTO_INCREMENT NOT NULL, city_name VARCHAR(150) NOT NULL, postal_code VARCHAR(20) NOT NULL, position INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, zone_id INT NOT NULL, INDEX IDX_22E41AF69F2C3FAB (zone_id), UNIQUE INDEX uniq_zone_city (zone_id, postal_code, city_name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE intervention_zone (id INT AUTO_INCREMENT NOT NULL, base_city VARCHAR(150) NOT NULL, base_postal_code VARCHAR(20) NOT NULL, latitude NUMERIC(10, 7) DEFAULT NULL, longitude NUMERIC(10, 7) DEFAULT NULL, radius_km INT NOT NULL, extra_travel_fee_ht NUMERIC(10, 2) DEFAULT NULL, travel_note VARCHAR(255) DEFAULT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, artisan_profile_id INT NOT NULL, UNIQUE INDEX uniq_zone_artisan (artisan_profile_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE invoice (id INT AUTO_INCREMENT NOT NULL, reference VARCHAR(30) NOT NULL, status VARCHAR(255) NOT NULL, amount_ht NUMERIC(10, 2) NOT NULL, amount_vat NUMERIC(10, 2) NOT NULL, amount_ttc NUMERIC(10, 2) NOT NULL, period_starts_at DATETIME DEFAULT NULL, period_ends_at DATETIME DEFAULT NULL, billing_name VARCHAR(180) DEFAULT NULL, billing_address VARCHAR(255) DEFAULT NULL, issued_at DATETIME DEFAULT NULL, due_at DATETIME DEFAULT NULL, paid_at DATETIME DEFAULT NULL, provider_invoice_id VARCHAR(100) DEFAULT NULL, pdf_filename VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, subscription_id INT NOT NULL, INDEX IDX_906517449A1887DC (subscription_id), UNIQUE INDEX uniq_invoice_reference (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, is_system TINYINT NOT NULL, read_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, conversation_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_B6BD307F9AC0396 (conversation_id), INDEX IDX_B6BD307FF675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE message_attachment (id INT AUTO_INCREMENT NOT NULL, original_name VARCHAR(255) DEFAULT NULL, document_name VARCHAR(255) DEFAULT NULL, document_size INT DEFAULT NULL, document_mime_type VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, message_id INT NOT NULL, INDEX IDX_B68FF524537A1329 (message_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, title VARCHAR(180) NOT NULL, content LONGTEXT DEFAULT NULL, link_url VARCHAR(255) DEFAULT NULL, read_at DATETIME DEFAULT NULL, email_sent_at DATETIME DEFAULT NULL, push_sent_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE payment_method (id INT AUTO_INCREMENT NOT NULL, brand VARCHAR(30) DEFAULT NULL, last4 VARCHAR(4) NOT NULL, expires_month SMALLINT NOT NULL, expires_year SMALLINT NOT NULL, holder_name VARCHAR(120) DEFAULT NULL, is_default TINYINT NOT NULL, provider_payment_method_id VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_7B61A1F6A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, reference VARCHAR(30) NOT NULL, title VARCHAR(180) NOT NULL, status VARCHAR(255) NOT NULL, progress_percent INT NOT NULL, amount_ttc NUMERIC(10, 2) DEFAULT NULL, address_line1 VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, city VARCHAR(150) DEFAULT NULL, starts_at DATETIME DEFAULT NULL, ends_at DATETIME DEFAULT NULL, actual_started_at DATETIME DEFAULT NULL, completed_at DATETIME DEFAULT NULL, cancelled_at DATETIME DEFAULT NULL, cancellation_reason LONGTEXT DEFAULT NULL, internal_notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, quote_id INT NOT NULL, client_id INT NOT NULL, artisan_profile_id INT NOT NULL, INDEX IDX_2FB3D0EE19EB6921 (client_id), INDEX IDX_2FB3D0EEA02F3B25 (artisan_profile_id), UNIQUE INDEX uniq_project_reference (reference), UNIQUE INDEX uniq_project_quote (quote_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE project_step (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, label VARCHAR(180) NOT NULL, description LONGTEXT DEFAULT NULL, planned_at DATETIME DEFAULT NULL, done_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, project_id INT NOT NULL, INDEX IDX_7A283624166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quote (id INT AUTO_INCREMENT NOT NULL, reference VARCHAR(30) NOT NULL, status VARCHAR(255) NOT NULL, message LONGTEXT DEFAULT NULL, vat_rate NUMERIC(5, 2) NOT NULL, total_ht NUMERIC(10, 2) NOT NULL, total_vat NUMERIC(10, 2) NOT NULL, total_ttc NUMERIC(10, 2) NOT NULL, discount_ht NUMERIC(10, 2) DEFAULT NULL, deposit_percent NUMERIC(5, 2) DEFAULT NULL, work_duration_days INT NOT NULL, can_start_at DATETIME DEFAULT NULL, warranty_months INT NOT NULL, valid_until DATETIME DEFAULT NULL, sent_at DATETIME DEFAULT NULL, viewed_by_client_at DATETIME DEFAULT NULL, reminded_at DATETIME DEFAULT NULL, accepted_at DATETIME DEFAULT NULL, signed_at DATETIME DEFAULT NULL, signature_ip VARCHAR(45) DEFAULT NULL, refused_at DATETIME DEFAULT NULL, refusal_reason LONGTEXT DEFAULT NULL, pdf_filename VARCHAR(255) DEFAULT NULL, terms_and_conditions LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, service_request_id INT NOT NULL, artisan_profile_id INT NOT NULL, INDEX IDX_6B71CBF4D42F8111 (service_request_id), INDEX IDX_6B71CBF4A02F3B25 (artisan_profile_id), UNIQUE INDEX uniq_quote_reference (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quote_line (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, label VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, quantity NUMERIC(10, 2) NOT NULL, unit VARCHAR(20) NOT NULL, unit_price_ht NUMERIC(10, 2) NOT NULL, vat_rate NUMERIC(5, 2) DEFAULT NULL, total_ht NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, quote_id INT NOT NULL, INDEX IDX_43F3EB7CDB805178 (quote_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE request_photo (id INT AUTO_INCREMENT NOT NULL, caption VARCHAR(255) DEFAULT NULL, position INT NOT NULL, image_name VARCHAR(255) DEFAULT NULL, image_size INT DEFAULT NULL, image_mime_type VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, request_id INT NOT NULL, INDEX IDX_49A0383D427EB8A5 (request_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, rating SMALLINT NOT NULL, quality_rating SMALLINT DEFAULT NULL, punctuality_rating SMALLINT DEFAULT NULL, cleanliness_rating SMALLINT DEFAULT NULL, would_recommend TINYINT DEFAULT NULL, comment LONGTEXT NOT NULL, response LONGTEXT DEFAULT NULL, responded_at DATETIME DEFAULT NULL, is_published TINYINT NOT NULL, published_at DATETIME DEFAULT NULL, moderated_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, author_id INT NOT NULL, artisan_profile_id INT NOT NULL, project_id INT DEFAULT NULL, INDEX IDX_794381C6F675F31B (author_id), INDEX IDX_794381C6A02F3B25 (artisan_profile_id), UNIQUE INDEX uniq_review_project (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE service_request (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, description LONGTEXT NOT NULL, status VARCHAR(255) NOT NULL, is_urgent TINYINT NOT NULL, budget_min NUMERIC(10, 2) DEFAULT NULL, budget_max NUMERIC(10, 2) DEFAULT NULL, desired_start_at DATETIME DEFAULT NULL, property_type VARCHAR(30) DEFAULT NULL, surface_m2 NUMERIC(8, 2) DEFAULT NULL, access_details LONGTEXT DEFAULT NULL, availability_note VARCHAR(255) DEFAULT NULL, address_line1 VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(20) NOT NULL, city VARCHAR(150) NOT NULL, district VARCHAR(150) DEFAULT NULL, latitude NUMERIC(10, 7) DEFAULT NULL, longitude NUMERIC(10, 7) DEFAULT NULL, published_at DATETIME DEFAULT NULL, expires_at DATETIME DEFAULT NULL, max_quotes INT NOT NULL, quotes_count INT NOT NULL, views_count INT NOT NULL, cancelled_at DATETIME DEFAULT NULL, cancellation_reason LONGTEXT DEFAULT NULL, moderated_at DATETIME DEFAULT NULL, source VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, client_id INT NOT NULL, category_id INT NOT NULL, awarded_quote_id INT DEFAULT NULL, INDEX IDX_F413DD0319EB6921 (client_id), INDEX IDX_F413DD0312469DE2 (category_id), UNIQUE INDEX UNIQ_F413DD03EC27ECD4 (awarded_quote_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(255) NOT NULL, starts_at DATETIME NOT NULL, trial_ends_at DATETIME DEFAULT NULL, current_period_starts_at DATETIME DEFAULT NULL, current_period_ends_at DATETIME DEFAULT NULL, quotes_used_in_period INT NOT NULL, cancel_at_period_end TINYINT NOT NULL, cancelled_at DATETIME DEFAULT NULL, ended_at DATETIME DEFAULT NULL, provider_subscription_id VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, artisan_profile_id INT NOT NULL, plan_id INT NOT NULL, payment_method_id INT DEFAULT NULL, INDEX IDX_A3C664D3A02F3B25 (artisan_profile_id), INDEX IDX_A3C664D3E899029B (plan_id), INDEX IDX_A3C664D35AA1164F (payment_method_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE subscription_plan (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, name VARCHAR(80) NOT NULL, monthly_price_ht NUMERIC(10, 2) NOT NULL, yearly_price_ht NUMERIC(10, 2) DEFAULT NULL, vat_rate NUMERIC(5, 2) NOT NULL, trial_days INT NOT NULL, max_quotes_per_month INT DEFAULT NULL, max_categories INT DEFAULT NULL, max_photos INT DEFAULT NULL, has_urgent_access TINYINT NOT NULL, has_priority_ranking TINYINT NOT NULL, features JSON NOT NULL, provider_price_id VARCHAR(100) DEFAULT NULL, position INT NOT NULL, is_popular TINYINT NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_plan_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE verification_document (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, submitted_at DATETIME DEFAULT NULL, reviewed_at DATETIME DEFAULT NULL, expires_at DATETIME DEFAULT NULL, rejection_reason LONGTEXT DEFAULT NULL, document_name VARCHAR(255) DEFAULT NULL, document_size INT DEFAULT NULL, document_mime_type VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, artisan_profile_id INT NOT NULL, reviewed_by_id INT DEFAULT NULL, INDEX IDX_29A60264A02F3B25 (artisan_profile_id), INDEX IDX_29A60264FC6B21F1 (reviewed_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844A02F3B25 FOREIGN KEY (artisan_profile_id) REFERENCES artisan_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F84419EB6921 FOREIGN KEY (client_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844D42F8111 FOREIGN KEY (service_request_id) REFERENCES service_request (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE artisan_photo ADD CONSTRAINT FK_56C2F553A02F3B25 FOREIGN KEY (artisan_profile_id) REFERENCES artisan_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE artisan_service ADD CONSTRAINT FK_D5120FC3A02F3B25 FOREIGN KEY (artisan_profile_id) REFERENCES artisan_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE artisan_service ADD CONSTRAINT FK_D5120FC312469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E919EB6921 FOREIGN KEY (client_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9A02F3B25 FOREIGN KEY (artisan_profile_id) REFERENCES artisan_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9D42F8111 FOREIGN KEY (service_request_id) REFERENCES service_request (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED9A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED9A02F3B25 FOREIGN KEY (artisan_profile_id) REFERENCES artisan_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intervention_city ADD CONSTRAINT FK_22E41AF69F2C3FAB FOREIGN KEY (zone_id) REFERENCES intervention_zone (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intervention_zone ADD CONSTRAINT FK_AF54D8C5A02F3B25 FOREIGN KEY (artisan_profile_id) REFERENCES artisan_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517449A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F9AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF675F31B FOREIGN KEY (author_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_attachment ADD CONSTRAINT FK_B68FF524537A1329 FOREIGN KEY (message_id) REFERENCES message (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payment_method ADD CONSTRAINT FK_7B61A1F6A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEDB805178 FOREIGN KEY (quote_id) REFERENCES quote (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE19EB6921 FOREIGN KEY (client_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEA02F3B25 FOREIGN KEY (artisan_profile_id) REFERENCES artisan_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_step ADD CONSTRAINT FK_7A283624166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF4D42F8111 FOREIGN KEY (service_request_id) REFERENCES service_request (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF4A02F3B25 FOREIGN KEY (artisan_profile_id) REFERENCES artisan_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE quote_line ADD CONSTRAINT FK_43F3EB7CDB805178 FOREIGN KEY (quote_id) REFERENCES quote (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE request_photo ADD CONSTRAINT FK_49A0383D427EB8A5 FOREIGN KEY (request_id) REFERENCES service_request (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6F675F31B FOREIGN KEY (author_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6A02F3B25 FOREIGN KEY (artisan_profile_id) REFERENCES artisan_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE service_request ADD CONSTRAINT FK_F413DD0319EB6921 FOREIGN KEY (client_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE service_request ADD CONSTRAINT FK_F413DD0312469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE service_request ADD CONSTRAINT FK_F413DD03EC27ECD4 FOREIGN KEY (awarded_quote_id) REFERENCES quote (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A02F3B25 FOREIGN KEY (artisan_profile_id) REFERENCES artisan_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3E899029B FOREIGN KEY (plan_id) REFERENCES subscription_plan (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D35AA1164F FOREIGN KEY (payment_method_id) REFERENCES payment_method (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE verification_document ADD CONSTRAINT FK_29A60264A02F3B25 FOREIGN KEY (artisan_profile_id) REFERENCES artisan_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE verification_document ADD CONSTRAINT FK_29A60264FC6B21F1 FOREIGN KEY (reviewed_by_id) REFERENCES app_user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844A02F3B25');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F84419EB6921');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844166D1F9C');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844D42F8111');
        $this->addSql('ALTER TABLE artisan_photo DROP FOREIGN KEY FK_56C2F553A02F3B25');
        $this->addSql('ALTER TABLE artisan_service DROP FOREIGN KEY FK_D5120FC3A02F3B25');
        $this->addSql('ALTER TABLE artisan_service DROP FOREIGN KEY FK_D5120FC312469DE2');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1727ACA70');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E919EB6921');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9A02F3B25');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9D42F8111');
        $this->addSql('ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED9A76ED395');
        $this->addSql('ALTER TABLE favorite DROP FOREIGN KEY FK_68C58ED9A02F3B25');
        $this->addSql('ALTER TABLE intervention_city DROP FOREIGN KEY FK_22E41AF69F2C3FAB');
        $this->addSql('ALTER TABLE intervention_zone DROP FOREIGN KEY FK_AF54D8C5A02F3B25');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_906517449A1887DC');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F9AC0396');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF675F31B');
        $this->addSql('ALTER TABLE message_attachment DROP FOREIGN KEY FK_B68FF524537A1329');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE payment_method DROP FOREIGN KEY FK_7B61A1F6A76ED395');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEDB805178');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE19EB6921');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEA02F3B25');
        $this->addSql('ALTER TABLE project_step DROP FOREIGN KEY FK_7A283624166D1F9C');
        $this->addSql('ALTER TABLE quote DROP FOREIGN KEY FK_6B71CBF4D42F8111');
        $this->addSql('ALTER TABLE quote DROP FOREIGN KEY FK_6B71CBF4A02F3B25');
        $this->addSql('ALTER TABLE quote_line DROP FOREIGN KEY FK_43F3EB7CDB805178');
        $this->addSql('ALTER TABLE request_photo DROP FOREIGN KEY FK_49A0383D427EB8A5');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6F675F31B');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6A02F3B25');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6166D1F9C');
        $this->addSql('ALTER TABLE service_request DROP FOREIGN KEY FK_F413DD0319EB6921');
        $this->addSql('ALTER TABLE service_request DROP FOREIGN KEY FK_F413DD0312469DE2');
        $this->addSql('ALTER TABLE service_request DROP FOREIGN KEY FK_F413DD03EC27ECD4');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3A02F3B25');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3E899029B');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D35AA1164F');
        $this->addSql('ALTER TABLE verification_document DROP FOREIGN KEY FK_29A60264A02F3B25');
        $this->addSql('ALTER TABLE verification_document DROP FOREIGN KEY FK_29A60264FC6B21F1');
        $this->addSql('DROP TABLE appointment');
        $this->addSql('DROP TABLE artisan_photo');
        $this->addSql('DROP TABLE artisan_service');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE conversation');
        $this->addSql('DROP TABLE favorite');
        $this->addSql('DROP TABLE intervention_city');
        $this->addSql('DROP TABLE intervention_zone');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE message_attachment');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE payment_method');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE project_step');
        $this->addSql('DROP TABLE quote');
        $this->addSql('DROP TABLE quote_line');
        $this->addSql('DROP TABLE request_photo');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE service_request');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('DROP TABLE subscription_plan');
        $this->addSql('DROP TABLE verification_document');
    }
}
