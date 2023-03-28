<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230326160349 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tenant DROP FOREIGN KEY FK_4E59C462C5FCE984');
        $this->addSql('DROP INDEX IDX_4E59C462C5FCE984 ON tenant');
        $this->addSql('ALTER TABLE tenant CHANGE flat_id flat_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE tenant ADD CONSTRAINT FK_4E59C462C5FCE984 FOREIGN KEY (flat_id_id) REFERENCES flat (id)');
        $this->addSql('CREATE INDEX IDX_4E59C462C5FCE984 ON tenant (flat_id_id)');
        $this->addSql('ALTER TABLE user ADD is_verified TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE user DROP is_verified');
        $this->addSql('ALTER TABLE tenant DROP FOREIGN KEY FK_4E59C462C5FCE984');
        $this->addSql('DROP INDEX IDX_4E59C462C5FCE984 ON tenant');
        $this->addSql('ALTER TABLE tenant CHANGE flat_id_id flat_id INT NOT NULL');
        $this->addSql('ALTER TABLE tenant ADD CONSTRAINT FK_4E59C462C5FCE984 FOREIGN KEY (flat_id) REFERENCES flat (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_4E59C462C5FCE984 ON tenant (flat_id)');
    }
}
