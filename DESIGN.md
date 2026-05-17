# Boardly Design Guidelines

Boardly is a Jira-like project and task management system. It is **not** a CRM.

This document defines the frontend UI direction, layout rules, design tokens, component expectations, and required screens for the authenticated Boardly workspace.

## 1. Product identity

Boardly is a professional project management workspace for:

- project management;
- task and issue tracking;
- project dashboards;
- future kanban boards and workflow screens.

The UI must feel like a clean enterprise SaaS product: focused, calm, readable, and implementation-friendly.

## 2. Technology context

Frontend stack:

- Next.js;
- TypeScript;
- App Router;
- Tailwind CSS;
- CSS custom properties for design tokens;
- shadcn/ui-style shared primitives;
- Radix-style accessible primitives when needed.

Backend context:

- Symfony API-first backend;
- backend owns authentication;
- backend owns permissions;
- backend owns workflow rules;
- backend owns project and task state.

Frontend must not make final permission, lifecycle, or workflow decisions by itself. It may hide or disable actions for UX, but backend responses remain authoritative.

Do not expose tokens, secrets, session internals, or technical authentication details in the UI.

## 3. Frontend architecture principles

Use a context-based frontend structure:

```text
app/
  app/
    projects/
      page.tsx
      new/
        page.tsx
      [projectId]/
        page.tsx

shared/
  ui/
    button.tsx
    card.tsx
    input.tsx
    badge.tsx
    dialog.tsx
    skeleton.tsx

contexts/
  projects/
    components/
    api/
    model/
    lib/
```

Architecture rules:

- `app` routes compose screens.
- `shared/ui` contains generic reusable primitives only.
- Product-specific UI lives inside feature/product contexts.
- API calls and backend contracts must be isolated from purely visual components.
- Components should be small, composable, and easy to test.
- Avoid leaking backend implementation details into the UI.

## 4. Visual direction

Primary direction:

- navy-based product identity;
- light theme first;
- dark mode should remain possible through tokens;
- high contrast;
- clear hierarchy;
- soft slate backgrounds;
- white cards;
- muted borders;
- blue accent for primary actions;
- minimal visual noise;
- dense enough for productivity, but not cramped.

Avoid:

- decorative illustrations;
- playful visual language;
- excessive gradients;
- aggressive destructive colors before confirmation;
- CRM screens;
- sales pipeline screens.

## 5. Design tokens

Use semantic CSS custom properties. Do not hardcode visual meaning into component names.

Recommended token set:

```css
:root {
  --background: 210 40% 98%;
  --foreground: 222 47% 11%;

  --card: 0 0% 100%;
  --card-foreground: 222 47% 11%;

  --primary: 222 72% 18%;
  --primary-foreground: 0 0% 100%;

  --secondary: 214 32% 91%;
  --secondary-foreground: 222 47% 11%;

  --muted: 215 28% 94%;
  --muted-foreground: 215 16% 47%;

  --border: 214 32% 88%;
  --input: 214 32% 88%;
  --ring: 217 91% 60%;

  --destructive: 0 72% 51%;
  --destructive-foreground: 0 0% 100%;

  --success: 142 71% 35%;
  --success-foreground: 0 0% 100%;

  --warning: 38 92% 50%;
  --warning-foreground: 222 47% 11%;

  --radius: 0.75rem;
}
```

Token usage rules:

- use `primary` for main navigation, brand, and primary actions;
- use `destructive` only for dangerous actions and confirmed destructive states;
- use `success` for active or positive states;
- use `warning` for archived or pending states;
- use neutral/muted styles for inactive or future functionality.

## 6. Authenticated workspace shell

Authenticated workspace pages live under:

```text
/app
```

The workspace shell must include a sidebar and a main content area.

### 6.1 Left sidebar

Sidebar content:

- Boardly logo/brand;
- workspace selector/card;
- navigation items:
  - Projects;
  - Dashboard;
  - My tasks;
  - Boards;
  - Calendar;
  - Settings;
- inactive/future items should be visibly disabled but readable;
- account card at the bottom;
- logout action at the bottom.

Sidebar rules:

- active route must be clearly highlighted;
- disabled items must not look broken;
- logout must be visually separated from primary navigation;
- spacing should be compact but readable.

### 6.2 Main content area

Main content must include:

- stable top/header area;
- page title;
- page description;
- primary action when relevant;
- content cards;
- safe loading, empty, and error states.

### 6.3 Responsive behavior

Desktop first:

- sidebar visible on desktop;
- sidebar collapses or stacks on smaller screens;
- main content remains readable on tablet/mobile;
- forms should remain single-column on narrow screens;
- primary actions should remain easy to reach.

## 7. Shared component style

### Button

Buttons must look like reusable shared primitives.

Required variants:

- `default` / primary;
- `secondary`;
- `outline`;
- `ghost`;
- `destructive`.

Rules:

- primary action: solid navy or blue;
- destructive action: avoid aggressive styling until confirmation;
- disabled state must be visually clear;
- loading state should prevent duplicate submission.

### Input

Input rules:

- accessible label required;
- helper text when useful;
- error text below field;
- token-based border, ring, and foreground colors;
- no placeholder-only labels.

### Card

Card rules:

- soft border;
- light shadow;
- clear spacing;
- no heavy decoration;
- content hierarchy must be obvious.

### Badge

Badge rules:

- compact;
- readable;
- status-specific but not visually loud.

Status mapping:

| Status | Visual language |
|---|---|
| `active` | success / green |
| `archived` | warning / amber |
| `deleted` | destructive / red |
| unknown | neutral |

### Dialog / confirmation UI

Confirmation UI must be explicit and accessible.

Rules:

- explain the action clearly;
- show the affected project name when available;
- destructive confirmation button should be destructive;
- cancel button should be easy to find;
- archive/delete actions should feel serious, but not aggressive before confirmation.

## 8. Required screens

## 8.1 Projects list page

Route:

```text
/app/projects
```

Purpose:

Allow the authenticated user to browse projects owned by the current workspace.

Page header requirements:

- eyebrow: `Workspace`;
- title: `Projects`;
- description: `Browse the projects owned by the authenticated workspace.`;
- primary action: `Create project`.

Content requirements:

- project count badge;
- loading skeleton concept;
- empty state concept;
- error state concept;
- project cards or list rows.

Project item must include:

- icon key badge;
- project name;
- created date;
- status badge;
- archive action;
- delete action;
- link to details page.

Behavior requirements:

- archive action must use confirmation UI;
- delete action must use confirmation UI;
- project details link navigates to `/app/projects/[projectId]`;
- deleted projects should be visually distinct if shown;
- unknown status must use neutral fallback.

Do not include:

- project editing screen;
- restore archived project flow;
- project members UI;
- permissions UI;
- issue UI;
- board UI.

## 8.2 Create project page

Route:

```text
/app/projects/new
```

Purpose:

Allow the authenticated user to create a new project.

Page header requirements:

- title: `Create project`;
- description explaining that project name is required.

Form requirements:

- form card;
- `Project name` field;
- `Icon key` optional field;
- submit button: `Create project`;
- cancel/back link to projects.

Helper text:

- project name should be recognizable by the team;
- icon key can be blank to use backend default.

Validation and state requirements:

- validation error state;
- server error state;
- loading state on submit;
- disabled submit while submitting.

Backend contract:

```http
POST /api/projects
```

Request body:

```json
{
  "name": "Project name",
  "iconKey": "folder"
}
```

Validation rules:

- `name` is required;
- `name` max length: 100 characters;
- `iconKey` is optional;
- `iconKey` max length: 64 characters;
- `iconKey` pattern: lowercase letter first, then lowercase letters, digits, underscore, or hyphen.

Recommended frontend validation:

```ts
const iconKeyPattern = /^[a-z][a-z0-9_-]*$/;
```

Frontend validation improves UX only. Backend validation remains authoritative.

## 8.3 Project details page

Route:

```text
/app/projects/[projectId]
```

Purpose:

Display read-only project details.

Page header requirements:

- title: `Project details`;
- back button/link to projects.

Detail card must include:

- project name;
- project id;
- icon key;
- status;
- created date;
- updated date;
- archived date if present.

State requirements:

- loading state;
- not found / inaccessible state;
- safe error state.

Required placeholder note:

> Project permissions, editing, and lifecycle changes remain backend-owned and will be exposed through dedicated actions later.

Do not include:

- edit project form;
- restore archived project flow;
- project members;
- permissions management;
- issue list;
- board preview;
- workflow editor.

## 9. Data and API assumptions

Expected project shape:

```ts
type ProjectStatus = 'active' | 'archived' | 'deleted' | string;

type Project = {
  id: string;
  name: string;
  iconKey: string | null;
  status: ProjectStatus;
  createdAt: string;
  updatedAt: string;
  archivedAt?: string | null;
};
```

Frontend rules:

- never assume unknown status is safe;
- show unknown status neutrally;
- handle missing optional fields gracefully;
- do not expose raw backend errors directly to users;
- log technical details only through approved observability patterns.

## 10. State design requirements

Every production screen should define these states:

- default/content state;
- loading state;
- empty state;
- validation error state when forms exist;
- server error state;
- not found/inaccessible state when route params are used;
- disabled/submitting state for mutations;
- confirmation state for dangerous actions.

Error copy should be safe and user-oriented. Avoid exposing stack traces, exception class names, SQL errors, tokens, or internal service names.

## 11. Accessibility requirements

Minimum requirements:

- semantic headings;
- proper label/input association;
- keyboard-accessible actions;
- visible focus states;
- dialog focus management;
- sufficient contrast;
- no color-only status communication;
- buttons must be buttons, links must be links.

## 12. Explicitly out of scope

Do not design or implement these areas from this document:

- project editing screen;
- restore archived project flow;
- project members/permissions UI;
- issue UI;
- board UI;
- workflow editor;
- admin UI;
- CRM screens;
- sales pipeline screens.

These areas should be introduced later through dedicated product and backend contracts.

## 13. Implementation checklist

Before implementing a screen:

- [ ] Route matches the documented `/app` structure.
- [ ] Screen uses the authenticated workspace shell.
- [ ] Shared UI primitives are generic.
- [ ] Product-specific components are inside the proper context.
- [ ] Backend remains authoritative for permissions and state.
- [ ] Loading, empty, error, and confirmation states are covered.
- [ ] Form validation mirrors backend constraints but does not replace them.
- [ ] Status badges use semantic status mapping.
- [ ] Destructive actions use confirmation UI.
- [ ] UI remains responsive on tablet/mobile.
- [ ] No CRM or sales-pipeline patterns are introduced.

## 14. Design principle summary

Boardly UI should be:

- clear over decorative;
- structured over playful;
- token-based over hardcoded;
- backend-owned over frontend-assumed;
- reusable over page-specific duplication;
- production-friendly over prototype-only.
