<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503013000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add expense payment type and recurrence details.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expense ADD payment_type VARCHAR(20) NOT NULL, ADD recurrence_day_of_month INT DEFAULT NULL, ADD recurrence_weekday INT DEFAULT NULL, ADD recurrence_time TIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expense DROP payment_type, DROP recurrence_day_of_month, DROP recurrence_weekday, DROP recurrence_time');
    }
}
