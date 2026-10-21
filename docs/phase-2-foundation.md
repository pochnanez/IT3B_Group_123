# Phase 2: registration and authentication foundation

Completed on 20 October 2026 following approval of the Phase 1 plan. Phase 3 has not started.

## Result

The public registration endpoint now creates customers only. Authorized administrators and explicitly assigned HR employees create employee accounts through `/employees/create`. `/admin/register` is administrator-only and has no department or role selector. Existing admin/customer/employee records are retained. All new passwords are hashed; password confirmation is never persisted or flashed to the session.

Login accepts email or username across all three tables and fails closed when the identifier matches multiple legacy records. Session identity includes account type and primary key, avoiding collisions between numeric IDs in separate tables. Protected requests reload identity and recompute roles, preventing department fields or stale session role claims from granting privileges. Missing/deleted accounts lose access. Authentication rotates the session ID; POST logout clears the session. Idle and absolute session expiry are enforced.

Registration validates dates, allowed genders/departments, contact format, field lengths, password confirmation, and duplicate identifiers. Missing middle names are supported. Inputs are escaped when repopulated, with passwords excluded. Failure feedback is visible, and database failures do not report success or expose database exception messages. Signup and login are rate limited. Forms carry session-based CSRF tokens; private responses cannot be cached. The debug toolbar is disabled because request history can retain credentials.

## Database decisions

No schema changes, migrations, table resets, or live account writes were performed. Existing primary key/model casing was aligned with actual metadata. `App\Libraries\Accounts` centralizes cross-table creation/authentication, validation, and trusted role resolution. Account creation uses MySQL `GET_LOCK`/`RELEASE_LOCK` around a transaction so application writers share one duplicate-check boundary across the three legacy tables. Table-level unique indexes remain the final constraint for duplicates within each table. External writers bypass the application lock; a future shared identity registry would provide schema-enforced cross-table uniqueness.

HR permissions are an explicit trusted integer-ID allowlist in `app/Config/AccountAccess.php`; the list is empty by default. Administration and HR department membership alone grant no special access. This interim design avoids an unreviewed schema redesign. Persistent role administration and audit records belong to later phases.

Initial administrator creation is a local CLI operation, reads JSON from stdin, uses the same validation/locking path, and refuses if any administrator already exists. The PowerShell helper prompts for masked passwords. Existing administrators can create further admins through the protected web form. No initial administrator was created in the live database.

## Files added

- `app/Libraries/Accounts.php`: account rules, creation, hashing, duplicate checks, identity lookup and authentication.
- `app/Config/AccountAccess.php`: trusted HR assignment and session lifetime settings.
- `app/Controllers/Auth.php`: login, logout and authenticated account page.
- `app/Filters/AccountAuth.php`: session and role enforcement.
- `app/Filters/PrivateResponse.php`: no-store headers.
- `app/Commands/CreateInitialAdmin.php` and `tools/New-InitialAdmin.ps1`: local initial-admin provisioning.
- `app/Views/login.php`, `account.php`, and `partials/feedback.php`: account screens and escaped feedback.
- `tests/feature/AccountFlowsTest.php`: isolated registration/authentication/security tests.
- This implementation note.

## Files updated

- `app/Controllers/Registration.php`: public customer signup and protected staff/admin creation.
- `app/Views/registration.php`: reusable account form, safe repopulation, feedback, CSRF, correct stylesheet case, optional middle name, conditional employee-only department selector, working sign-in link.
- `app/Models/CustomerModel.php`, `EmployeeModel.php`: actual field/key casing.
- `app/Config/Routes.php`, `Filters.php`, `Security.php`, `Toolbar.php`: routes, role filters, session CSRF, cache/privacy behavior.
- `public/css/R_Style.css`: account and feedback styles preserving the approved palette and registration layout.
- `README.md`: setup, actual routes, database decisions, role assignment, provisioning, test instructions and limits.

## Verification

- PHP syntax checks passed for application controllers, libraries, filters, commands and views during implementation; the final test suite loads the changed application paths.
- Full PHPUnit suite passed: **37 tests, 202 assertions**, including 32 new account-flow tests and 5 existing starter tests. Command: `php -d extension=sqlite3 vendor/phpunit/phpunit/phpunit --no-coverage --do-not-cache-result`.
- Test fixtures assert `ENVIRONMENT=testing`, SQLite3 and `:memory:` before creating tables; tests never use live MySQL for account mutations.
- HTTP checks: root, registration, login, CSS and logo all returned 200; registration returned `Cache-Control: no-store, private`; account, employee and admin creation pages redirected guests to login; missing-CSRF signup POST returned 403.
- The existing local base URL is `http://localhost:8080/`; smoke testing used a temporary server on port 8087, whose redirects correctly followed the configured base URL. Update local `.env` base URL if running on a different port.
- Read-only MySQL compatibility check acquired and released the same account lock successfully (1/1). Actual MySQL account insertion and concurrent-process writes were not exercised; SQLite flow tests cannot verify MySQL-specific locking behavior under load.
- Browser/mobile layout and screenshots remain unverified. No browser automation was used.
- No commit, push or PR update was performed because this workspace has no Git metadata.

## Next phase

Phase 3 is the public landing page from the second master command. It will reuse the real logo and six approved colors, preserve registration/login, and avoid presenting unimplemented bookings or demonstration catalog content as live operations. The master command requires review before the next major phase.
