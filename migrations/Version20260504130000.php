<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260504130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create refresh_sessions table for server-side refresh-token session persistence.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE refresh_sessions (id VARCHAR(36) NOT NULL, account_id VARCHAR(36) NOT NULL, token_hash VARCHAR(255) NOT NULL, family_id VARCHAR(36) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, replaced_by_token_id VARCHAR(36) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, user_agent_hash VARCHAR(255) DEFAULT NULL, ip_hash VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_refresh_sessions_token_hash ON refresh_sessions (token_hash)');
        $this->addSql('CREATE INDEX idx_refresh_sessions_account_id ON refresh_sessions (account_id)');
        $this->addSql('CREATE INDEX idx_refresh_sessions_family_id ON refresh_sessions (family_id)');
        $this->addSql('CREATE INDEX idx_refresh_sessions_expires_at ON refresh_sessions (expires_at)');
        $this->addSql('CREATE INDEX idx_refresh_sessions_revoked_at ON refresh_sessions (revoked_at)');
        $this->addSql('ALTER TABLE refresh_sessions ADD CONSTRAINT fk_refresh_sessions_account_id FOREIGN KEY (account_id) REFERENCES accounts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE refresh_sessions DROP CONSTRAINT fk_refresh_sessions_account_id');
        $this->addSql('DROP INDEX uniq_refresh_sessions_token_hash');
        $this->addSql('DROP INDEX idx_refresh_sessions_account_id');
        $this->addSql('DROP INDEX idx_refresh_sessions_family_id');
        $this->addSql('DROP INDEX idx_refresh_sessions_expires_at');
        $this->addSql('DROP INDEX idx_refresh_sessions_revoked_at');
        $this->addSql('DROP TABLE refresh_sessions');
    }
}
