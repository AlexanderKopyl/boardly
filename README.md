# Boardly

Boardly is a Jira-like project/task/workflow management system.

## Architecture Baseline

The project starts as a Symfony backend with:

- Modular Monolith;
- Hexagonal Architecture;
- DDD;
- CQRS where useful;
- EDA where justified;
- PostgreSQL as source of truth;
- RabbitMQ for async side effects;
- Redis for cache / fast storage;
- OpenSearch for search / read-side use cases.

Read the architecture rules before designing modules or business flows:

```text
docs/architecture/project-architecture-rules.md
```

AI agent entrypoint:

```text
AGENTS.md
```

## Requirements

Preferred local setup:

- PHP 8.3+
- Composer 2+
- Docker
- Docker Compose
- Make

Symfony/PHP runs locally.
Infrastructure runs in Docker.

## First Install — Preferred Hybrid Mode

Copy local environment example:

```bash
cp .env.local.example .env.local
```

Install PHP dependencies locally:

```bash
composer install
```

Start infrastructure only:

```bash
make infra-up
```

Start Symfony locally:

```bash
make serve
```

Application:

```text
http://127.0.0.1:8080
```

Health check:

```text
GET http://127.0.0.1:8080/health
```

Expected response:

```json
{"status":"ok"}
```

## Alternative — Full Docker Mode

Use this only if local PHP/Composer/extensions are problematic:

```bash
make install
make up
```

Application:

```text
http://localhost:8080
```

## Infrastructure Services

RabbitMQ management UI:

```text
http://localhost:15672
```

Default local credentials:

```text
guest / guest
```

OpenSearch:

```text
http://localhost:9200
```

PostgreSQL:

```text
localhost:5432
```

Default local database credentials:

```text
Database: boardly
User: boardly
Password: boardly
```

Redis:

```text
localhost:6379
```

## Useful Commands

Hybrid/local commands:

```bash
make infra-up       # Start PostgreSQL, Redis, RabbitMQ, OpenSearch
make infra-down     # Stop infrastructure services
make infra-restart  # Restart infrastructure services
make serve          # Run Symfony locally on 127.0.0.1:8080
make local-install  # Run composer install locally
make local-console  # Run Symfony console locally
make local-test     # Run PHPUnit locally
make local-phpstan  # Run PHPStan locally
make local-cs-fix   # Run PHP CS Fixer locally
make local-rector   # Run Rector locally
make local-qa       # Run local tests and static analysis
```

Full Docker commands:

```bash
make install  # Build app container and run composer install inside Docker
make up       # Start all services including app container
make down     # Stop all services
make restart  # Restart all services
make shell    # Open shell in app container
make console  # Run Symfony console inside Docker
make test     # Run PHPUnit inside Docker
make phpstan  # Run PHPStan inside Docker
make cs-fix   # Run PHP CS Fixer inside Docker
make rector   # Run Rector inside Docker
make qa       # Run tests and static analysis inside Docker
make logs     # Follow Docker logs
```

## Composer Lock

This is an application repository, not a reusable library.

After the first successful install, commit:

```text
composer.lock
```

This keeps dependency versions reproducible for all developers and CI.

## Current Scope

This repository currently contains only the technical bootstrap:

- Symfony kernel;
- HTTP front controller;
- console entrypoint;
- health check endpoint;
- Docker development stack;
- PHPUnit/PHPStan/Rector/PHP CS Fixer configuration;
- architecture documentation.

Business modules such as Issues, Projects, Workflow, Boards, and Permissions should be added only after the relevant architecture decision or module design is documented.

## Documentation

- `AGENTS.md` — AI agent entrypoint and documentation routing.
- `docs/architecture/project-architecture-rules.md` — project architecture rules.
- `docs/agents/subagents-map.md` — subagent routing map.
- `docs/adr/0000-template.md` — ADR template.
