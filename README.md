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

- Docker
- Docker Compose
- Make

Local PHP and Composer are optional if you work through Docker.

## First Install

```bash
make install
```

This builds the PHP container and runs:

```bash
composer install
```

## Start Development Stack

```bash
make up
```

Application:

```text
http://localhost:8080
```

Health check:

```text
GET http://localhost:8080/health
```

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

## Useful Commands

```bash
make up       # Start services
make down     # Stop services
make restart  # Restart services
make shell    # Open shell in app container
make console  # Run Symfony console
make test     # Run PHPUnit
make phpstan  # Run PHPStan
make cs-fix   # Run PHP CS Fixer
make rector   # Run Rector
make qa       # Run tests and static analysis
make logs     # Follow Docker logs
```

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
