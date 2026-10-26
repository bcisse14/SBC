<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261022130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Pages composables, annonces et ordre des avis, sans suppression des contenus existants.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cms_document (id INT AUTO_INCREMENT NOT NULL, resource VARCHAR(30) NOT NULL, slug VARCHAR(120) NOT NULL, data JSON NOT NULL, sort_order INT NOT NULL, is_published TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX cms_resource_slug (resource, slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE review ADD sort_order INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE review ALTER sort_order DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE cms_document');
        $this->addSql('ALTER TABLE review DROP sort_order');
    }
}
