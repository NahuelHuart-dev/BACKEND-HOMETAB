<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds multimedia playlists and chat image attachments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE multimedia_playlist (id INT AUTO_INCREMENT NOT NULL, household_id INT NOT NULL, created_by_id INT NOT NULL, name VARCHAR(120) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_73430E2A3C89F72A (household_id), INDEX IDX_73430E2AB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE multimedia_video (id INT AUTO_INCREMENT NOT NULL, playlist_id INT NOT NULL, added_by_id INT NOT NULL, youtube_id VARCHAR(32) NOT NULL, title VARCHAR(255) NOT NULL, thumbnail_url VARCHAR(255) DEFAULT NULL, channel_title VARCHAR(160) DEFAULT NULL, position INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_E372CF496BBD148 (playlist_id), INDEX IDX_E372CF49ADDEE54E (added_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE multimedia_playlist ADD CONSTRAINT FK_73430E2A3C89F72A FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE multimedia_playlist ADD CONSTRAINT FK_73430E2AB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE multimedia_video ADD CONSTRAINT FK_E372CF496BBD148 FOREIGN KEY (playlist_id) REFERENCES multimedia_playlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE multimedia_video ADD CONSTRAINT FK_E372CF49ADDEE54E FOREIGN KEY (added_by_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE household_message ADD image_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE multimedia_video DROP FOREIGN KEY FK_E372CF496BBD148');
        $this->addSql('ALTER TABLE multimedia_video DROP FOREIGN KEY FK_E372CF49ADDEE54E');
        $this->addSql('ALTER TABLE multimedia_playlist DROP FOREIGN KEY FK_73430E2A3C89F72A');
        $this->addSql('ALTER TABLE multimedia_playlist DROP FOREIGN KEY FK_73430E2AB03A8386');
        $this->addSql('DROP TABLE multimedia_video');
        $this->addSql('DROP TABLE multimedia_playlist');
        $this->addSql('ALTER TABLE household_message DROP image_path');
    }
}
