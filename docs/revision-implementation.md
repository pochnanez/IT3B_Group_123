# Integrated management revision

Implemented locally on 20 October 2026 from the supplied revision prompt. This extends the existing project and its approved visual design; no replacement project, profile-table reset or Git repository was created.

## Delivered behavior

The public website now reflects actual database catalog availability and links to the authenticated booking form. The company founders from the revised brief are included. Product category guides remain labeled until real products are published. Verified company contact details can be set by an administrator; unsupported testimonials remain hidden.

Every role has a dashboard and server-restricted navigation. Customers can submit, cancel and request rescheduling of pending bookings, track service history, view notifications and reminders, edit their profiles/passwords, access recorded payments and printable acknowledgments, and submit support concerns. Admin/HR can create and edit employee information; only administrators grant roles or deactivate accounts. Department assignment and permission assignment remain separate.

Admin/dispatch scheduling checks technician authorization and overlapping two-hour visits. Technicians see only their assigned work, record check-in with browser GPS permission or a stated manual exception, record optional checkout, report equipment issues and submit completion reports. Reports require a prior check-in and transition the booking to completed. Admin/HR announcements and safety reminders appear on staff dashboards. Admins can respond to concerns, record actual received payments, resolve equipment issues, publish catalog information and view filtered operational charts, attendance, reports and audit entries.

Operations notify affected users in-app. Customer resources enforce ownership, technician writes enforce assignment ownership, and protected requests reload current account status/role. Deactivation blocks existing sessions. Password changes increment a session version so all previously issued sessions lose access. Operational writes use transactions and a shared MySQL advisory lock; duplicate identifiers and payment references are rejected. CSRF, login/signup throttling, escaped output and no-store responses remain enabled.

## Database application and evidence

The exact schema is in [the additive migration](../app/Database/Migrations/2026-10-20-150000_ManagementFoundation.php), with relationships and preservation decisions in [the schema plan](revision-schema-plan.md). `php spark migrate` completed against the existing MySQL database; `php spark migrate:status` confirmed version `2026-10-20-150000`, batch 1.

The existing customer, employee and admin profiles were fingerprinted before/after migration. All original records matched exactly: **4 customers, 2 employees, 2 admins**, including their original password fields. The migration only added account/access identities and the new tables; profile credentials were not copied or replaced. It seeded eight departments, six roles and the seven documented service categories without prices. It inserted no fake MySQL products, stock, bookings, attendance, reports, payments, announcements or support messages.

The `users` registry links the three legacy profile tables through a unique account-type/legacy-ID pair. This polymorphic legacy reference is checked by the account service. Ordinary foreign keys connect roles/departments and all operational owners/records. Rollback is intentionally blocked because deleting these tables would destroy management records. A fresh installation can also create the legacy profile tables through the same migration.

## Important routes

| Route | Function |
| --- | --- |
| `/`, `/register`, `/login` | Public website and preserved customer authentication |
| `/account`, `/dashboard` | Role-specific dashboard |
| `/bookings/new`, `/bookings`, `/bookings/{id}` | Request, list/history and owned/authorized booking detail |
| `POST /bookings/{id}/change`, `/assign`, `/payment` | Authorized lifecycle, schedule/assignment and received-payment recording |
| `POST /assignments/{id}/attendance`, `/report`, `/issue` | Assigned-technician operations |
| `/profile`, `POST /profile/password` | Own information and password changes |
| `/notifications`, `/support`, `/payments`, `/receipts/{id}` | Owned customer updates, concerns, payments and printable acknowledgment |
| `/users`, `/users/{id}`, `/employees/create`, `/admin/register` | Protected account/staff provisioning and management |
| `/announcements`, `/manage/products`, `/manage/services` | Staff communications and catalog management |
| `/reports`, `/records`, `/settings` | Admin charts, attendance/reports/equipment/audit and official contacts |

## Files

New implementation files:

- `app/Database/Migrations/2026-10-20-150000_ManagementFoundation.php`
- `app/Models/IdentityRegistry.php`
- `app/Libraries/Management.php`
- `app/Controllers/Workspace.php`
- `app/Views/layouts/workspace.php` and `app/Views/workspace/*.php`
- `public/css/workspace.css`, `public/js/workspace.js`
- `tests/feature/ManagementWorkflowTest.php`
- `tools/Prepare-ManagementPreview.php`, `Start-ManagementPreview.ps1`, `Test-ManagementWorkspace.ps1`
- `docs/revision-schema-plan.md`, this note and nine `revision-*.png` screenshots.

Updated integration files:

- `app/Libraries/Accounts.php`, `app/Filters/AccountAuth.php`, `app/Controllers/Auth.php`
- `app/Config/AccountAccess.php`, `App.php`, `Routes.php`
- `app/Controllers/Home.php`, `app/Views/home.php`
- `tools/Test-LandingPage.ps1`, `.gitignore`, `README.md`

The registration PHP/HTML views, registration CSS/JavaScript and the existing visual palette were preserved in this revision. Staff provisioning continues to use the existing protected registration forms.

## Verification

**Automated:** `php -d extension=sqlite3 vendor/phpunit/phpunit/phpunit --no-coverage --do-not-cache-result` passed **64 tests, 388 assertions**. The isolated management tests cover migrations/fresh reference data and original-profile preservation, the complete service workflow, resource ownership, customer role/owner tampering, cross-technician denial, assignment conflicts, cancellation/rescheduling state rules, manual/GPS validation, report-before-check-in rejection, deactivation, role-specific page rendering, HR/admin differences, support responses, profile/password changes and session invalidation, catalog publishing, unavailable services/past times, duplicate payments, settings escaping, invalid report dates, and transaction rollback. Existing registration/authentication and landing tests also passed.

**Browser:** Real forms were exercised in an explicitly selected separate SQLite database containing labeled synthetic data, using normal browser sessions rather than PHPUnit's session-suppressing testing environment. Headless Chrome passed all six dashboards, desktop/mobile overflow checks, customer booking/receipt access, admin product creation, report-series selection, dispatcher assignment, browser geolocation denial/manual fallback, technician check-in/report completion/check-out, and HR announcement delivery to general employees. No browser runtime/resource errors were reported. This does not validate physical GPS accuracy or external services.

The MySQL-backed homepage passed checks at 1440/1024/768/390/320 pixels, including mobile open/Escape/focus/link behavior, native keyboard FAQs, details expansion, no-JavaScript fallbacks, image loading and a deliberately blocked hero image. Public screenshots were refreshed after booking activation.

No production account password was requested or used for QA. No synthetic operational data was inserted into MySQL. The normal and synthetic QA servers were temporary. Syntax checks passed for new PHP views/controllers, the migration and browser helper scripts during implementation.

## Screenshots

All dashboard screenshots visibly say **TEST ENVIRONMENT - Synthetic records for verification only**.

- [Customer desktop](screenshots/revision-customer-desktop.png) / [mobile](screenshots/revision-customer-mobile.png)
- [Administrator dashboard](screenshots/revision-admin-desktop.png)
- [Filtered reports](screenshots/revision-reports-desktop.png)
- [User management mobile](screenshots/revision-users-mobile.png)
- [Dispatcher dashboard](screenshots/revision-dispatcher-desktop.png)
- [Technician booking and attendance](screenshots/revision-technician-desktop.png)
- [HR dashboard](screenshots/revision-hr-desktop.png)
- [General employee mobile](screenshots/revision-employee-mobile.png)
- [Updated public desktop](screenshots/home-desktop.png) / [mobile](screenshots/home-mobile.png)

## Setup still requiring company input

An existing administrator must assign trusted employee roles and publish genuine catalog/pricing/contact information. No role was automatically granted based on a department. Existing unsupported/ambiguous credentials need operator review rather than silent migration. Verified testimonials and policy text have not been supplied.

The implementation provides in-app notifications/reminders and manually recorded payment acknowledgments. Email/SMS delivery, online payment gateways, tax invoicing, password recovery and real equipment-monitoring integrations are not implemented. GPS needs permission and HTTPS outside localhost, with actual device/site validation still required. Visits currently reserve a fixed two-hour slot and one current technician; different duration/team rules need a subsequent business-rule change.

There is no Git metadata in this workspace. Branch/commit/push/PR status could not be verified; no commit, push, PR update, merge or external deployment was performed. The original repository checkout must be recovered for publication.
