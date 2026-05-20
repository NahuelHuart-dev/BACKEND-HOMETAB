<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add visual identity fields to households.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE household ADD avatar VARCHAR(255) DEFAULT NULL, ADD avatar_icon VARCHAR(60) DEFAULT NULL');
        $this->addSql("UPDATE household SET avatar_icon = 'pi-home' WHERE avatar_icon IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE household DROP avatar, DROP avatar_icon');
    }
}
