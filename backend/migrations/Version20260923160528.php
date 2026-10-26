<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260923160528 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(120) NOT NULL, entity_name VARCHAR(120) NOT NULL, entity_id INT DEFAULT NULL, payload JSON NOT NULL, created_at DATETIME NOT NULL, actor_id INT DEFAULT NULL, INDEX IDX_F6E1C0F510DAF24A (actor_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE certification (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(140) NOT NULL, description LONGTEXT DEFAULT NULL, verification_url VARCHAR(255) DEFAULT NULL, logo_media_id INT DEFAULT NULL, sort_order INT NOT NULL, is_published TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE content_entry (id INT AUTO_INCREMENT NOT NULL, content_key VARCHAR(150) NOT NULL, default_value LONGTEXT NOT NULL, custom_value LONGTEXT DEFAULT NULL, status VARCHAR(16) DEFAULT \'published\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_C0E2C9A23F7BFD5C (content_key), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE content_revision (id INT AUTO_INCREMENT NOT NULL, value LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, entry_id INT NOT NULL, editor_id INT DEFAULT NULL, INDEX IDX_3FD4FF23BA364942 (entry_id), INDEX IDX_3FD4FF236995AC4C (editor_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE faq_item (id INT AUTO_INCREMENT NOT NULL, question VARCHAR(255) NOT NULL, answer LONGTEXT NOT NULL, sort_order INT NOT NULL, is_published TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE media_asset (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(80) NOT NULL, alt_text VARCHAR(255) DEFAULT NULL, focal_point VARCHAR(32) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE partner (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(140) NOT NULL, logo_media_id INT DEFAULT NULL, website_url VARCHAR(255) DEFAULT NULL, category VARCHAR(80) DEFAULT NULL, sort_order INT NOT NULL, is_published TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE photo_slot (id INT AUTO_INCREMENT NOT NULL, slot_key VARCHAR(120) NOT NULL, ratio VARCHAR(30) NOT NULL, minimum_size VARCHAR(100) NOT NULL, ideal_description VARCHAR(255) NOT NULL, media_asset_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_2D3F929318A4F944 (slot_key), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, category VARCHAR(60) NOT NULL, city_or_department VARCHAR(80) DEFAULT NULL, year INT DEFAULT NULL, duration_label VARCHAR(80) DEFAULT NULL, surface_label VARCHAR(80) DEFAULT NULL, description LONGTEXT NOT NULL, services_done JSON NOT NULL, gallery_media_ids JSON NOT NULL, before_media_id INT DEFAULT NULL, after_media_id INT DEFAULT NULL, video_url VARCHAR(255) DEFAULT NULL, is_featured TINYINT DEFAULT 0 NOT NULL, is_published TINYINT DEFAULT 0 NOT NULL, sort_order INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quote_attachment (id INT AUTO_INCREMENT NOT NULL, storage_path VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(80) NOT NULL, size_bytes INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, quote_request_id INT NOT NULL, INDEX IDX_C908A300F229C21E (quote_request_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quote_request (id INT AUTO_INCREMENT NOT NULL, property_type VARCHAR(30) NOT NULL, requested_services JSON NOT NULL, description LONGTEXT NOT NULL, city VARCHAR(120) NOT NULL, postal_code VARCHAR(20) NOT NULL, budget_range VARCHAR(40) NOT NULL, timeline VARCHAR(50) NOT NULL, visit_requested TINYINT DEFAULT 0 NOT NULL, first_name VARCHAR(80) NOT NULL, last_name VARCHAR(80) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(40) NOT NULL, callback_time VARCHAR(80) DEFAULT NULL, status VARCHAR(255) DEFAULT \'new\' NOT NULL, is_spam TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, author_name VARCHAR(120) NOT NULL, city VARCHAR(120) NOT NULL, project_type VARCHAR(120) NOT NULL, rating INT NOT NULL, message LONGTEXT NOT NULL, source VARCHAR(120) DEFAULT NULL, is_published TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE service (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, service_group VARCHAR(30) NOT NULL, description LONGTEXT NOT NULL, items JSON NOT NULL, media_asset_id INT DEFAULT NULL, sort_order INT NOT NULL, is_published TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE setting (id INT AUTO_INCREMENT NOT NULL, setting_key VARCHAR(120) NOT NULL, setting_value LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_9F74B8985FA1E697 (setting_key), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(80) DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F510DAF24A FOREIGN KEY (actor_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE content_revision ADD CONSTRAINT FK_3FD4FF23BA364942 FOREIGN KEY (entry_id) REFERENCES content_entry (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE content_revision ADD CONSTRAINT FK_3FD4FF236995AC4C FOREIGN KEY (editor_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE quote_attachment ADD CONSTRAINT FK_C908A300F229C21E FOREIGN KEY (quote_request_id) REFERENCES quote_request (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F510DAF24A');
        $this->addSql('ALTER TABLE content_revision DROP FOREIGN KEY FK_3FD4FF23BA364942');
        $this->addSql('ALTER TABLE content_revision DROP FOREIGN KEY FK_3FD4FF236995AC4C');
        $this->addSql('ALTER TABLE quote_attachment DROP FOREIGN KEY FK_C908A300F229C21E');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE certification');
        $this->addSql('DROP TABLE content_entry');
        $this->addSql('DROP TABLE content_revision');
        $this->addSql('DROP TABLE faq_item');
        $this->addSql('DROP TABLE media_asset');
        $this->addSql('DROP TABLE partner');
        $this->addSql('DROP TABLE photo_slot');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE quote_attachment');
        $this->addSql('DROP TABLE quote_request');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP TABLE setting');
        $this->addSql('DROP TABLE `user`');
    }
}
