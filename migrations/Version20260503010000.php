<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clean HomeTab schema with household memberships and shared expense shares.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE household (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, invite_code VARCHAR(10) DEFAULT NULL, UNIQUE INDEX UNIQ_54C32FC06F21F112 (invite_code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone_number VARCHAR(20) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, bio LONGTEXT DEFAULT NULL, joined_at DATETIME NOT NULL, is_active TINYINT NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE home_members (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, household_id INT NOT NULL, role VARCHAR(20) NOT NULL, joined_at DATETIME NOT NULL, INDEX IDX_F08019A7A76ED395 (user_id), INDEX IDX_F08019A7E79FF843 (household_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, household_id INT NOT NULL, assigned_to_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, due_date DATETIME DEFAULT NULL, priority VARCHAR(20) DEFAULT NULL, category VARCHAR(100) DEFAULT NULL, completed TINYINT NOT NULL, created_at DATETIME NOT NULL, periodicity VARCHAR(50) DEFAULT NULL, INDEX IDX_527EDB25E79FF843 (household_id), INDEX IDX_527EDB25F4BD7827 (assigned_to_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, household_id INT NOT NULL, created_by_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, is_all_day TINYINT NOT NULL, color VARCHAR(7) DEFAULT NULL, INDEX IDX_3BAE0AA7E79FF843 (household_id), INDEX IDX_3BAE0AA7B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event_user (event_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_92589AE271F7E88B (event_id), INDEX IDX_92589AE2A76ED395 (user_id), PRIMARY KEY(event_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE expense (id INT AUTO_INCREMENT NOT NULL, paid_by_id INT NOT NULL, household_id INT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, amount NUMERIC(10, 2) NOT NULL, category VARCHAR(100) DEFAULT NULL, paid_at DATETIME DEFAULT NULL, due_date DATETIME DEFAULT NULL, periodicity VARCHAR(50) DEFAULT NULL, is_paid TINYINT NOT NULL, notes LONGTEXT DEFAULT NULL, INDEX IDX_2D3A8DA67F9BC654 (paid_by_id), INDEX IDX_2D3A8DA6E79FF843 (household_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE expense_share (id INT AUTO_INCREMENT NOT NULL, expense_id INT NOT NULL, user_id INT NOT NULL, amount_owed NUMERIC(10, 2) NOT NULL, is_paid TINYINT NOT NULL, paid_at DATETIME DEFAULT NULL, INDEX IDX_4C0E3A60F395DB7B (expense_id), INDEX IDX_4C0E3A60A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE home_members ADD CONSTRAINT FK_F08019A7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE home_members ADD CONSTRAINT FK_F08019A7E79FF843 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25E79FF843 FOREIGN KEY (household_id) REFERENCES household (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7E79FF843 FOREIGN KEY (household_id) REFERENCES household (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE event_user ADD CONSTRAINT FK_92589AE271F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_user ADD CONSTRAINT FK_92589AE2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA67F9BC654 FOREIGN KEY (paid_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA6E79FF843 FOREIGN KEY (household_id) REFERENCES household (id)');
        $this->addSql('ALTER TABLE expense_share ADD CONSTRAINT FK_SHARE_EXPENSE FOREIGN KEY (expense_id) REFERENCES expense (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE expense_share ADD CONSTRAINT FK_SHARE_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE home_members DROP FOREIGN KEY FK_F08019A7A76ED395');
        $this->addSql('ALTER TABLE home_members DROP FOREIGN KEY FK_F08019A7E79FF843');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25E79FF843');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25F4BD7827');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7E79FF843');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7B03A8386');
        $this->addSql('ALTER TABLE event_user DROP FOREIGN KEY FK_92589AE271F7E88B');
        $this->addSql('ALTER TABLE event_user DROP FOREIGN KEY FK_92589AE2A76ED395');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA67F9BC654');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA6E79FF843');
        $this->addSql('ALTER TABLE expense_share DROP FOREIGN KEY FK_SHARE_EXPENSE');
        $this->addSql('ALTER TABLE expense_share DROP FOREIGN KEY FK_SHARE_USER');

        $this->addSql('DROP TABLE expense_share');
        $this->addSql('DROP TABLE expense');
        $this->addSql('DROP TABLE event_user');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE home_members');
        $this->addSql('DROP TABLE household');
        $this->addSql('DROP TABLE user');
    }
}
