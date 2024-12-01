<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241201141427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE documentation ADD title VARCHAR(255) NOT NULL, ADD content LONGTEXT NOT NULL, ADD section VARCHAR(100) DEFAULT NULL, ADD type VARCHAR(50) DEFAULT NULL, DROP context, DROP problem_title, DROP problem_description, DROP solution');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE documentation ADD context VARCHAR(255) DEFAULT NULL, ADD problem_title VARCHAR(255) DEFAULT NULL, ADD problem_description LONGTEXT DEFAULT NULL, ADD solution LONGTEXT DEFAULT NULL, DROP title, DROP content, DROP section, DROP type');
    }
}
