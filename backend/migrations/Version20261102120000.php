<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261102120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Resume des fiches de realisations affiche sur les cartes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE realisation_category ADD summary LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE realisation_category DROP summary');
    }
}
