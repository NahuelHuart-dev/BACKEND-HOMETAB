<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260512193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add household chat messages.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE household_message (id INT AUTO_INCREMENT NOT NULL, household_id INT NOT NULL, sender_id INT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, INDEX IDX_DB49DA51E79FF843 (household_id), INDEX IDX_DB49DA51F624B39D (sender_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE household_message ADD CONSTRAINT FK_DB49DA51E79FF843 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE household_message ADD CONSTRAINT FK_DB49DA51F624B39D FOREIGN KEY (sender_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE household_message DROP FOREIGN KEY FK_DB49DA51E79FF843');
        $this->addSql('ALTER TABLE household_message DROP FOREIGN KEY FK_DB49DA51F624B39D');
        $this->addSql('DROP TABLE household_message');
    }
}
