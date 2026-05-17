<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260516071731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create projects schema and projects table for Projects context.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS projects');
        $this->addSql('CREATE TABLE projects.projects (id VARCHAR(36) NOT NULL, owner_account_id VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL, icon_key VARCHAR(64) DEFAULT \'folder\' NOT NULL, status VARCHAR(32) NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, archived_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, version INT DEFAULT 1 NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE projects.projects ADD CONSTRAINT projects_projects_owner_account_fk FOREIGN KEY (owner_account_id) REFERENCES public.accounts (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE projects.projects ADD CONSTRAINT projects_projects_icon_key_format_check CHECK (icon_key ~ \'^[a-z][a-z0-9_-]{0,63}$\')');
        $this->addSql('ALTER TABLE projects.projects ADD CONSTRAINT projects_projects_name_not_blank_check CHECK (length(trim(name)) > 0)');
        $this->addSql('ALTER TABLE projects.projects ADD CONSTRAINT projects_projects_status_check CHECK (status IN (\'active\', \'archived\', \'deleted\'))');
        $this->addSql('ALTER TABLE projects.projects ADD CONSTRAINT projects_projects_version_positive_check CHECK (version > 0)');
        $this->addSql('CREATE INDEX idx_projects_projects_owner_status ON projects.projects (owner_account_id, status)');
        $this->addSql('CREATE INDEX idx_projects_projects_owner_created_at ON projects.projects (owner_account_id, created_at DESC)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE IF EXISTS projects.projects DROP CONSTRAINT IF EXISTS projects_projects_version_positive_check');
        $this->addSql('ALTER TABLE IF EXISTS projects.projects DROP CONSTRAINT IF EXISTS projects_projects_status_check');
        $this->addSql('ALTER TABLE IF EXISTS projects.projects DROP CONSTRAINT IF EXISTS projects_projects_name_not_blank_check');
        $this->addSql('ALTER TABLE IF EXISTS projects.projects DROP CONSTRAINT IF EXISTS projects_projects_icon_key_format_check');
        $this->addSql('DROP INDEX IF EXISTS projects.idx_projects_projects_owner_created_at');
        $this->addSql('DROP INDEX IF EXISTS projects.idx_projects_projects_owner_status');
        $this->addSql('ALTER TABLE IF EXISTS projects.projects DROP CONSTRAINT IF EXISTS projects_projects_owner_account_fk');
        $this->addSql('DROP TABLE IF EXISTS projects.projects');
        $this->addSql('DROP SCHEMA IF EXISTS projects');
    }
}
