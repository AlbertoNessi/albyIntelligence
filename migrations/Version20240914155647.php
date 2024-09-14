<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240914155647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE calendar_events (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(50) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, event_date DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE file_documents (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(100) DEFAULT NULL, filepath VARCHAR(255) DEFAULT NULL, uploaded_at DATETIME DEFAULT NULL, file_type VARCHAR(10) DEFAULT NULL, uploaded_by VARCHAR(10) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE locations (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, saved_at DATETIME DEFAULT NULL, user VARCHAR(10) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notifications (id INT AUTO_INCREMENT NOT NULL, message VARCHAR(255) DEFAULT NULL, flag_read TINYINT(1) DEFAULT NULL, action VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reminders (id INT AUTO_INCREMENT NOT NULL, due_date DATETIME DEFAULT NULL, priority VARCHAR(10) DEFAULT NULL, task VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE search_history (id INT AUTO_INCREMENT NOT NULL, query LONGTEXT DEFAULT NULL, searched_at DATETIME DEFAULT NULL, user VARCHAR(10) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tasks (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) DEFAULT NULL, due_date DATETIME DEFAULT NULL, priority VARCHAR(10) DEFAULT NULL, status VARCHAR(10) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE calendar_events');
        $this->addSql('DROP TABLE file_documents');
        $this->addSql('DROP TABLE locations');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE reminders');
        $this->addSql('DROP TABLE search_history');
        $this->addSql('DROP TABLE tasks');
    }
}
