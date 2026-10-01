# Admin database setup

The configured database has an `admins` table without `department` or `created_at`.
The AdminModel has no automatic timestamp handling. Administrator records,
customers, and employees were preserved.

The admin migration file and the database `migrations` history table were removed
as requested. Future migration commands may recreate that history table; this
feature uses direct SQL setup instead.

For a new MySQL/MariaDB database, import `database/admins.sql`. It creates the admin
table with unique email and username indexes, nullable middle name, fixed gender
choices, and a 255-character password hash field. It does not modify an existing
admin table. Confirm Password is never stored.

The following changes have already been applied to the configured database:

```sql
ALTER TABLE admins DROP COLUMN created_at;
DROP TABLE migrations;
```

Do not rerun these statements against the updated database. For another existing
database, inspect its schema first and remove only columns/tables that exist.

Open `http://localhost:8080/index.php/admin/register` to create accounts. Start the
local server with `php spark serve` if it is not running. The root HTML page opens
this PHP form automatically, so submission has a session and CSRF token. If the
application address changes, update its `data-register-url` and form action in
`Admin Registration.html` to match the configured base URL.
The routes remain GET `/admin/register` and POST
`/admin/register/save`, both with CSRF protection. Apply your real administrator
authorization filter before public deployment.

Configure the database connection and base URL in `.env`, and use `public/` as the
web server document root. Normal registration still requires Department: N/A
selects customers, other departments select employees.

The feature tests use a separate test-only schema helper, not a migration:

```powershell
php -d extension=sqlite3 vendor/phpunit/phpunit/phpunit --no-coverage tests/feature
```
