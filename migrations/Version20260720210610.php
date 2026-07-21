<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720210610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE artisan_notification_preferences (id INT AUTO_INCREMENT NOT NULL, new_requests_enabled TINYINT DEFAULT 1 NOT NULL, urgent_requests_sms_enabled TINYINT DEFAULT 1 NOT NULL, client_messages_enabled TINYINT DEFAULT 1 NOT NULL, new_reviews_enabled TINYINT DEFAULT 1 NOT NULL, quote_reminders_enabled TINYINT DEFAULT 1 NOT NULL, weekly_summary_enabled TINYINT DEFAULT 1 NOT NULL, tips_and_news_enabled TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, artisan_profile_id INT NOT NULL, UNIQUE INDEX UNIQ_C4AB317FA02F3B25 (artisan_profile_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE artisan_notification_preferences ADD CONSTRAINT FK_C4AB317FA02F3B25 FOREIGN KEY (artisan_profile_id) REFERENCES artisan_profile (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artisan_notification_preferences DROP FOREIGN KEY FK_C4AB317FA02F3B25');
        $this->addSql('DROP TABLE artisan_notification_preferences');
    }
}
