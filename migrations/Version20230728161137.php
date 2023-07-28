<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230728161137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE utility_meter_reading (id INT AUTO_INCREMENT NOT NULL, flat_id INT NOT NULL, date DATE NOT NULL, water LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', gas LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', electricity LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_BDB3C62FD3331C94 (flat_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE utility_meter_reading ADD CONSTRAINT FK_BDB3C62FD3331C94 FOREIGN KEY (flat_id) REFERENCES flat (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utility_meter_reading DROP FOREIGN KEY FK_BDB3C62FD3331C94');
        $this->addSql('DROP TABLE utility_meter_reading');
    }
}
