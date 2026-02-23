<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223160015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE card_status (card_id INT NOT NULL, status_id INT NOT NULL, INDEX IDX_93F36684ACC9A20 (card_id), INDEX IDX_93F36686BF700BD (status_id), PRIMARY KEY (card_id, status_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE card_study_field (card_id INT NOT NULL, study_field_id INT NOT NULL, INDEX IDX_E35B4A224ACC9A20 (card_id), INDEX IDX_E35B4A22E7BE1239 (study_field_id), PRIMARY KEY (card_id, study_field_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, brief VARCHAR(255) DEFAULT NULL, parent_id INT DEFAULT NULL, INDEX IDX_64C19C1727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE image (id INT AUTO_INCREMENT NOT NULL, file_name VARCHAR(255) NOT NULL, size INT NOT NULL, card_id INT NOT NULL, INDEX IDX_C53D045F4ACC9A20 (card_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, is_read TINYINT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, card_id INT NOT NULL, INDEX IDX_B6BD307FA76ED395 (user_id), INDEX IDX_B6BD307F4ACC9A20 (card_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE status (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE study_field (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, theme VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user_card (user_id INT NOT NULL, card_id INT NOT NULL, INDEX IDX_6C95D41AA76ED395 (user_id), INDEX IDX_6C95D41A4ACC9A20 (card_id), PRIMARY KEY (user_id, card_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE card_status ADD CONSTRAINT FK_93F36684ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE card_status ADD CONSTRAINT FK_93F36686BF700BD FOREIGN KEY (status_id) REFERENCES status (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE card_study_field ADD CONSTRAINT FK_E35B4A224ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE card_study_field ADD CONSTRAINT FK_E35B4A22E7BE1239 FOREIGN KEY (study_field_id) REFERENCES study_field (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F4ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F4ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id)');
        $this->addSql('ALTER TABLE user_card ADD CONSTRAINT FK_6C95D41AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_card ADD CONSTRAINT FK_6C95D41A4ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE card ADD user_id INT NOT NULL, ADD category_id INT NOT NULL');
        $this->addSql('ALTER TABLE card ADD CONSTRAINT FK_161498D3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE card ADD CONSTRAINT FK_161498D312469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('CREATE INDEX IDX_161498D3A76ED395 ON card (user_id)');
        $this->addSql('CREATE INDEX IDX_161498D312469DE2 ON card (category_id)');
        $this->addSql('ALTER TABLE user ADD status_id INT NOT NULL, ADD study_field_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6496BF700BD FOREIGN KEY (status_id) REFERENCES status (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649E7BE1239 FOREIGN KEY (study_field_id) REFERENCES study_field (id)');
        $this->addSql('CREATE INDEX IDX_8D93D6496BF700BD ON user (status_id)');
        $this->addSql('CREATE INDEX IDX_8D93D649E7BE1239 ON user (study_field_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE card_status DROP FOREIGN KEY FK_93F36684ACC9A20');
        $this->addSql('ALTER TABLE card_status DROP FOREIGN KEY FK_93F36686BF700BD');
        $this->addSql('ALTER TABLE card_study_field DROP FOREIGN KEY FK_E35B4A224ACC9A20');
        $this->addSql('ALTER TABLE card_study_field DROP FOREIGN KEY FK_E35B4A22E7BE1239');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1727ACA70');
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045F4ACC9A20');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FA76ED395');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F4ACC9A20');
        $this->addSql('ALTER TABLE user_card DROP FOREIGN KEY FK_6C95D41AA76ED395');
        $this->addSql('ALTER TABLE user_card DROP FOREIGN KEY FK_6C95D41A4ACC9A20');
        $this->addSql('DROP TABLE card_status');
        $this->addSql('DROP TABLE card_study_field');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE image');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE status');
        $this->addSql('DROP TABLE study_field');
        $this->addSql('DROP TABLE user_card');
        $this->addSql('ALTER TABLE card DROP FOREIGN KEY FK_161498D3A76ED395');
        $this->addSql('ALTER TABLE card DROP FOREIGN KEY FK_161498D312469DE2');
        $this->addSql('DROP INDEX IDX_161498D3A76ED395 ON card');
        $this->addSql('DROP INDEX IDX_161498D312469DE2 ON card');
        $this->addSql('ALTER TABLE card DROP user_id, DROP category_id');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6496BF700BD');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649E7BE1239');
        $this->addSql('DROP INDEX IDX_8D93D6496BF700BD ON user');
        $this->addSql('DROP INDEX IDX_8D93D649E7BE1239 ON user');
        $this->addSql('ALTER TABLE user DROP status_id, DROP study_field_id');
    }
}
