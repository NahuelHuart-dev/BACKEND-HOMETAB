<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503014500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add PrimeIcon avatar selection to users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD avatar_icon VARCHAR(60) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP avatar_icon');
    }
}
