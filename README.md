# Human Resource Management System (HRMS)

A web-based Human Resource Management System built with PHP. This application provides core HR functionalities including employee management, administrative dashboards, and reporting tools.

## ✨ Features

*   **Role-Based Access Control**: Separate interfaces and functionalities for administrators and employees.
*   **Employee Management**: Add, view, and manage employee profiles and information.
*   **Administrative Dashboard**: A central panel for HR administrators to oversee system operations.
*   **Reporting Module**: Generate and view various HR reports.
*   **User Authentication**: Secure login, registration, and password recovery (forgot password) system.

# Project File Structure .

Human-Resource-Management-System/
│
├── index.php                  # Landing / Login page
├── register.php               # User Registration
├── logout.php                 # Logout logic
│
├── config/
│   ├── db.php                 # Database connection
│   └── auth.php               # Session & authentication check
│
├── assets/
│   ├── css/
│   │   ├── bootstrap.min.css
│   │   └── style.css          # Custom styles
│   │
│   ├── js/
│   │   ├── bootstrap.bundle.min.js
│   │   ├── jquery.min.js
│   │   └── main.js            # Custom JavaScript
│   │
│   └── images/
│       ├── logo.png
│       └── profile/
│           └── default.png
│
├── uploads/
│   ├── profile_pictures/
│   └── documents/
│
├── admin/
│   ├── dashboard.php
│   ├── employees.php
│   ├── add_employee.php
│   ├── attendance.php
│   ├── leave_requests.php
│   ├── payroll.php
│   └── reports.php
│
├── employee/
│   ├── dashboard.php
│   ├── profile.php
│   ├── edit_profile.php
│   ├── attendance.php
│   ├── apply_leave.php
│   └── payroll.php
│
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── navbar.php
│
├── actions/
│   ├── login_action.php
│   ├── register_action.php
│   ├── attendance_action.php
│   ├── leave_action.php
│   └── payroll_action.php
│
├── reports/
│   ├── attendance_report.php
│   └── salary_slip.php
│
├── .gitignore
└── README.md

## 🛠️ Technology Stack

*   **Backend**: PHP (99.3% of the repository)
*   **Frontend**: HTML, CSS, JavaScript (typically found within the PHP files or assets folder)
*   **Database**: (Assumed to be MySQL/MariaDB, based on common PHP project patterns. The exact configuration should be checked in the `config/` directory.)

## 🚀 Getting Started

### Prerequisites
*   A web server with PHP support (e.g., Apache, Nginx).
*   MySQL or MariaDB database server.
*   A modern web browser.

### Installation Steps
1.  **Clone the repository:**
    ```bash
    git clone https://github.com/Atharav2006/Human-Resource-management-System.git
    ```
2.  **Move the project** to your web server's root directory (e.g., `htdocs` for XAMPP, `www` for WAMP).
3.  **Create a database** for the HRMS using your preferred tool (phpMyAdmin, MySQL CLI).
4.  **Configure the connection:** Import any provided SQL file (if it exists) and update the database credentials in the `config/` files.
5.  **Access the application** via your browser at `http://localhost/Human-Resource-management-System/`.

## 📖 Usage
1.  Navigate to the application's root URL.
2.  **For first-time users:** Use the `register.php` page to create an account.
3.  **For existing users:** Log in via `index.php`. You will be redirected to either the admin dashboard or employee portal based on your role.
4.  Use the navigation within the dashboard to access different modules like employee management or reports.

## 👥 Contributors

Thanks to these individuals for their contributions to this project:

*  [Atharav2006](https://github.com/Atharav2006) - Project maintainer.
*  [pujan11patel](https://github.com/pujan11patel)
*  [rudra00030009](https://github.com/rudra00030009)
*  [Maharshi-1506](https://github.com/Maharshi-1506)

## 📄 License

This project does not have a specified license in the repository. Please contact the repository owner for details regarding usage and distribution.


