<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260719092259 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_preferences (id INT AUTO_INCREMENT NOT NULL, new_quotes_enabled TINYINT NOT NULL, artisan_messages_enabled TINYINT NOT NULL, appointment_reminders_enabled TINYINT NOT NULL, review_invitations_enabled TINYINT NOT NULL, profile_visible_to_artisans TINYINT NOT NULL, phone_shared_after_acceptance TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_402A6F60A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user_preferences ADD CONSTRAINT FK_402A6F60A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_preferences DROP FOREIGN KEY FK_402A6F60A76ED395');
        $this->addSql('DROP TABLE user_preferences');
    }
}
