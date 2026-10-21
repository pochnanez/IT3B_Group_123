# Sun Son Solar: Phase 1 inspection

Inspection date: 20 October 2026. Scope: the existing local CodeIgniter project and read-only database metadata. No application behavior, schema, or account records were changed during this inspection.

## Existing structure

- CodeIgniter 4.7.4 is installed in `vendor/`; Composer requires PHP ^8.2 and CodeIgniter ^4.7. The local CLI runs PHP 8.2.12.
- `app/Controllers/` contains `BaseController`, `Home`, and `Registration`.
- `app/Models/` contains `CustomerModel` and `EmployeeModel`.
- `app/Views/registration.php` is the active registration view. `registration.html` is an unused duplicate with a different stylesheet reference. There is no shared application layout.
- `/` renders the default CodeIgniter welcome view. `/register` renders registration; `POST /register/save` processes it. Auto-routing is disabled.
- `/admin/register`, login, logout, booking, product, service, and dashboard routes are absent.
- The existing assets are `public/images/logo.png` and `sssbg.png`. The logo was visually inspected and should be reused. No separate product photography or verified testimonials/contact details were found in project documentation.
- Registration uses `public/css/R_Style.css` and `public/js/R_Script.js`. The view references `R_style.css`, which differs in case from the actual filename and can fail on Linux.
- The existing four brand colors remain #FFF9D0, #CAF4FF, #A0DEFF, and #5AB2FF. Added dark colors are #17324D and #34546F. Preserve these choices in later work; use the navy for small text on bright blue.
- README explains the project and registration routing by department but lacks installation, migrations, authentication, and testing instructions.

## Registration and authentication

Implemented in source:

- Server validation of names, email, Philippine mobile format, required fields, minimum username/password lengths, and password confirmation.
- Password hashing with `password_hash(PASSWORD_DEFAULT)`; confirmation passwords are not part of stored model fields.
- `N/A` selects the customer table; any other submitted department selects employees.
- Client password visibility toggles and password length/confirmation checks.

Problems to address before expansion:

1. The public form permits employee account creation, and the server only requires a nonempty department. It does not enforce the documented department list or restrict employee creation to authorized staff. Department must never confer privileged access.
2. CSRF protection is disabled, and the form has no CSRF field.
3. There are no application duplicate checks, including checks across the three account tables. Database uniqueness only applies within each table.
4. Insert results are ignored before reporting success; database rejection can produce an exception or incorrect success handling.
5. The controller sets flash errors/success and old input, but the view does not render them or repopulate safe fields. Passwords should be excluded from flashed old input.
6. Birthdate and gender lack strict server validation; department lacks an allowlist. Middle name is required in the browser but optional in the schema and not validated on the server. The view duplicates `departmentHelp` IDs.
7. No password verification, login/logout, authenticated session flow, or role authorization is implemented. The sign-in link is `#`.
8. Admin records exist, but no admin controller/model/view or creation route exists in this checkout. Do not expose unrestricted admin registration when adding provisioning.

## Actual database

The existing environment configuration successfully connects to MySQL. Credentials were not printed. Metadata queries found exactly three tables:

| Table | Actual primary key | Other fields | Indexes |
| --- | --- | --- | --- |
| admins | admin_id | first_name, middle_name, last_name, birthdate, gender, email, contact_number, address, username, password | Primary key; unique email; unique username |
| customers | Customer_id | First_Name, Middle_Name, Last_Name, Birthdate, Gender, Email, Contact_Number, Address, Username, Password | Primary key; unique Email; unique Username |
| employees | Employee_Id | First_Name, Middle_Name, Last_Name, Birthdate, Gender, Email, Contact_Number, Address, Department, Username, Password | Primary key; unique Email; unique Username |

Middle names are nullable; the other listed fields are not. Gender is an enum; employee Department is an enum. No foreign keys were returned. No account contents or stored password values were read, so existing password formats and cross-table duplicates remain unverified.

There is no shared users table, departments table, roles table, or any of the documented operational tables. Application migrations and seed directories contain only placeholders. No SQL scripts were found. Model field/key casing differs from actual customer/employee metadata; normalize model mapping against the real schema before relying on fetched array keys and account IDs.

Proposed foundation: retain the three current account tables initially, use an explicit account type plus primary key for session identity, and reject new duplicate emails/usernames across all three. Check existing duplicates before choosing how ambiguous legacy logins are resolved. Application-only duplicate checks cannot guarantee cross-table uniqueness during concurrent writes; a shared identity registry with database constraints is the stronger future option. Any identity-table migration needs a separate schema proposal and a preservation/backfill strategy before implementation. Employee authorization requires explicit trusted role assignments, not submitted departments.

## Verification and limits

- `php spark routes` succeeded and confirmed the three application routes above.
- Read-only MySQL metadata inspection succeeded.
- The default PHPUnit run had two errors because the configured SQLite3 test driver extension is not enabled in CLI PHP.
- `php -d extension=sqlite3 vendor/phpunit/phpunit/phpunit --no-coverage --do-not-cache-result` passed: 5 tests, 7 assertions. This enables the already installed extension for that process only.
- These are starter health/session/example-database tests, not registration or authorization coverage. No live registration submissions or browser/responsive checks were performed in this phase.
- No `.git` metadata exists in this folder or its parent Git context. Git status, branch, and remote commands fail with “not a git repository.” GitHub CLI is unavailable. The existing remote/PR cannot be identified from this checkout. Do not initialize a replacement repository or claim commits/pushes.
- `.gitignore` excludes `.env`, vendor, and writable runtime files, but tracked status cannot be checked without Git metadata.

## Prioritized implementation plan

1. **Foundation / Phase 2:** preserve existing records; fix schema field mapping, validation, feedback, duplicate checks, and insert failure handling. Enable CSRF. Restrict public signup to customers and move employee creation behind authorization as an intentional, documented change to the legacy department-based form. Add login/logout, session identity, and server authorization with explicit trusted roles. Provide protected initial administrator setup rather than public administrator registration. Add meaningful registration/authentication tests against isolated data. Present exact migrations before schema changes.
2. **Public website / Phase 3:** replace the default homepage with the requested landing sections using the real logo and approved six-color palette. Keep CodeIgniter and the existing registration route. Add responsive navigation and accessible FAQs. Use verified company facts only; hide unavailable testimonials and clearly distinguish category demonstrations from real inventory. Booking/login buttons must follow implemented routes. Test desktop/mobile behavior and registration regressions.
3. **Customer features / Phase 4:** add owned profiles, bookings, status history, notifications, service/payment history, and support, using migrations and ownership checks.
4. **Administration and HR / Phase 5:** add protected account/role management, employee creation, departments, catalog management, announcements, and settings.
5. **Dispatch and technicians / Phase 6:** add assignments, conflict checking, technician job access, permission-based GPS attendance, reports, and equipment issues.
6. **Reports / Phase 7:** add filtered reporting, notifications, and audit records from actual operational data.
7. **Validation and documentation / Phase 8:** complete workflow/security/regression checks, responsive screenshots, setup documentation, migration instructions, and honest limitations.
8. **GitHub / Phase 9:** recover the existing repository/branch context, inspect the intended PR, then commit and push verified changes and update that PR. Do not merge.

The master command explicitly says to start with Phase 1 only and have its plan reviewed before Phase 2. This report completes that inspection; later phases remain pending review.
