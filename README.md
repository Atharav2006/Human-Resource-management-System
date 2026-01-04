# Human Resource Management System (HRMS)

A web-based Human Resource Management System built with PHP. This application provides core HR functionalities including employee management, role-based access (admin/employee), attendance, leave management, payroll, and reporting.

[Optional badges — add when available]
- Build / CI: ![CI](https://img.shields.io/badge/CI-none-lightgrey)
- License: ![License](https://img.shields.io/badge/license-MIT-blue)
- Issues: ![Issues](https://img.shields.io/github/issues/Atharav2006/Human-Resource-management-System)

## Table of contents
- Features
- Demo / Screenshots
- Project structure
- Technology stack
- Prerequisites
- Installation
- Configuration
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

## Project file structure
Human-Resource-management-System/
│
├── index.php                  # Landing / Login page
├── register.php               # User Registration
├── logout.php                 # Logout logic
│
├── config/
│   ├── db.php                 # Database connection (update credentials here)
│   └── auth.php               # Session & authentication check
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── uploads/
│   ├── profile_pictures/
│   └── documents/
│
├── admin/
│   ├── dashboard.php
│   ├── employees.php
│   └── ...
│
├── employee/
│   ├── dashboard.php
│   ├── profile.php
│   └── ...
│
├── includes/
│   ├── header.php
│   └── footer.php
│
├── actions/
│   ├── login_action.php
│   └── register_action.php
│
├── reports/
│   └── attendance_report.php
│
├── .gitignore
└── README.md

## 🛠️ Technology Stack
- Backend: PHP (specify version below)
- Frontend: HTML, CSS, JavaScript (Bootstrap)
- Database: MySQL / MariaDB

## Prerequisites
- PHP 7.4+ (or specify required version)
- Enabled PHP extensions: mysqli (or PDO), openssl, mbstring, fileinfo
- MySQL or MariaDB
- Web server (Apache / Nginx). XAMPP/WAMP/LAMP for local development.
- Optional: Composer if you later add dependencies

## Installation
1. Clone the repository:
   ```bash
   git clone https://github.com/Atharav2006/Human-Resource-management-System.git
   ```
2. Move or copy the project to your web server root (e.g., `htdocs` for XAMPP).
3. Create a database for the project:
   - If a SQL dump exists (e.g., `db/backup.sql`), import it:
     ```bash
     mysql -u root -p hrms_db < db/backup.sql
     ```
   - If no dump is provided, create the database and tables using schema in `db/schema.sql` (add file if missing).
4. Configure database credentials:
   - Edit `config/db.php` and set DB_HOST, DB_USER, DB_PASS, DB_NAME.
5. Ensure `uploads/` and subfolders are writable by the web server.
6. Open your browser:
   ```
   http://localhost/Human-Resource-management-System/
   ```

## Configuration
- Open `config/db.php` and update credentials:
  ```php
  <?php
  // config/db.php (example)
  $host = 'localhost';
  $username = 'root';
  $password = '';
  $database = 'hrms_db';
  $conn = new mysqli($host, $username, $password, $database);
  ```
- If you prefer environment variables, add a `config/db.example.php` or `.env.example` and update `.gitignore` to exclude real credentials.

## Default / Test Accounts
- You can seed an initial admin user in the DB. Example (SQL):
  ```sql
  INSERT INTO users (username, password, role, email) VALUES ('admin', '<hashed-password>', 'admin', 'admin@example.com');
  ```
- Document the hashing method used (bcrypt/ password_hash).

## Usage
1. Register a new user (`register.php`) or log in (`index.php`).
2. Admin users will be redirected to `/admin/dashboard.php`.
3. Employees will be redirected to `/employee/dashboard.php`.
4. Use the admin panel to add employees, approve leaves, view reports, and run payroll.

## Troubleshooting
- DB connection error: ensure credentials in `config/db.php` are correct and MySQL is running.
- File upload errors: confirm `uploads/` permissions (e.g., `chmod -R 755 uploads/`).
- Session issues: check `session.save_path` in php.ini.

## Contributing
Contributions are welcome! Please:
1. Fork the repo
2. Create a new branch (feature/my-feature)
3. Make changes and add tests if applicable
4. Submit a pull request describing your changes

Optionally add a `CONTRIBUTING.md` file with branch & commit guidelines.

## License
This repository currently has no license file. To enable reuse, add a license (e.g., MIT). Example:
```
MIT License
...
```
Add a `LICENSE` file at repository root.

## Security
- Do not commit real credentials.
- Validate and sanitize file uploads.
- Use prepared statements (or parameterized queries) to avoid SQL injection.

## Contact
- Maintainer: [Atharav2006](https://github.com/Atharav2006)
