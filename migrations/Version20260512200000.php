<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260512200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audit logs for administrative chat access.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE chat_access_log (id INT AUTO_INCREMENT NOT NULL, admin_id INT NOT NULL, household_id INT NOT NULL, reason LONGTEXT NOT NULL, accessed_at DATETIME NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, INDEX IDX_288E70B6642B8210 (admin_id), INDEX IDX_288E70B6E79FF843 (household_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE chat_access_log ADD CONSTRAINT FK_288E70B6642B8210 FOREIGN KEY (admin_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chat_access_log ADD CONSTRAINT FK_288E70B6E79FF843 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chat_access_log DROP FOREIGN KEY FK_288E70B6642B8210');
        $this->addSql('ALTER TABLE chat_access_log DROP FOREIGN KEY FK_288E70B6E79FF843');
        $this->addSql('DROP TABLE chat_access_log');
    }
}
