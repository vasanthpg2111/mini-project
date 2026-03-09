# College Feedback System (PHP + MySQL)

A simple web-based application to collect feedback from students about **subjects** and **faculty**, and allow admins to securely view/export feedback.

## Requirements

- PHP 8.x (with PDO MySQL extension enabled)
- MySQL 8.x (or MariaDB)

## Setup

- **1) Create database**
  - Create a database named `college_feedback` (or change it in `config.php`).

- **2) Import schema + seed data**
  - Import `database/schema.sql` into your database (it creates tables and inserts sample subjects/faculty + a default admin).

- **3) Create `config.php`**
  - Copy `config.example.php` to `config.php` and update DB credentials (**especially `user`, `pass`, and sometimes `port`**).
  - If you’re using **XAMPP**, MySQL/MariaDB is usually on **port 3306** (see `C:\xampp\mysql\bin\my.ini`).
  - If you have another MySQL installed, there may be a **port conflict**; update `config.php` to match the port your DB is actually running on.

## Run locally (PHP built-in server)

From the project root:

```bash
php -S 127.0.0.1:8000 -t public
```

Then open:

- Student portal: `http://127.0.0.1:8000/`
- Admin portal: `http://127.0.0.1:8000/admin/login.php`

## Default admin credentials

- Username: `admin`
- Password: `admin123`

## Project structure

- `public/` – web root (student form + admin pages)
- `lib/` – database + auth helpers
- `database/schema.sql` – MySQL schema + seed data

## Troubleshooting

### PDOException: SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost' (using password: NO)

This means the app is connecting to MySQL, but your `config.php` credentials don’t match.

- **Fix option A (quick)**: set the correct password in `config.php`
  - Update:
    - `db.user` (e.g. `root`)
    - `db.pass` (your actual MySQL password)
    - `db.port` (if not `3306`)

- **Fix option B (recommended)**: create a dedicated user for this app
  - In phpMyAdmin (or any SQL client), run:

```sql
CREATE USER 'cfs'@'localhost' IDENTIFIED BY 'change_this_password';
GRANT ALL PRIVILEGES ON college_feedback.* TO 'cfs'@'localhost';
FLUSH PRIVILEGES;
```

  - Then set `db.user` = `cfs` and `db.pass` = `change_this_password` in `config.php`.


C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public