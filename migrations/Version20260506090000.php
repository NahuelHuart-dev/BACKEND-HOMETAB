<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional email two-factor authentication.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD two_factor_enabled TINYINT(1) DEFAULT 0 NOT NULL, ADD two_factor_enabled_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE TABLE two_factor_code (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, challenge_id VARCHAR(64) NOT NULL, code_hash VARCHAR(64) NOT NULL, purpose VARCHAR(20) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, failed_attempts INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_2FA_CHALLENGE (challenge_id), INDEX IDX_2FA_EXPIRES (expires_at), INDEX IDX_AF8B59DDA76ED395 (user_id), UNIQUE INDEX UNIQ_AF8B59DD98A21AC6 (challenge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE two_factor_code ADD CONSTRAINT FK_AF8B59DDA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE two_factor_code DROP FOREIGN KEY FK_AF8B59DDA76ED395');
        $this->addSql('DROP TABLE two_factor_code');
        $this->addSql('ALTER TABLE user DROP two_factor_enabled, DROP two_factor_enabled_at');
    }
}
