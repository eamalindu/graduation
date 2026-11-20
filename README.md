# Metropolitan College Graduation Management System

[![License](https://img.shields.io/github/license/eamalindu/graduation)](https://github.com/eamalindu/graduation)
[![Issues](https://img.shields.io/github/issues/eamalindu/graduation)](https://github.com/eamalindu/graduation/issues)
[![Last Commit](https://img.shields.io/github/last-commit/eamalindu/graduation)](https://github.com/eamalindu/graduation/commits)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL%2FMariaDB-supported-4479A1)](https://www.mysql.com/)

A web application for managing key parts of a college graduation event: student registration and eligibility, ceremony check-in, seat information, administrator operations, and a prize draw. Students can look up their record using a registration number, while event staff use a protected dashboard to monitor and manage the roster.

## What the system can do

### Student experience

- Look up a graduation record by registration number and see registration, program, contact, email, and seat details.
- Distinguish approved students from students whose registration is complete.
- Confirm attendance from a mobile-friendly check-in page. A successful check-in records its time and is guarded against duplicate or overlapping submissions.
- View seat information and download a seat-arrangement image.
- Confirm registration through the registration page.
- Enter the graduation lucky draw. Each eligible registration can spin once; prize stock and draw results are recorded together to prevent overselling.

### Event administration

- Sign in to a protected admin area with session-based access controls.
- Monitor total, pending, and checked-in student counts, including the attendance rate and gold-medal nominee attendance.
- Search for a student by name or registration number, review recent check-ins, and manually mark or reverse attendance.
- Review student lists by registration and attendance status, and view registration, approval, and attendance-trend charts.
- Import registered or approved rosters from `.xlsx` files. The import reports added, updated, unchanged, and skipped rows; it preserves attendance records and does not demote registered students when importing approved records.

### Notifications

- Queue attendance confirmation emails and SMS messages rather than sending them during the check-in request.
- Send email through an SMTP server and SMS through the Dialog eSMS API using a command-line cron worker.
- Retry failed queue items up to a configured limit and expose sent and pending counts in the admin dashboard.

## Technologies

- **Backend:** PHP 8.0+ (the application uses PHP 8 language features), PDO, and prepared SQL statements.
- **Database:** MySQL or a compatible MariaDB server.
- **Frontend:** HTML, CSS, and browser-native JavaScript; responsive styles support phone and desktop layouts.
- **Charts:** locally bundled Highcharts 11.4.3.
- **Roster import:** a project-provided XLSX reader; no spreadsheet framework is used.
- **Email and configuration:** PHPMailer and `phpdotenv` are used by the notification code through Composer's autoloader.
- **SMS:** Dialog eSMS API, called with PHP cURL.

The application does not use a full-stack PHP framework.

## Project layout

| Path | Purpose |
| --- | --- |
| `index.php`, `registration.php` | Student-facing attendance and registration pages |
| `lucky_draw.php` | Student-facing prize wheel |
| `download_seat.php` | Validates and downloads a seat image |
| `api/` | Student lookup, registration, attendance, and lucky-draw endpoints |
| `admin/` | Login-protected dashboard, roster tools, charts, and admin APIs |
| `admin/includes/` | Admin session, authentication, and XLSX-reading helpers |
| `services/` | SMS number normalization and notification delivery |
| `cron/send_notifications.php` | CLI worker for queued email and SMS |
| `templates/` | HTML email templates |
| `assets/` | Frontend styles and JavaScript |
| `images/` | Branding, event imagery, and seat arrangements |
| `schema.sql` | Small starter schema and sample records |

## Requirements

- PHP 8.0 or newer with PDO MySQL and cURL extensions. XLSX roster imports also require ZipArchive and SimpleXML.
- MySQL or MariaDB.
- Apache, Nginx, or another web server configured to run PHP.
- Composer dependencies available through `vendor/autoload.php` if enabling notifications. The notification code uses PHPMailer and `phpdotenv`; these packages are not currently declared in `composer.json`, so provision them in the deployment before using email or SMS features.

## Setup

1. Clone the repository:

   ```sh
   git clone https://github.com/eamalindu/graduation.git
   cd graduation
   ```

2. Create and configure a MySQL/MariaDB database. Provide `config/database.php` with a `getDbConnection()` function that returns a configured PDO connection. This local configuration file is not tracked by Git.

3. Create the database tables and columns expected by the application. These include the extended student roster fields used throughout the app, plus registration, prizes, lucky-draw results, and notification-queue data. **`schema.sql` is only a minimal starter schema and sample data; it does not create the complete schema required by the current application.** Do not use it as the sole production database setup.

4. Configure the web server to serve the repository and ensure PHP requests are handled by PHP. Open the student-facing pages and `/admin/login.php` through the local or deployed site. The root `index.php` currently applies a date-based redirect to `registration.php`; adjust that gate to match the event schedule.

5. To enable email and SMS, install the notification dependencies and configure the environment variables below. Set up a scheduled CLI invocation of `php cron/send_notifications.php` to process the queue.

## Configuration

### Database

`config/database.php` is expected by the PHP entry points and is intentionally not committed. Keep database credentials out of source control and configure it for the target environment.

### Email and SMS

The notification configuration reads these values from the environment (for example, from a root `.env` file):

| Variable | Purpose |
| --- | --- |
| `MAIL_HOST` | SMTP server hostname |
| `MAIL_PORT` | SMTP port (defaults to `587`) |
| `MAIL_USERNAME` | SMTP username |
| `MAIL_PASSWORD` | SMTP password |
| `MAIL_SECURE` | SMTP security mode (defaults to `tls`) |
| `MAIL_FROM` | Sender email address |
| `MAIL_FROM_NAME` | Sender display name |
| `SMS_API` | Dialog eSMS API key |
| `SMS_SOURCE` | Dialog eSMS sender/source address |

The SMS integration currently normalizes Sri Lankan mobile numbers; other or invalid numbers are not queued for SMS.

## Main endpoints

| Endpoint | Method | Purpose |
| --- | --- | --- |
| `api/search_student.php` | POST | Look up a student and return their record when registration is complete |
| `api/mark_attendance.php` | POST | Record a student's attendance and queue eligible notifications |
| `api/check_registration.php` | POST | Check registration details |
| `api/mark_registration.php` | POST | Process a registration confirmation |
| `api/spin.php` | POST | Record a one-time lucky-draw result and decrement available prize stock |

The admin dashboard also uses JSON endpoints under `admin/api/`; these are intended for authenticated admin screens.

## Security and deployment

- Serve the site over HTTPS in production; enable the secure session-cookie option in `admin/includes/auth.php` when HTTPS is configured.
- Protect database and mail/SMS credentials; never commit secrets.
- The admin pages require a signed-in session and the roster import uses CSRF-token validation. Restrict access to the admin area to authorized event staff.
- Schedule `cron/send_notifications.php` as a CLI job only. It rejects web requests and uses a lock to avoid concurrent workers.
- Review PHP and web-server logs when diagnosing failed database or notification operations.

## Development

Use the provided sample rows in `schema.sql` only for testing the basic attendance lookup shape. The complete application needs a database schema matching the current code paths; see [Setup](#setup) before testing registration, administration, the draw, or notifications.

Contributions and bug reports are welcome through GitHub issues and pull requests. See [LICENSE](LICENSE) for license information.
