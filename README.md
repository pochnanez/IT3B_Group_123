# Sun Son Solar Web System

Team: Team_123

Sun Son Solar is a solar-energy company in Pasig City founded by Katherine Sinagaraw (Kat) and Santino Sinagaraw (Sonny). This existing CodeIgniter project now combines its public website with customer, administrator, HR, dispatcher, technician and general employee workspaces.

The original yellow/cyan/blue palette, two approved dark colors, company artwork and registration routes are preserved.

## Repository integration

This workspace is connected to [pochnanez/IT3B_Group_123](https://github.com/pochnanez/IT3B_Group_123). The original `main` branch was retrieved on 21 October 2026 at commit `9970f2d` (`Add files via upload`), retaining its existing history. The CodeIgniter application and documentation are submitted for review on a separate feature branch.

The upstream `main` contains the original standalone `Registration.html`, `Admin Regis.html`, `R_Style.css`, `R_Script.js`, logo/background images, database screenshots, `sunsonsolar.sql` and the original VS Code launcher. All were included without replacing the local `app/`, `public/`, tests, tools, configuration or `.env`. The original static registration uses the root CSS/JavaScript and images. The static admin page is a browser-only prototype; the working application uses `/login` and the protected `/admin/register` route. The root SQL file was retained as a legacy schema reference and was not imported into the existing database.

For the current system, requests enter through `public/index.php`, then `app/Config/Routes.php`, controllers and PHP views. Active web assets live under `public/css`, `public/js` and `public/images`. To view the unchanged standalone prototype instead, open the root `Registration.html` in a browser or use its existing VS Code launch configuration. A static HTML preview does not run the CodeIgniter backend.

## Run locally

Requirements: PHP 8.2+, Composer, MySQL/MariaDB, and PHP `intl`, `mbstring`, `mysqli` extensions. SQLite3 is needed only for automated tests and the isolated preview. XAMPP is supported.

1. Run `composer install` if dependencies are missing.
2. Keep the current `.env` for an existing installation. For a fresh installation, copy `env` to `.env` and privately configure `database.default.*` and `app.baseURL`. Do not commit credentials.
3. Run `php spark migrate`. The additive migration creates the management tables and registers existing account identities. Fresh databases also receive the three profile tables. It does not reset existing profiles or passwords.
4. Run `php spark serve --host localhost --port 8080`.
5. Open **http://localhost:8080/** or **http://localhost:8080/login**. Match `app.baseURL` to the host and port you actually use.

The application uses Philippine time (`Asia/Manila`, UTC+8). For deployment, use production configuration, HTTPS, secure cookies, and a web root pointing at `public/`. Keep `writable/` writable by PHP and outside the public document root. Use persistent shared cache when running multiple application instances: rate limiting depends on the cache.

## Roles and navigation

Sign in with an existing account's email or username and password. `/account` and `/dashboard` display the signed-in user's role-specific workspace.

The customer landing screen is titled **Homepage**, with a matching navigation label. Its design uses the original palette, a custom solar illustration, service overview cards, the next scheduled visit, recent requests and working account shortcuts. [Desktop preview](docs/screenshots/customer-homepage-desktop.png) / [mobile preview](docs/screenshots/customer-homepage-mobile.png) use isolated test data. Other staff dashboard titles and permissions are unchanged.

| Role | Features |
| --- | --- |
| Customer | Request/cancel/reschedule services before work begins; track status and completed reports; notifications and scheduled-visit reminders; profile/password changes; payment records and printable acknowledgments; support concerns |
| Administrator | Manage accounts and access, employee roles, departments, bookings, schedules, technicians, product/service pricing and availability, announcements, customer concerns, received payments, company contacts, filtered charts, attendance, service reports, equipment issues and audit records |
| HR | Create employees, edit employee information/departments, publish general announcements and safety reminders |
| Dispatcher | Review bookings, schedule visits, assign technicians, cancel/reschedule pending work and monitor the shared schedule |
| Technician | View assigned jobs and necessary site/customer details; GPS or explicitly explained manual check-in; optional check-out; service-completion reports; equipment issue reports; announcements |
| Other employee | Own profile/password, notifications, general employee dashboard, company announcements and safety reminders |

Public signup creates **customers only**. `/employees/create` requires admin/HR permission. `/admin/register` requires an administrator and has no department or public role selector. Department membership never grants permissions.

To authorize staff, an administrator opens **Users & employees**, edits an employee and selects `employee`, `hr`, `dispatcher` or `technician`. The department is stored separately. Existing employees remain ordinary employees unless an explicit trusted role was assigned. The old `AccountAccess::$hrEmployeeIds` list is only for initial migration/bootstrap; after migration, role changes belong in the protected administrator screen.

Accounts are deactivated rather than deleted. Deactivated users cannot sign in, and their existing sessions lose access. An administrator cannot deactivate their own account. Password changes invalidate previous sessions and sign the user out. Sessions also have a 30-minute idle limit and two-hour absolute limit.

## Booking workflow

1. A customer opens **Request a service**, selects an available service, preferred future date/time, address and optional notes. A request does not guarantee a schedule.
2. An admin/dispatcher reviews the booking, confirms a future visit and chooses an active technician. Each visit reserves a two-hour slot; overlapping assignments are rejected.
3. The customer and assigned technician receive in-app updates. The scheduled visit remains visible on their dashboards as a reminder.
4. On the assigned job, the technician presses **Check in with GPS**. The browser requests location permission at that moment. If denied/unavailable, the technician can provide a manual exception reason. No coordinates are fabricated.
5. Check-in changes the booking to `in_progress`. A completion report changes it to `completed`. Optional check-out records the end of the visit. Equipment issues are recorded separately and can be resolved by an administrator.

Customers and authorized scheduling staff can cancel or request rescheduling while a booking is `requested` or `scheduled`. Rescheduling cancels the current assignment and returns the request for review. Once work begins, those actions are blocked. Assignment changes and operational actions produce audit entries.

Customers can access only their own bookings, reports, receipts, concerns and notifications. Technicians can act only on their own assignments. Authorization is enforced by route filters and record-level checks, including fresh role/account status checks.

## Catalog, contacts, support and payments

The homepage reads published products and available services from the database. Until actual products are entered, it clearly labels the existing product category guide. No inventory, prices or stock were fabricated. The seven documented service categories are seeded without prices. Administrators can edit prices, availability and stock through the catalog screens.

Official phone/email/office details can be published through **System settings**. Blank details stay unpublished. Testimonials stay hidden until genuine approved reviews are provided. No unsupported awards, project counts, customer quotes or policy links were invented.

Customer support messages are saved as tickets. Administrator responses and status updates appear to their owners and generate in-app notifications. Announcements/safety reminders are published by HR/admin for staff.

Payment forms record **payments actually received** and require a unique reference. They do not charge cards or transfer funds. Customers can view and print/save the corresponding acknowledgment. These acknowledgments are not tax invoices. Notifications and reminders are in-app; email/SMS delivery and payment gateways are not configured.

## Database and preservation

Exact migration: [ManagementFoundation](app/Database/Migrations/2026-10-20-150000_ManagementFoundation.php). Design: [schema plan](docs/revision-schema-plan.md).

Existing `customers`, `employees` and `admins` retain profile data and password hashes. `users` is the account/access registry: unique `(account_type, legacy_id)`, role, department, active status and session version. Credentials are not duplicated into a second login table. Polymorphic legacy references are validated by the account service; operational tables use foreign keys to the registry.

| Tables | Purpose / relationship |
| --- | --- |
| roles, departments, users | Trusted role and department links; shared operational identity |
| products, services | Published catalog, nullable prices and availability |
| bookings | Customer user and service references; preferred/confirmed times and status |
| technician_assignments | One current assignment per booking; technician and assigning staff references |
| attendance_logs, service_reports, equipment_issues | Assignment/technician references; server times, coordinates/manual exception, completion and issue records |
| notifications, announcements | Owned updates and staff-authored company notices |
| payments | Booking, recording administrator, amount, unique reference and timestamp |
| support_tickets | Customer ownership, response and responding administrator |
| audit_logs, settings | Operational action history and official company contact configuration |

Migration was applied successfully to the current local MySQL database. Before/after fingerprints confirmed that **4 customer, 2 employee and 2 administrator records were unchanged**, including their stored hashes. The registry contains those 8 existing accounts. No synthetic operational records were inserted into MySQL. Destructive rollback is intentionally blocked; use reviewed forward migrations for later changes.

Application account creation/profile changes use a MySQL advisory lock plus transactions for cross-table duplicate checks. Direct database writers bypass that application lock and must enforce the same uniqueness rule. Existing ambiguous email/usernames fail closed during sign-in and need operator review. Unsupported/plaintext legacy password values are not accepted or silently converted.

## Initial administrator

Existing administrators sign in normally. If a fresh database has no administrator, a local operator can run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File tools/New-InitialAdmin.ps1
```

The process-only policy override does not change the machine policy. The helper prompts for account fields and masked passwords, and passes JSON through standard input to `php spark accounts:initial-admin`. No password file or password command argument is used. The command refuses if any administrator already exists. Use `YYYY-MM-DD` birthdates and an 11-digit Philippine phone number beginning with `09`; middle names are optional.

## Verification

```powershell
php -d extension=sqlite3 vendor/phpunit/phpunit/phpunit --no-coverage --do-not-cache-result
```

Omit `-d extension=sqlite3` if that extension is already enabled in CLI PHP. Latest result: **64 tests, 388 assertions passed**. The tests enforce in-memory SQLite isolation before creating/removing fixtures. Coverage includes prior registration/login/CSRF checks, profile preservation, the full booking workflow, ownership restrictions, conflicting assignments, deactivation and stale roles, GPS-coordinate validation/manual exceptions, session invalidation, support, catalog/settings, payment-reference uniqueness and transaction rollback.

Headless Chrome also passed real form/navigation checks across all six roles, including dispatcher assignment, denied geolocation/manual check-in, report completion, checkout, chart selection, catalog publishing and HR announcements. Dashboard screenshots were checked at desktop/mobile sizes. Public homepage QA passed at 1440, 1024, 768, 390 and 320 pixels, including keyboard FAQs, mobile navigation/focus, no-JavaScript navigation and deliberately missing hero-image handling.

See [revision implementation notes](docs/revision-implementation.md) and [screenshots](docs/screenshots/). Dashboard screenshots visibly identify synthetic test data.

## Repeat the isolated browser preview

In one terminal, start a fresh fixture database and normal browser-session server:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File tools/Start-ManagementPreview.ps1
```

In another terminal:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File tools/Test-ManagementWorkspace.ps1
```

The first script creates a new named SQLite file under `writable/`, selects that database explicitly and runs the preview at `http://127.0.0.1:8088/`. It uses development mode because CodeIgniter's PHPUnit `testing` mode suppresses real browser sessions. Preview identities and test-only credentials are defined in `tools/Prepare-ManagementPreview.php`; never reuse them for real accounts. No test forms use the live MySQL database. Fixture databases and browser profiles are ignored by Git. Run the fixture preparation again before repeating mutating browser tests.

The browser script uses installed Chrome. Supply `-ChromePath` if necessary. It closes its isolated browser at the end. Stop the preview server with Ctrl+C; the startup helper restores its process environment variables.

For the public page, run a normal development server and then:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File tools/Test-LandingPage.ps1 -BaseUrl http://localhost:8080/
```

## Remaining setup and limits

- Administrators must assign real staff roles and publish verified catalog/prices/contact details. Existing employees do not gain privileges from their departments.
- GPS requires browser permission and an available location source; production geolocation requires HTTPS. GPS success was tested with isolated coordinate fixtures; actual device location accuracy still needs field validation.
- Email/SMS delivery, online payment processing, tax invoicing, password recovery, production hosting and external monitoring integrations are not implemented. In-app notifications, recorded payments and equipment-dependent service descriptions do not imply those integrations.
- A visit uses one current technician assignment and a fixed two-hour conflict window. Audit records retain reassignment actions. Custom visit durations/multiple simultaneous technicians would require an additional reviewed business rule.
- Review and merge the application feature branch through GitHub before deploying these changes to production.

Earlier [Phase 1](docs/phase-1-inspection.md), [Phase 2](docs/phase-2-foundation.md) and [Phase 3](docs/phase-3-landing-page.md) notes are historical snapshots; this README and the revision notes describe the current system.

## Latest website updates

The Sun Son Solar website now combines a responsive public landing page with secure customer registration and login, while keeping the original company logo and yellow, cyan and blue palette with the two approved dark tones. The customer dashboard is now called **Homepage** and features a refined welcome area, custom solar illustration, service overview, upcoming visit details, recent requests and helpful account shortcuts. Logo links open the signed-in account Homepage for authenticated users and the public landing page for guests.

The management system now supports customers, administrators, HR staff, dispatchers, technicians and other employees through role-specific workspaces. Customers can request services, manage eligible bookings, receive in-app updates, edit their profiles and passwords, view recorded payments and receipts, and submit support concerns. Authorized staff can manage accounts, catalog information, schedules, technician assignments, announcements, support responses and operational reports; technicians can check in with GPS or a documented manual exception, submit service reports and record equipment issues.

The database was extended through an additive migration that preserved all eight existing account records and password hashes. Security includes server-side ownership and role checks, CSRF protection, password hashing, session invalidation and scheduling-conflict checks. The latest verification passed **64 automated tests with 388 assertions**, together with isolated browser checks and responsive previews. Official contact details and testimonials still require verified company content; external email/SMS delivery and online payment processing remain unconfigured.
