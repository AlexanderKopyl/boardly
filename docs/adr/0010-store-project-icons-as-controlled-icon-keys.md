# ADR-0010: Store Project Icons as Controlled Icon Keys

## Status

Accepted

## Date

2026-05-16

## Owner

- Architect: Boardly architecture owner
- Domain owner: Product / project management domain owner
- Technical owner: Backend / database architecture owner

## Context

Boardly Projects now need a project icon in addition to the project name and status.

This affects the Projects MVP introduced in issue `#55` and the persistence direction described by:

- `docs/adr/0008-use-layered-project-access-control-with-future-database-protection.md`
- `docs/adr/0009-use-postgresql-schemas-for-bounded-context-and-infrastructure-boundaries.md`

The icon requirement must be handled without overcomplicating the first Projects slice.

There are several possible interpretations of "project icon":

```text
- a frontend icon identifier;
- an emoji;
- a stored SVG;
- an uploaded image;
- a URL to an external image;
- a file stored in object storage;
- a future asset entity.
```

For the MVP, Boardly does not need user-uploaded project images or binary icon storage. The initial need is to let users choose a stable icon from a controlled application icon set.

## Decision

We will store project icons as a controlled string key on the project record.

The column name is:

```text
icon_key
```

The value represents an application-owned icon identifier, for example:

```text
folder
kanban
rocket
bug
book
shield
```

The Projects table must include:

```sql
icon_key VARCHAR(64) NOT NULL DEFAULT 'folder'
```

The database should prevent blank or malformed icon keys with a CHECK constraint.

For the current PostgreSQL schema direction, the Projects table becomes:

```sql
CREATE TABLE projects.projects (
    id VARCHAR(36) PRIMARY KEY,
    owner_account_id VARCHAR(36) NOT NULL,

    name VARCHAR(120) NOT NULL,
    icon_key VARCHAR(64) NOT NULL DEFAULT 'folder',
    status VARCHAR(32) NOT NULL,

    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    archived_at TIMESTAMPTZ NULL,

    version INT NOT NULL DEFAULT 1,

    CONSTRAINT projects_projects_owner_account_fk
        FOREIGN KEY (owner_account_id)
        REFERENCES public.accounts (id)
        ON DELETE RESTRICT,

    CONSTRAINT projects_projects_status_check
        CHECK (status IN ('active', 'archived')),

    CONSTRAINT projects_projects_name_not_blank_check
        CHECK (length(trim(name)) > 0),

    CONSTRAINT projects_projects_icon_key_format_check
        CHECK (icon_key ~ '^[a-z][a-z0-9_-]{0,63}$'),

    CONSTRAINT projects_projects_version_positive_check
        CHECK (version > 0)
);
```

No index is required on `icon_key` for the MVP.

## Scope

This decision applies to:

- Projects bounded context;
- Projects MVP persistence;
- Project create/read API contracts;
- Doctrine ProjectEntity mapping;
- frontend project list/card rendering;
- future icon evolution rules.

This decision does not apply to:

- user avatar storage;
- account profile images;
- attachments;
- custom uploaded project logos;
- external media URLs;
- object storage architecture;
- final design-system icon inventory.

## Architectural Rules

- Store the project icon as `icon_key`, not as raw SVG, binary data, or external URL.
- `icon_key` must be non-null.
- `icon_key` must have a safe default value.
- `icon_key` must not be used for authorization, ownership, or workflow logic.
- `icon_key` is presentation metadata owned by the Projects context.
- The domain may validate icon key format, but the database must also protect against blank/malformed values.
- The frontend must map `icon_key` to an approved icon from the UI icon registry.
- The backend must not trust arbitrary frontend icon strings without validation.
- Do not add indexes on `icon_key` unless a real query requires filtering/grouping by icon.
- Do not store uploaded custom icons in `projects.projects`.
- If custom uploaded icons are introduced later, model them as separate asset/media persistence through a new ADR or ADR update.

## API Contract Direction

Project creation may accept an optional `iconKey`:

```json
{
  "name": "Boardly",
  "iconKey": "kanban"
}
```

If `iconKey` is omitted, the backend uses the default:

```text
folder
```

Project responses should expose the selected icon key:

```json
{
  "id": "project-id",
  "name": "Boardly",
  "iconKey": "kanban",
  "status": "active",
  "createdAt": "2026-05-14T12:00:00+00:00"
}
```

The API must not expose database column naming directly if the API convention is camelCase.

Use:

```text
iconKey
```

not:

```text
icon_key
```

## Domain Direction

The Projects domain should introduce a small value object or equivalent validation rule for project icons when the domain model is implemented.

Possible value object:

```text
ProjectIconKey
```

Responsibilities:

```text
- reject blank values;
- enforce max length;
- enforce safe identifier format;
- optionally validate against an application-approved icon allow-list.
```

Allowed format for MVP:

```text
^[a-z][a-z0-9_-]{0,63}$
```

This keeps values stable and safe for API responses, frontend mapping, and future theming.

## Doctrine Implementation Direction

ProjectEntity should map `icon_key` as a scalar string:

```php
#[ORM\Column(name: 'icon_key', type: 'string', length: 64, options: ['default' => 'folder'])]
private string $iconKey = 'folder';
```

Do not model icons as a Doctrine association for the MVP.

The Project aggregate/application model should expose icon intent through a stable value, not through frontend component names.

Good:

```text
kanban
rocket
bug
book
```

Weak:

```text
LucideKanbanSquare
HeroiconsRocketLaunch
frontend/components/icons/BugIcon.tsx
```

The icon key must remain UI-library independent.

## PostgreSQL / Migration Direction

For a new table, include `icon_key` in the initial `CREATE TABLE`.

For an existing table, use a safe staged migration:

```sql
ALTER TABLE projects.projects
    ADD COLUMN icon_key VARCHAR(64) NOT NULL DEFAULT 'folder';

ALTER TABLE projects.projects
    ADD CONSTRAINT projects_projects_icon_key_format_check
    CHECK (icon_key ~ '^[a-z][a-z0-9_-]{0,63}$');
```

For a very large future table, avoid adding a non-null column with a default without reviewing PostgreSQL rewrite/locking behavior for the target version and deployment environment.

For the current early Projects MVP, adding the column directly in the initial table is acceptable.

## Alternatives Considered

### Option A: Store raw SVG in the project table

Pros:

- maximum visual flexibility;
- no frontend icon registry required.

Cons:

- unsafe and unnecessary for MVP;
- bloats project rows;
- introduces sanitization and XSS concerns;
- mixes presentation assets into domain persistence;
- harder to cache and validate.

Rejected.

### Option B: Store external icon URL

Pros:

- simple for externally hosted images;
- flexible for future custom icons.

Cons:

- weak control over content and availability;
- security and privacy concerns;
- broken links become product state;
- unnecessary for controlled MVP icons.

Rejected for MVP.

### Option C: Store emoji

Pros:

- simple;
- no icon library dependency;
- easy user customization.

Cons:

- inconsistent rendering across platforms;
- weaker brand control;
- harder to align with a product UI system;
- still requires validation decisions.

Not selected for the Projects MVP.

### Option D: Store controlled `icon_key`

Pros:

- simple database model;
- stable API contract;
- frontend can map to approved icons;
- avoids binary/media storage;
- avoids UI-library-specific persistence;
- works well with DDD value object validation;
- easy to migrate later if custom icons are needed.

Cons:

- limited to predefined icons;
- requires frontend/backend agreement on allowed keys;
- changing an icon set requires compatibility discipline.

Accepted.

### Option E: Separate `project_icons` or `assets` table now

Pros:

- flexible for future uploaded logos;
- clear asset ownership model.

Cons:

- overengineering for the Projects MVP;
- requires file storage/security decisions;
- increases API and persistence complexity before the need exists.

Rejected for now. May be revisited later.

## Consequences

### Positive

- Project icons are supported without adding file/media complexity.
- The Projects table remains simple.
- API responses can include project icons immediately.
- The frontend can render icons through a controlled registry.
- Database constraints prevent malformed icon keys.
- Future custom icons remain possible through a separate design decision.

### Negative

- Users cannot upload arbitrary custom project images in the MVP.
- Backend and frontend must coordinate allowed icon keys.
- If the frontend icon library changes, icon keys must remain stable or be migrated.

### Neutral / Operational

- No additional indexes are required.
- No additional table is required.
- No object storage is required.
- No schema-level permission change is required.

## Impact on Layers

### Domain

- Project gains icon metadata.
- A `ProjectIconKey` value object is preferred when implementing the domain model.
- Icon choice must not affect project access, status transitions, or ownership.

### Application

- CreateProjectCommand should accept optional icon key.
- CreateProjectHandler should apply the default icon when omitted.
- Project read results should include icon key.
- Validation errors for invalid icon keys should follow the existing API error strategy.

### Infrastructure

- `projects.projects` table includes `icon_key`.
- Doctrine ProjectEntity maps `icon_key` as a scalar string.
- No Doctrine association is required.
- No index is required for MVP.

### Interfaces / UI

- API uses `iconKey`.
- Frontend maps `iconKey` to an approved icon component.
- Frontend must handle unknown icon keys defensively, usually by falling back to `folder`.

## Impact on DDD

The project icon is project metadata, not a separate aggregate.

It belongs inside the Project aggregate for the MVP because it changes with project profile/display data and has no independent lifecycle.

If project icons later become uploaded assets with ownership, storage, moderation, or reuse rules, they may become a separate concept through a future ADR.

## Impact on EDA

No new event is required only because `icon_key` exists.

If project updates later emit domain events, icon changes may be part of a general `ProjectUpdated` event or a more specific event only if product behavior needs it.

## Impact on CQRS

Project list/read models should include `iconKey` so project lists can render icons without extra queries.

No search index impact is required for MVP.

Do not filter or rank projects by icon unless a real product requirement appears.

## Symfony Implementation Direction

Expected implementation changes:

```text
src/Boardly/Projects/Domain/ProjectIconKey.php
src/Boardly/Projects/Application/CreateProject/CreateProjectCommand.php
src/Boardly/Projects/Application/CreateProject/CreateProjectResult.php
src/Boardly/Projects/Application/ListProjects/ListProjectsResult.php
src/Boardly/Projects/Application/GetProject/GetProjectResult.php
src/Boardly/Projects/Infrastructure/Persistence/Doctrine/Entity/ProjectEntity.php
migrations/<version>.php
OpenAPI project schemas
```

Validation should be implemented in the request DTO/application/domain path and backed by the database CHECK constraint.

## Risks

- Persisting frontend component names instead of stable icon keys.
- Allowing arbitrary strings without validation.
- Adding an index on `icon_key` without a query need.
- Treating icon choice as authorization or business workflow data.
- Introducing file upload/storage complexity too early.
- Breaking API casing by exposing `icon_key` instead of `iconKey`.

## Mitigations

- Use stable UI-library-independent icon keys.
- Add domain/application validation.
- Add database CHECK constraint.
- Use a default icon key.
- Keep frontend mapping defensive with a fallback icon.
- Document allowed icon keys in OpenAPI or frontend constants.
- Defer uploaded/custom icons to a future ADR.

## Migration / Adoption Plan

1. Update Projects persistence design to include `icon_key`.
2. Add `icon_key` to the first Projects migration if the table is not created yet.
3. If the table already exists locally, add a follow-up migration with default `'folder'`.
4. Add `ProjectIconKey` validation in the Projects domain/application layer.
5. Add optional `iconKey` to create project request DTO.
6. Add `iconKey` to project response DTOs and OpenAPI docs.
7. Add frontend icon registry mapping from key to icon component.
8. Use `folder` fallback for missing or unknown icon keys.
9. Do not implement uploaded custom icons in the MVP.

## Open Questions

- What is the initial approved icon key list?
- Should icon keys be validated against a hard allow-list in backend code or only by format in the first MVP?
- Should project icon changes be supported in the first Projects slice or only during project creation?
- Should archived projects keep their icon editable later?

## References

- Related ADRs:
  - `docs/adr/0008-use-layered-project-access-control-with-future-database-protection.md`
  - `docs/adr/0009-use-postgresql-schemas-for-bounded-context-and-infrastructure-boundaries.md`
- Related issues:
  - `#55 Phase 5: Start core product domain with Projects MVP`
- Related PRs: none

## Review Checklist

- [x] Decision is clear and testable.
- [x] Business context is explained.
- [x] Alternatives are documented.
- [x] Trade-offs are explicit.
- [x] Layer impact is described.
- [x] DDD impact is described.
- [x] EDA impact is described if events are involved.
- [x] CQRS impact is described if commands/queries/read models are involved.
- [x] Symfony implementation is treated as implementation detail.
- [x] Risks and mitigations are documented.
