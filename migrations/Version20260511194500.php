<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260511194500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audit and soft-delete fields to backend managed records.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD disabled_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE task ADD disabled_at DATETIME DEFAULT NULL, ADD is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE expense ADD created_at DATETIME DEFAULT NULL, ADD disabled_at DATETIME DEFAULT NULL, ADD is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE event ADD created_at DATETIME DEFAULT NULL, ADD disabled_at DATETIME DEFAULT NULL, ADD is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('UPDATE expense SET created_at = COALESCE(paid_at, due_date, NOW()) WHERE created_at IS NULL');
        $this->addSql('UPDATE event SET created_at = COALESCE(start_date, NOW()) WHERE created_at IS NULL');
        $this->addSql('ALTER TABLE expense CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE event CHANGE created_at created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP created_at, DROP disabled_at, DROP is_active');
        $this->addSql('ALTER TABLE expense DROP created_at, DROP disabled_at, DROP is_active');
        $this->addSql('ALTER TABLE task DROP disabled_at, DROP is_active');
        $this->addSql('ALTER TABLE user DROP disabled_at');
    }
}
