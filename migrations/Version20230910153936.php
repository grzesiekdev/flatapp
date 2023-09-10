<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230910153936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE specialist (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, profession VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(12) DEFAULT NULL, gmb VARCHAR(255) DEFAULT NULL, note VARCHAR(1000) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE specialist_flat (specialist_id INT NOT NULL, flat_id INT NOT NULL, INDEX IDX_85BC71327B100C1A (specialist_id), INDEX IDX_85BC7132D3331C94 (flat_id), PRIMARY KEY(specialist_id, flat_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE specialist_flat ADD CONSTRAINT FK_85BC71327B100C1A FOREIGN KEY (specialist_id) REFERENCES specialist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE specialist_flat ADD CONSTRAINT FK_85BC7132D3331C94 FOREIGN KEY (flat_id) REFERENCES flat (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE specialist_flat DROP FOREIGN KEY FK_85BC71327B100C1A');
        $this->addSql('ALTER TABLE specialist_flat DROP FOREIGN KEY FK_85BC7132D3331C94');
        $this->addSql('DROP TABLE specialist');
        $this->addSql('DROP TABLE specialist_flat');
    }
}
