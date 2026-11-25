<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261125164149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Événements et types de billets';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, slug VARCHAR(170) NOT NULL, description LONGTEXT NOT NULL, image VARCHAR(255) DEFAULT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, category_id INT NOT NULL, venue_id INT NOT NULL, INDEX idx_event_start_at (start_at), INDEX idx_event_status (status), INDEX IDX_3BAE0AA712469DE2 (category_id), INDEX IDX_3BAE0AA740A73EBA (venue_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ticket_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(60) NOT NULL, price NUMERIC(10, 2) NOT NULL, quantity INT NOT NULL, sold INT DEFAULT 0 NOT NULL, event_id INT NOT NULL, UNIQUE INDEX uniq_ticket_type_event_name (event_id, name), INDEX IDX_BE05421171F7E88B (event_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA712469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA740A73EBA FOREIGN KEY (venue_id) REFERENCES venue (id)');
        $this->addSql('ALTER TABLE ticket_type ADD CONSTRAINT FK_BE05421171F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA712469DE2');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA740A73EBA');
        $this->addSql('ALTER TABLE ticket_type DROP FOREIGN KEY FK_BE05421171F7E88B');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE ticket_type');
    }
}
