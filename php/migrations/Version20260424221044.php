<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260424221044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book ADD user_entity_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE book ADD CONSTRAINT FK_CBE5A33181C5F0B9 FOREIGN KEY (user_entity_id) REFERENCES "user" (id)');
        $this->addSql('CREATE INDEX IDX_CBE5A33181C5F0B9 ON book (user_entity_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book DROP CONSTRAINT FK_CBE5A33181C5F0B9');
        $this->addSql('DROP INDEX IDX_CBE5A33181C5F0B9');
        $this->addSql('ALTER TABLE book DROP user_entity_id');
    }
}
