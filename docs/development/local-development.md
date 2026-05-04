# Local Development

This guide describes the local development setup for Boardly.

It focuses on the current Mac/Homebrew workflow where Symfony/PHP-FPM runs on the host machine and infrastructure services run locally or through Docker with ports exposed to `127.0.0.1`.

## Environment files

Use `.env` for safe committed defaults.

Use `.env.local` for machine-specific local values. This file is ignored by Git and must not be committed.

Start from the example file:

```bash
cp .env.local.example .env.local
```

Then adjust values if your local ports, database name, or credentials differ.

## Local hostnames vs Docker service names

Use `127.0.0.1` when Symfony runs on the host machine:

```env
DATABASE_URL="postgresql://broadly:broadly@127.0.0.1:5432/broadly?serverVersion=18&charset=utf8"
MESSENGER_TRANSPORT_DSN="amqp://guest:guest@127.0.0.1:5672/%2f/messages"
REDIS_DSN="redis://127.0.0.1:6379"
OPENSEARCH_URL="http://127.0.0.1:9200"
```

Use Docker service names only when Symfony itself runs inside Docker:

```env
DATABASE_URL="postgresql://broadly:broadly@postgres:5432/broadly?serverVersion=18&charset=utf8"
MESSENGER_TRANSPORT_DSN="amqp://guest:guest@rabbitmq:5672/%2f/messages"
REDIS_DSN="redis://redis:6379"
OPENSEARCH_URL="http://opensearch:9200"
```

The most common local mistake is using `postgres` or `rabbitmq` while PHP-FPM is running directly on the host machine. In that case PHP cannot resolve Docker-internal service names.

## Required services

The current local development baseline expects:

- PHP 8.3 or newer;
- PostgreSQL 18-compatible database server;
- RabbitMQ with AMQP port `5672`;
- Redis on port `6379`;
- OpenSearch on port `9200`.

Redis and OpenSearch may not be required by every request yet, but they are part of the target local infrastructure.

## PostgreSQL

Expected local database connection:

```env
DATABASE_URL="postgresql://broadly:broadly@127.0.0.1:5432/broadly?serverVersion=18&charset=utf8"
```

Useful checks:

```bash
php bin/console debug:dotenv DATABASE_URL
php bin/console doctrine:migrations:status -vvv
```

If the database or user does not exist, create them according to your local PostgreSQL installation.

Example using local PostgreSQL tools:

```bash
createuser broadly
createdb -O broadly broadly
psql postgres -c "ALTER USER broadly WITH PASSWORD 'broadly';"
```

Then run migrations:

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

## RabbitMQ

Expected local AMQP DSN:

```env
MESSENGER_TRANSPORT_DSN="amqp://guest:guest@127.0.0.1:5672/%2f/messages"
```

Check Messenger routing:

```bash
php bin/console debug:messenger
```

Check RabbitMQ queues:

```bash
rabbitmqctl list_queues name messages_ready messages_unacknowledged
```

Current registration flow uses this chain:

```text
POST /api/auth/register
-> outbox_messages
-> boardly:outbox:publish
-> RabbitMQ queue
-> messenger:consume async
```

Publish pending outbox records:

```bash
php bin/console boardly:outbox:publish --limit=10 -vvv
```

Consume one async message:

```bash
php bin/console messenger:consume async -vv --limit=1
```

## Redis

Expected local Redis DSN:

```env
REDIS_DSN="redis://127.0.0.1:6379"
```

Basic check:

```bash
redis-cli ping
```

Expected output:

```text
PONG
```

## OpenSearch

Expected local OpenSearch URL:

```env
OPENSEARCH_URL="http://127.0.0.1:9200"
```

Basic check:

```bash
curl http://127.0.0.1:9200
```

## Nginx, PHP-FPM, and Xdebug

When debugging HTTP requests through PHP-FPM, Xdebug can pause execution on a breakpoint.

If PHP-FPM stays paused longer than the Nginx FastCGI timeout, Nginx may return:

```text
upstream timed out while reading response header from upstream
```

This looks like a `502 Bad Gateway`, but it can simply mean PHP is paused in the debugger.

For local development only, increase FastCGI timeouts in the PHP location block:

```nginx
fastcgi_read_timeout 300;
fastcgi_send_timeout 300;
fastcgi_connect_timeout 300;
```

Then validate and reload Nginx:

```bash
nginx -t
nginx -s reload
```

With Homebrew services, restarting Nginx may be:

```bash
brew services restart nginx
```

Do not treat these values as production defaults. They are for local debugging convenience.

## Common Makefile commands

Clear Symfony cache:

```bash
make cc
```

Check migrations:

```bash
make db-status
```

Run migrations:

```bash
make db-migrate
```

Consume async messages:

```bash
make messenger-consume
```

Debug a console command with Xdebug:

```bash
make console-debug CONSOLE_ARGS='list'
```

If PHP is not found automatically:

```bash
make console-debug PHP_BIN=/opt/homebrew/bin/php CONSOLE_ARGS='list'
```

## Registration and queue verification checklist

1. Prepare local environment:

```bash
cp .env.local.example .env.local
php bin/console cache:clear
```

2. Verify database configuration:

```bash
php bin/console debug:dotenv DATABASE_URL
php bin/console doctrine:migrations:status -vvv
```

3. Verify Messenger configuration:

```bash
php bin/console debug:messenger
```

4. Register an account through the API.

5. Check that an outbox record exists:

```bash
psql -d broadly -c "SELECT id, event_type, published_at, attempts, last_error, created_at FROM outbox_messages ORDER BY created_at DESC LIMIT 5;"
```

6. Publish pending outbox records:

```bash
php bin/console boardly:outbox:publish --limit=10 -vvv
```

7. Check RabbitMQ queue:

```bash
rabbitmqctl list_queues name messages_ready messages_unacknowledged
```

8. Consume one message:

```bash
php bin/console messenger:consume async -vv --limit=1
```

9. Check idempotency record:

```bash
psql -d broadly -c "SELECT event_id, handler_name, status, processed_at FROM processed_messages ORDER BY started_at DESC LIMIT 5;"
```

## Troubleshooting

### `could not translate host name "postgres"`

Symfony is probably running on the host machine while `DATABASE_URL` uses a Docker service name.

Use `127.0.0.1` in `.env.local`.

### `upstream timed out while reading response header from upstream`

PHP-FPM did not return headers before the Nginx timeout. During local debugging this often means execution is paused on an Xdebug breakpoint.

Continue execution or increase local FastCGI timeout.

### Message appears in RabbitMQ but handler does not run

Start the Symfony consumer:

```bash
php bin/console messenger:consume async -vv --limit=1
```

### Outbox record exists but no RabbitMQ message appears

Run the outbox publisher:

```bash
php bin/console boardly:outbox:publish --limit=10 -vvv
```

Then inspect `last_error` in `outbox_messages` if publishing fails.
