<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503075438 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create accounts table for IdentityAccess account persistence.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE accounts (id VARCHAR(36) NOT NULL, email VARCHAR(255) NOT NULL, password_hash VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, status VARCHAR(255) NOT NULL, is_system_admin BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, approved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, rejected_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, disabled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, version INT DEFAULT 1 NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE accounts ADD CONSTRAINT uniq_accounts_email UNIQUE (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE accounts');
    }
}
