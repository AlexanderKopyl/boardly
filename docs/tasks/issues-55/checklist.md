# Implementation Checklist: Fix final Projects review nits

- [x] Make the migration `down()` drop project indexes with schema-qualified names.
- [x] Align the default project list ordering with the `created_at DESC` index and verify the repository integration test expectation.
