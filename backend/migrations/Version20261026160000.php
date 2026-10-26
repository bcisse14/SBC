<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261026160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fiches entreprise/clients et devis prives avec copies des coordonnees, sans modifier les demandes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE estimate_profile (id INT AUTO_INCREMENT NOT NULL, kind VARCHAR(10) NOT NULL, name VARCHAR(180) NOT NULL, data JSON NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE estimate (id INT AUTO_INCREMENT NOT NULL, request_id INT DEFAULT NULL, data JSON NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_D2EA4607427EB8A5 (request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE estimate ADD CONSTRAINT FK_D2EA4607427EB8A5 FOREIGN KEY (request_id) REFERENCES quote_request (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE estimate');
        $this->addSql('DROP TABLE estimate_profile');
    }
}
