<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds per-user household ordering and password reset tokens';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE home_members ADD sort_order INT DEFAULT 0 NOT NULL');
        $this->addSql('UPDATE home_members SET sort_order = id WHERE sort_order = 0');
        $this->addSql('CREATE TABLE password_reset_token (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token_hash VARCHAR(128) NOT NULL, expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', INDEX IDX_5F37A13CA76ED395 (user_id), INDEX idx_password_reset_token_hash (token_hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE password_reset_token ADD CONSTRAINT FK_5F37A13CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE password_reset_token DROP FOREIGN KEY FK_5F37A13CA76ED395');
        $this->addSql('DROP TABLE password_reset_token');
        $this->addSql('ALTER TABLE home_members DROP sort_order');
    }
}
