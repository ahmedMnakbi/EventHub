<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261127104556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Zones des lieux, plusieurs images par événement, type de billet relié à une zone, suppression de sold';
    }

    public function up(Schema $schema): void
    {
        // Zones: one default zone per existing venue, covering its whole capacity.
        $this->addSql('CREATE TABLE zone (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(60) NOT NULL, capacity INT NOT NULL, venue_id INT NOT NULL, UNIQUE INDEX uniq_zone_venue_name (venue_id, name), INDEX IDX_A0EBC00740A73EBA (venue_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE zone ADD CONSTRAINT FK_A0EBC00740A73EBA FOREIGN KEY (venue_id) REFERENCES venue (id) ON DELETE CASCADE');
        $this->addSql("INSERT INTO zone (name, capacity, venue_id) SELECT 'Générale', capacity, id FROM venue");

        // Several images per event: the former poster becomes the first image.
        $this->addSql('ALTER TABLE event ADD images JSON DEFAULT NULL');
        $this->addSql('UPDATE event SET images = IF(image IS NULL, JSON_ARRAY(), JSON_ARRAY(image))');
        $this->addSql('ALTER TABLE event MODIFY images JSON NOT NULL, DROP image');

        // Ticket types now belong to a zone (the default zone of their event's venue); "sold" is computed from reservations.
        $this->addSql('ALTER TABLE ticket_type ADD zone_id INT DEFAULT NULL, DROP sold');
        $this->addSql('UPDATE ticket_type t INNER JOIN event e ON e.id = t.event_id INNER JOIN zone z ON z.venue_id = e.venue_id SET t.zone_id = z.id');
        $this->addSql('ALTER TABLE ticket_type MODIFY zone_id INT NOT NULL');
        $this->addSql('ALTER TABLE ticket_type ADD CONSTRAINT FK_BE0542119F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id)');
        $this->addSql('CREATE INDEX IDX_BE0542119F2C3FAB ON ticket_type (zone_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ticket_type DROP FOREIGN KEY FK_BE0542119F2C3FAB');
        $this->addSql('DROP INDEX IDX_BE0542119F2C3FAB ON ticket_type');
        $this->addSql('ALTER TABLE ticket_type ADD sold INT DEFAULT 0 NOT NULL, DROP zone_id');
        $this->addSql('ALTER TABLE event ADD image VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE event SET image = JSON_UNQUOTE(JSON_EXTRACT(images, \'$[0]\'))');
        $this->addSql('ALTER TABLE event DROP images');
        $this->addSql('ALTER TABLE zone DROP FOREIGN KEY FK_A0EBC00740A73EBA');
        $this->addSql('DROP TABLE zone');
    }
}
