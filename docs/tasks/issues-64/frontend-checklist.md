# Issue 64 Checklist

- [x] Translate the Stitch Projects List reference into a Boardly-specific layout map for the projects screen.
- [ ] Recompose `ProjectsListPage` to match the reference hierarchy while preserving current project behavior.
- [ ] Restyle loading, empty, error, and ready states without changing their data flow or copy semantics.
- [ ] Preserve create/detail navigation and archive/delete confirmation behavior, including loading guards and inline action errors.
- [ ] Make only minimal generic shared-primitive adjustments if they are required by more than the Projects screen.
- [ ] Verify responsive behavior, keyboard accessibility, and manual smoke coverage for the redesigned `/app/projects` flow.
