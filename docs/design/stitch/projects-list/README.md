# Stitch Reference: Projects List

## Retrieval status

- Source: Stitch MCP server `stitch`
- Project ID: `6711793596249838566`
- Screen ID: `db4fc82ad077451daab0feabc1138197`
- Title: `Projects List`
- Device: `DESKTOP`
- Retrieved successfully with `get_screen`
- Full project screen inventory retrieved successfully with `list_screens`

## Artifact handling

- The Stitch screen response included hosted HTML and screenshot download URLs.
- The URLs were not persisted to the repository because they are signed hosted links and should not be committed.
- Direct download from this sandbox to the Google-hosted URLs was blocked, so no local screenshot or HTML export was written here.

## Project screen inventory

| Screen title | Screen ID | Device | Height |
| --- | --- | --- | --- |
| Projects List | `db4fc82ad077451daab0feabc1138197` | DESKTOP | `2048` |
| Create Project | `2ff34200ec4543bfbe4ed31fa03e4299` | DESKTOP | `2152` |
| Projects List - Loading Skeleton | `5025e18e7aae48419b4a6369c375c65a` | DESKTOP | `2048` |
| Project Details - Not Found State | `fe413d9c0ba440e9a92d5082cdd52e8a` | DESKTOP | `2048` |
| Create Project - Submitting State | `7ee2c4ede71641b6bb5f4116bbc02255` | DESKTOP | `2050` |
| Project Details - Delete Confirmation | `a05d0b27696c4ca58cc746cb75efd074` | DESKTOP | `2048` |
| Project Details | `071fb6dd52bb499faabe247db60dd15a` | DESKTOP | `2048` |
| Project Details - Archive Confirmation | `0c1a4164223f4944ac3ded69492f52b8` | DESKTOP | `2048` |
| Create Project - Validation Errors | `bb5f85c45663470699e28053802b3600` | DESKTOP | `2270` |
| Projects List - Empty State | `234610550a3c43988780aaaedf7da0a5` | DESKTOP | `2048` |

## Boardly layout map

This reference should be translated into the Boardly projects screen as a dense list surface with a strong header and per-project action affordances.

### Page shell

- Keep the page inside the existing protected workspace shell.
- Use a compact but prominent page header.
- Keep the primary action aligned with the header instead of floating it inside the list.
- Preserve the project count or workspace context as a lightweight secondary signal.

### Content hierarchy

- Use one dominant content surface for the list rather than many disconnected tiles.
- Keep each project visually grouped as a row or card with clear left-to-right scanning.
- Emphasize project name first, then metadata, then status and actions.
- Keep the destructive actions visually secondary to the project identity.

### Per-item chrome

- Show the project icon key as a small identity token near the project name.
- Keep the project name as the strongest clickable element.
- Keep the created timestamp as supporting metadata.
- Keep the status badge visible but not louder than the name.
- Place archive and delete actions together and keep them easy to reach without overwhelming the row.

### Confirmation and errors

- Keep the confirmation affordance nested with the selected project row.
- Preserve inline confirmation messaging instead of using a global dialog.
- Preserve inline action errors close to the affected project.
- Keep disabled/loading state obvious while an archive or delete action is running.

### Screen states

- Loading should reuse the same list shell with skeleton rows.
- Empty should use a centered card-style empty state with a clear create action.
- Error should use the same empty-state frame with retry affordance.
- Ready should remain a stacked list of project items with row-level actions.

### Responsive behavior

- On desktop, use a header/content rhythm with the primary action and count visible together.
- On narrow widths, let header actions wrap cleanly.
- Let project actions stack under the item identity when horizontal space is constrained.
- Avoid horizontal scrolling in the list.
