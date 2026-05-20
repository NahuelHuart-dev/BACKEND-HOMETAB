<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add persistent in-app notifications.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, household_id INT NOT NULL, notification_key VARCHAR(180) NOT NULL, type VARCHAR(30) NOT NULL, priority VARCHAR(20) NOT NULL, title VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, target_at DATETIME DEFAULT NULL, route VARCHAR(255) NOT NULL, target_type VARCHAR(40) DEFAULT NULL, target_id INT DEFAULT NULL, is_read TINYINT(1) NOT NULL, read_at DATETIME DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_notification_key (notification_key), INDEX IDX_BF5476CAA76ED395 (user_id), INDEX IDX_BF5476CAE79FF843 (household_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA44E9C3D5 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA44E9C3D5');
        $this->addSql('DROP TABLE notification');
    }
}
