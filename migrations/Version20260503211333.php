<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503211333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create outbox_messages table for transactional domain event delivery.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE outbox_messages (id UUID NOT NULL, event_id UUID NOT NULL, event_type VARCHAR(255) NOT NULL, aggregate_type VARCHAR(255) DEFAULT NULL, aggregate_id VARCHAR(255) DEFAULT NULL, payload JSONB NOT NULL, occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, attempts INT DEFAULT 0 NOT NULL, last_error TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_OUTBOX_MESSAGES_UNPUBLISHED_AVAILABLE ON outbox_messages (published_at, available_at, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_OUTBOX_MESSAGES_UNPUBLISHED_AVAILABLE');
        $this->addSql('DROP TABLE outbox_messages');
    }
}
