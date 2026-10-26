<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261021142812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE realisation_category (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, slug VARCHAR(160) NOT NULL, description LONGTEXT NOT NULL, gallery JSON NOT NULL, sort_order INT NOT NULL, status VARCHAR(16) DEFAULT \'draft\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, tag_service_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_5E43E33B989D9B62 (slug), INDEX IDX_5E43E33B7B00651C45AFA4EA (status, sort_order), INDEX IDX_5E43E33B48472BB7 (tag_service_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE realisation_category ADD CONSTRAINT FK_5E43E33B48472BB7 FOREIGN KEY (tag_service_id) REFERENCES service (id) ON DELETE SET NULL');
        $this->addSql('DROP TABLE project');
        $this->addSql('ALTER TABLE media_asset ADD width INT DEFAULT NULL, ADD height INT DEFAULT NULL, ADD variants JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, category VARCHAR(60) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, city_or_department VARCHAR(80) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, year INT DEFAULT NULL, duration_label VARCHAR(80) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, surface_label VARCHAR(80) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, description LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, services_done JSON NOT NULL, gallery_media_ids JSON NOT NULL, before_media_id INT DEFAULT NULL, after_media_id INT DEFAULT NULL, video_url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, is_featured TINYINT DEFAULT 0 NOT NULL, is_published TINYINT DEFAULT 0 NOT NULL, sort_order INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE realisation_category DROP FOREIGN KEY FK_5E43E33B48472BB7');
        $this->addSql('DROP TABLE realisation_category');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE media_asset DROP width, DROP height, DROP variants');
    }
}
