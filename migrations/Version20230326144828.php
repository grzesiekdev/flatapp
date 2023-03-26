<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230326144828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE flat (id INT AUTO_INCREMENT NOT NULL, landlord_id INT NOT NULL, area INT NOT NULL, number_of_rooms INT NOT NULL, rent INT NOT NULL, fees LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', deposit INT DEFAULT NULL, pictures LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', pictures_for_tenant LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', description VARCHAR(1000) DEFAULT NULL, address VARCHAR(255) NOT NULL, rent_agreement VARCHAR(255) DEFAULT NULL, furnishing LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_554AAA44D48E7AED (landlord_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE landlord (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tenant (id INT NOT NULL, flat_id INT NOT NULL, INDEX IDX_4E59C462C5FCE984 (flat_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, date_of_birth DATE NOT NULL, address VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, phone VARCHAR(12) DEFAULT NULL, discriminator VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE flat ADD CONSTRAINT FK_554AAA44D48E7AED FOREIGN KEY (landlord_id) REFERENCES landlord (id)');
        $this->addSql('ALTER TABLE landlord ADD CONSTRAINT FK_F446E8F8BF396750 FOREIGN KEY (id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tenant ADD CONSTRAINT FK_4E59C462C5FCE984 FOREIGN KEY (flat_id) REFERENCES flat (id)');
        $this->addSql('ALTER TABLE tenant ADD CONSTRAINT FK_4E59C462BF396750 FOREIGN KEY (id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE flat DROP FOREIGN KEY FK_554AAA44D48E7AED');
        $this->addSql('ALTER TABLE landlord DROP FOREIGN KEY FK_F446E8F8BF396750');
        $this->addSql('ALTER TABLE tenant DROP FOREIGN KEY FK_4E59C462C5FCE984');
        $this->addSql('ALTER TABLE tenant DROP FOREIGN KEY FK_4E59C462BF396750');
        $this->addSql('DROP TABLE flat');
        $this->addSql('DROP TABLE landlord');
        $this->addSql('DROP TABLE tenant');
        $this->addSql('DROP TABLE user');
    }
}
