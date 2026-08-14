# Metropolitan College — Graduation Ceremony Attendance

[![License](https://img.shields.io/github/license/eamalindu/graduation)](https://github.com/eamalindu/graduation)
[![Issues](https://img.shields.io/github/issues/eamalindu/graduation)](https://github.com/eamalindu/graduation/issues)
[![Last Commit](https://img.shields.io/github/last-commit/eamalindu/graduation)](https://github.com/eamalindu/graduation/commits)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D7.4-brightgreen)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)](https://www.mysql.com/)

A simple, dependency-free PHP application to record student attendance at the graduation/convocation. Students self-check by registration number; admins manage and review attendance via a protected admin interface.

Table of contents
- Project overview
- Features
- Screenshots
- Prerequisites
- Installation & setup
- Configuration
- Database schema
- Usage
- API endpoints
- Admin interface
- Security & deployment notes
- Project structure
- Contributing
- License

Project overview

This repo implements a minimal check-in flow optimized for event-day use:
- No student login required — lookup by registration number
- Prevents duplicate marks with a guarded UPDATE
- Includes a small admin UI for live roster and overrides
- Runs on plain PHP + MySQL (no framework)

Features
- Fast student lookup (case-insensitive)
- Atomic attendance marking (attendance_status = 'present' guarded by WHERE)
- Admin roster with counts and manual override
- Small footprint; suitable for local LAMP/XAMPP deployment

Screenshots
- See the `images/` folder for UI screenshots used during development. Add event-specific screenshots there for clarity.

Prerequisites
- PHP 7.4 or newer
- MySQL / MariaDB (5.7+ recommended)
- Web server (Apache, Nginx) or XAMPP for local testing

Installation & setup

1. Clone the repository

   git clone https://github.com/eamalindu/graduation.git

2. Import the database schema (provided as schema.sql)

   mysql -u root -p < schema.sql

   The script creates the `metropolitan_attendance` database, a `students` table, and includes a few sample rows for testing.

3. Configure database connection

   Copy or edit `config/database.php` and set your DB host, name, user, and password. Note: this file is already listed in `.gitignore` to help avoid committing credentials.

4. Point the web server's document root at the project folder (or ensure the project is reachable via http://localhost/graduation) and open `index.php` in your browser.

Configuration

- config/database.php — database connection settings.
- config/app.php (if present) — global app settings (site title, timezone, etc.)

Database schema

The minimal schema (schema.sql) contains a `students` table with the important columns:
- id (PK)
- registration_number (unique)
- name
- course
- faculty
- batch
- attendance_status (pending|present)
- attendance_time (DATETIME nullable)

Usage (student flow)

1. Student opens the check-in page (index.php).
2. Enters registration number and taps "Find My Record".
3. The app returns student details and the current attendance status.
4. If status is `pending`, the "Confirm Attendance" button becomes available.
5. Tapping Confirm triggers `api/mark_attendance.php` which atomically sets `attendance_status='present'` and `attendance_time=NOW()` only when the status is still `pending` (prevents double-marking).

API Endpoints
- api/search_student.php
  - Method: GET or POST
  - Params: registration_number
  - Returns: JSON with student details or error
- api/mark_attendance.php
  - Method: POST
  - Params: student_id (or registration_number)
  - Behavior: sets attendance_status = 'present' and attendance_time = NOW() when current status = 'pending'

Admin interface
- /admin (open this directory in the browser)
- Features: live roster, attendance counts, search by registration number, manual override for corrections
- For production, secure the /admin area (HTTP auth, VPN, or simple login) — this repo does not include a hardened auth system out of the box.

Security & deployment notes
- Never commit real DB credentials; keep config/database.php in .gitignore.
- Serve the app over HTTPS in production.
- Add basic authentication for /admin or integrate with an existing admin auth provider.
- Validate and sanitize user input server-side (the API does basic checks, but review for injection risks).

Project structure (important files)
- index.php — student-facing check-in UI
- api/search_student.php — lookup endpoint
- api/mark_attendance.php — attendance update endpoint
- admin/ — admin UI and tools
- config/database.php — DB config (gitignored)
- schema.sql — DB schema and sample rows
- images/ — screenshots and assets
- assets/ — CSS, JS, images used by the UI

Development & testing
- Local testing with XAMPP or LAMP is recommended (quick setup).
- Use the provided sample rows in schema.sql to verify the check-in flow.

Contributing
- Open an issue to discuss significant changes first.
- Pull requests should include a concise description and, when applicable, updates to schema.sql or images.

License
- See the LICENSE file in the repo. If no license exists, add one (MIT is a common choice).

Contact
- Repo: https://github.com/eamalindu/graduation
- For questions, open an issue.

Acknowledgements
- Built for a simple, robust event-day attendance workflow. Contributions welcome.

