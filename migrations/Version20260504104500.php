<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260504104500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create processed_messages table for async handler idempotency.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE processed_messages (event_id UUID NOT NULL, handler_name VARCHAR(255) NOT NULL, status VARCHAR(32) NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, processed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (event_id, handler_name))');
        $this->addSql('CREATE INDEX IDX_PROCESSED_MESSAGES_HANDLER_STATUS ON processed_messages (handler_name, status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_PROCESSED_MESSAGES_HANDLER_STATUS');
        $this->addSql('DROP TABLE processed_messages');
    }
}
