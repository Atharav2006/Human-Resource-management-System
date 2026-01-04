```markdown
# Human Resource Management System (HRMS)

A web-based Human Resource Management System built with PHP. This application provides core HR functionalities including employee management, role-based access (admin/employee), attendance, leave management, payroll, and reporting.

[Optional badges — add when available]
- Build / CI: ![CI](https://img.shields.io/badge/CI-none-lightgrey)
- License: ![License](https://img.shields.io/badge/license-MIT-blue)
- Issues: ![Issues](https://img.shields.io/github/issues/Atharav2006/Human-Resource-management-System)

## Table of contents
- Features
- Demo / Screenshots
- Project map (stylized)
- Technology stack
- Prerequisites
- Installation
- Configuration
- Database Schema
- Usage
- Contributing
- License
- Contact

## ✨ Features
- Role-Based Access Control (Admin vs Employee)
- Employee management (create, view, edit employee profiles)
- Attendance tracking
- Leave requests & approvals
- Payroll and payslip generation
- Administrative dashboard & reporting
- User authentication with registration and password recovery

## Demo / Screenshots
_Add screenshots here (e.g. /assets/images/screenshot-dashboard.png)_

![Dashboard screenshot](assets/images/screenshot-dashboard.png)

## Project map — "HRMS Transit" (stylized)
Instead of a plain tree, this map groups files as transit lines (roles & responsibilities). Emojis make sections easy to scan.

(Hub) Central Station — project root
- 🚉 index.php — Landing / Login (main entrance)
- 🪪 register.php — User registration
- 🔐 logout.php — Logout logic

Utilities Line — system helpers
- ⚙️ config/
  - 🔑 db.php — DB connection (update credentials)
  - 🛡 auth.php — Session & auth helpers
  - 📦 env.example — (optional) environment example

Frontend Line — UI & assets
- 🎨 assets/
  - 💅 css/ — Stylesheets (Bootstrap, custom)
  - ⚡ js/ — Frontend scripts
  - 🖼 images/ — Static images, screenshots

Data Depot — schema & storage
- 🗂 db/
  - 📄 schema.sql — Dayflow HRMS DB schema (CREATE & seeds)
- 📤 uploads/
  - 🧑‍🎨 profile_pictures/
  - 📁 documents/

Admin Branch — back office operations
- 🏢 admin/
  - 🧭 dashboard.php — Admin overview
  - 👥 employees.php — Manage employees
  - ⚖ payroll.php — Payroll processing
  - 🗂 leave_requests.php — Process leave requests

Employee Branch — self-service
- 👩‍💼 employee/
  - 🧾 dashboard.php — Employee overview
  - 🙍‍♂️ profile.php — View / edit profile
  - 💳 payslips.php — Payslip history

Shared Stations — included partials & actions
- 🧩 includes/
  - 🔼 header.php
  - 🔽 footer.php
- 🚀 actions/
  - 🔐 login_action.php
  - 🧾 register_action.php
- 📊 reports/
  - 📅 attendance_report.php

Repo Essentials
- ☠️ .gitignore
- 📘 README.md (this file)
- 🧭 CONTRIBUTING.md (optional)
- 🧾 LICENSE (add if desired)

Quick tips:
- Use the Data Depot (`db/schema.sql`) to recreate the DB quickly.
- Emojis indicate function: 🚉 (entry points), 🗂 (data), 🏢/👩‍💼 (role-specific).

---

## 🛠️ Technology Stack
- Backend: PHP (7.4+) — specify your required version
- Frontend: HTML, CSS, JavaScript (Bootstrap)
- Database: MySQL / MariaDB

## Prerequisites
- PHP 7.4+ (or your chosen version)
- Enabled PHP extensions: mysqli (or PDO), openssl, mbstring, fileinfo
- MySQL or MariaDB
- Web server (Apache / Nginx). XAMPP/WAMP/LAMP for local dev
- Optional: Composer (for future PHP packages)

## Installation
1. Clone the repository:
   ```bash
   git clone https://github.com/Atharav2006/Human-Resource-management-System.git
   ```
2. Move the project to your web server root (e.g., `htdocs` for XAMPP).
3. Create/import the database (schema is in `db/schema.sql`):
   ```bash
   mysql -u root -p dayflow_hrms < db/schema.sql
   ```
4. Configure DB credentials:
   - Edit `config/db.php` and set DB_HOST, DB_USER, DB_PASS, DB_NAME.
5. Ensure `uploads/` and subfolders are writable by the web server.
6. Open your browser:
   ```
   http://localhost/Human-Resource-management-System/
   ```

## Configuration
Example `config/db.php`:
```php
<?php
// config/db.php (example)
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'dayflow_hrms';
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
```

For production, prefer environment variables and keep real credentials out of the repo.

## Database Schema (SQL)
The full schema and seed data are in `db/schema.sql` in this repository. It creates tables for users, employees, attendance, leave types and requests, payroll, documents, notifications, and activity logs.

## Default / Test Accounts
Seed an admin user (replace `<HASH>` with a password_hash output from PHP):

```sql
INSERT INTO users (employee_id, email, password, role, is_verified, status)
VALUES ('EMP001','admin@example.com','<HASH>','ADMIN',1,'ACTIVE');
```

Generate a hash locally using PHP CLI:
```bash
php -r "echo password_hash('YourAdminPassword', PASSWORD_DEFAULT) . PHP_EOL;"
```

Then, create the employee record linked to that user:
```sql
SET @uid = LAST_INSERT_ID();
INSERT INTO employees (user_id, first_name, last_name, department, designation, joining_date)
VALUES (@uid, 'Admin', 'User', 'HR', 'Administrator', CURDATE());
```

## Usage
1. Register a new user (`register.php`) or log in (`index.php`).
2. Admin users are redirected to `/admin/dashboard.php`.
3. Employees are redirected to `/employee/dashboard.php`.
4. Use admin panel to add employees, approve leaves, view reports, and run payroll.

## Troubleshooting
- DB errors: check `config/db.php` and ensure MySQL is running.
- Upload errors: confirm `uploads/` permissions (e.g., `chmod -R 755 uploads/`).
- Session issues: check `session.save_path` in php.ini.

## Contributing
1. Fork the repo
2. Create a branch (feature/my-feature)
3. Make changes and add tests where applicable
4. Open a pull request describing your changes

Consider adding `CONTRIBUTING.md` to document branch/commit conventions.

## License
Add a LICENSE file (MIT recommended) to enable reuse:

```
MIT License
...
```

## Security
- Never commit real credentials.
- Validate and sanitize file uploads.
- Use prepared statements/parameterized queries to avoid SQL injection.
- Limit upload types/sizes and store sensitive files outside web root when possible.

## Contact
- Maintainer: [Atharav2006](https://github.com/Atharav2006)
```
