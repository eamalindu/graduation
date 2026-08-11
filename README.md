# Metropolitan College — Graduation Ceremony Attendance

Student check-in for the convocation. No login — a student looks themself up
by registration number and confirms their own attendance.

## Setup

1. Import the schema:
   ```
   mysql -u root -p < database/schema.sql
   ```
   This creates the `metropolitan_attendance` database, the `students` table,
   and three sample rows (`MC/2022/0001`, `MC/2022/0002`, `MC/2022/0003`) for testing.

2. Edit `config/database.php` with your actual DB host / name / user / password.

3. Point your web server's document root at this folder and open `index.php`.
   (Any PHP 7.4+ / MySQL host — Apache or Nginx — works; no framework required.)

4. `config/database.php` is already in `.gitignore` — don't remove that line
   once real credentials are in there.

## How it works

- Student enters their registration number and taps **Find My Record**.
- `api/search_student.php` looks it up (case-insensitive) and returns their
  name / course / faculty / batch, plus whether they're already marked present.
- Once a record is found and pending, **Confirm Attendance** becomes tappable
  (it's disabled until then).
- Tapping it calls `api/mark_attendance.php`, which sets
  `attendance_status = 'present'` and `attendance_time = NOW()`. The update is
  guarded by `WHERE attendance_status = 'pending'`, so a double-tap or two
  overlapping requests can't double-mark someone — the second one is told
  it's already recorded instead.

## Next step

The admin UI at `/admin` — live roster, attendance counts, manual search/override.
