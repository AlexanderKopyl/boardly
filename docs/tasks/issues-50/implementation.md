## 2026-05-14 - Task: Step 1 Baseline Check

### Subagents used

None.

### Skills used

`task-implementation`

### Files changed

- `docs/tasks/issues-50/checklist.md`
- `docs/tasks/issues-50/implementation.md`

### Summary

Confirmed the working tree baseline and ran the focused IdentityAccess refresh-controller test before any code changes.

The repository shell startup tries to call a missing Xcode command line tools path, so I used absolute tool paths to get a real baseline result.

### Verification

- `/opt/homebrew/bin/git status --short`
- `/opt/homebrew/bin/php ./vendor/bin/phpunit tests/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RefreshAuthenticationControllerTest.php`

Result:

- Working tree was clean.
- `RefreshAuthenticationControllerTest::testReplacedTokenReuseReturns401ClearsCookieAndRevokesFamily` still fails with `Failed asserting that 1 is identical to 2.`

### Risks / follow-up

- The known unrelated refresh-token baseline failure remains present and does not block this issue.
- Shell startup still references a missing Xcode Command Line Tools path; direct absolute commands avoid the problem for now.
