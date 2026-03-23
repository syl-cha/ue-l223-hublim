<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email_auth_code and two_factor_method columns to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user ADD email_auth_code VARCHAR(10) DEFAULT NULL, ADD two_factor_method VARCHAR(10) NOT NULL DEFAULT 'totp'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP email_auth_code, DROP two_factor_method');
    }
}
