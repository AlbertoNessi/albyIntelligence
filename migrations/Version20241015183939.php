<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241015183939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE locations ADD city VARCHAR(20) DEFAULT NULL, ADD region VARCHAR(10) DEFAULT NULL, DROP latitude, DROP longitude, DROP saved_at, CHANGE user province VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE search_history DROP user');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE locations ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL, ADD saved_at DATETIME DEFAULT NULL, ADD user VARCHAR(10) DEFAULT NULL, DROP city, DROP province, DROP region');
        $this->addSql('ALTER TABLE search_history ADD user VARCHAR(10) DEFAULT NULL');
    }
}
