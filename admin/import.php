<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/SimpleXlsxReader.php';
require_once __DIR__ . '/../config/database.php';

requireAdminLogin();

function detectHeaderRow(array $rows): ?int
{
    foreach ($rows as $i => $row) {
        $joined = strtolower(implode(' ', array_filter($row, fn($v) => $v !== null)));
        $hits = 0;
        foreach (['reg', 'name', 'mail', 'date'] as $keyword) {
            if (str_contains($joined, $keyword)) {
                $hits++;
            }
        }
        if ($hits >= 3) {
            return $i;
        }
    }
    return null;
}

function mapHeaderToField(string $header): ?string
{
    $h = strtolower(trim($header));
    if ($h === '') {
        return null;
    }
    // Order matters: "Registered Date" contains "reg", so date is checked first.
    if (str_contains($h, 'date')) {
        return 'registered_date';
    }
    if (str_contains($h, 'reg') && (str_contains($h, 'no') || str_contains($h, 'number'))) {
        return 'registration_number';
    }
    if (str_contains($h, 'mail')) {
        return 'email';
    }
    if (str_contains($h, 'program') || str_contains($h, 'programme') || str_contains($h, 'course')) {
        return 'program';
    }
    if (str_contains($h, 'name')) {
        return 'full_name';
    }
    return null;
}

function parseExcelDate(?string $value): ?string
{
    if ($value === null || trim($value) === '') {
        return null;
    }
    $value = trim($value);

    // A true Excel date cell (numeric day-serial), in case a future export uses one.
    if (is_numeric($value)) {
        $date = (new DateTime('1899-12-30'))->modify('+' . (int) $value . ' days');
        return $date->format('Y-m-d');
    }

    foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'm/d/Y'] as $format) {
        $date = DateTime::createFromFormat($format, $value);
        $errors = DateTime::getLastErrors();
        if ($date !== false && (!$errors || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
            return $date->format('Y-m-d');
        }
    }

    return null;
}

/** @return array{imported:int, updated:int, unchanged:int, skipped:array<int,string>, total_rows:int} */
function importRoster(string $filePath, PDO $pdo): array
{
    $rows = SimpleXlsxReader::readFirstSheet($filePath);

    if (count($rows) === 0) {
        throw new RuntimeException('The first sheet is empty.');
    }

    $headerIndex = detectHeaderRow(array_slice($rows, 0, 5));
    if ($headerIndex === null) {
        throw new RuntimeException('Could not find a header row with recognizable columns (Reg. Number, Name, Email, Date).');
    }

    $fieldMap = [];
    foreach ($rows[$headerIndex] as $colIndex => $header) {
        $field = mapHeaderToField((string) $header);
        if ($field !== null) {
            $fieldMap[$colIndex] = $field;
        }
    }

    if (!in_array('registration_number', $fieldMap, true) || !in_array('full_name', $fieldMap, true)) {
        throw new RuntimeException('Could not find Reg. Number and Name columns in the header row.');
    }

    $dataRows = array_slice($rows, $headerIndex + 1);

    $valid = [];
    $skipped = [];
    $seenInFile = [];

    foreach ($dataRows as $i => $row) {
        $rowNumber = $headerIndex + 2 + $i; // 1-indexed, matches the row number you'd see in Excel

        $record = ['registration_number' => null, 'full_name' => null, 'email' => '', 'program' => null, 'registered_date' => null];
        foreach ($fieldMap as $colIndex => $field) {
            $record[$field] = isset($row[$colIndex]) ? trim((string) $row[$colIndex]) : null;
        }

        $regNumber = $record['registration_number'];
        $fullName = $record['full_name'];

        if ($regNumber === null || $regNumber === '' || $fullName === null || $fullName === '') {
            $skipped[] = "Row {$rowNumber}: missing registration number or name";
            continue;
        }

        if (isset($seenInFile[$regNumber])) {
            $skipped[] = "Row {$rowNumber}: duplicate registration number (also row {$seenInFile[$regNumber]})";
            continue;
        }
        $seenInFile[$regNumber] = $rowNumber;

        $valid[] = [
            'registration_number' => $regNumber,
            'full_name' => $fullName,
            'email' => $record['email'] ?? '',
            'program' => ($record['program'] ?? '') !== '' ? $record['program'] : null,
            'registered_date' => parseExcelDate($record['registered_date']) ?? date('Y-m-d'),
        ];
    }

    $inserted = 0;
    $updated = 0;
    $unchanged = 0;

    if (count($valid) > 0) {
        try {
            $pdo->beginTransaction();

            // Only roster fields are ever touched here — attendance_status,
            // attendance_time, and marked_by are never in this statement, so a
            // re-import can never undo someone's check-in.
            $stmt = $pdo->prepare(
                'INSERT INTO students (registration_number, full_name, email, program, registered_date)
                 VALUES (:registration_number, :full_name, :email, :program, :registered_date)
                 ON DUPLICATE KEY UPDATE
                    full_name = VALUES(full_name),
                    email = VALUES(email),
                    program = VALUES(program),
                    registered_date = VALUES(registered_date)'
            );

            foreach ($valid as $record) {
                $stmt->execute($record);
                // MySQL's affected-rows for INSERT ... ON DUPLICATE KEY UPDATE:
                // 1 = inserted, 2 = existing row changed, 0 = existing row already matched.
                match ($stmt->rowCount()) {
                    1 => $inserted++,
                    2 => $updated++,
                    default => $unchanged++,
                };
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    return [
        'imported' => $inserted,
        'updated' => $updated,
        'unchanged' => $unchanged,
        'skipped' => $skipped,
        'total_rows' => count($dataRows),
    ];
}

$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Session expired. Please try again.';
    } elseif (empty($_FILES['roster']) || $_FILES['roster']['error'] !== UPLOAD_ERR_OK) {
        $error = match ($_FILES['roster']['error'] ?? UPLOAD_ERR_NO_FILE) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "That file is too large for this server's upload limit.",
            UPLOAD_ERR_NO_FILE => 'Please choose a file to upload.',
            default => 'Upload failed. Please try again.',
        };
    } elseif (strtolower(pathinfo($_FILES['roster']['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
        $error = 'Only .xlsx files are supported.';
    } else {
        try {
            $result = importRoster($_FILES['roster']['tmp_name'], getDbConnection());
        } catch (Throwable $e) {
            error_log('roster import error: ' . $e->getMessage());
            $error = 'Could not process the file: ' . $e->getMessage();
        }
    }
}

$csrfToken = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#7A1F2B">
<meta name="robots" content="noindex, nofollow">
    <title>Students Upload | Graduation Attendance</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=IBM+Plex+Mono:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="assets/css/admin.css">
    <link rel="icon" type="image/ico" href="../favicon.ico"/>
</head>
<body>
<div class="admin-page">

  <header class="admin-topbar">
    <div class="admin-topbar__brand">
        <span>Admin &middot; Attendance</span>
    </div>
    <nav class="admin-topbar__nav">
      <a href="dashboard.php">Dashboard</a>
      <a href="import.php" class="is-active">Import Students</a>
        <a href="charts.php">Charts</a>
    </nav>
    <div class="admin-topbar__user">
      <span><?= htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES) ?></span>
      <a href="logout.php" class="admin-topbar__logout">Log out</a>
    </div>
  </header>

  <main class="admin-main">

    <section class="panel-section">
      <h2 class="section-title">Import Student Roster</h2>
      <p class="section-note" style="max-width: 100%">
        Upload the registration export (.xlsx). Only the first tab is read.
        Matching registration numbers are updated with the new name, email,
        program, and registered date — attendance is never touched. New
        registration numbers are added as pending.
      </p>

      <?php if ($error): ?>
        <div class="message"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" class="import-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
        <input type="file" name="roster" accept=".xlsx" required class="file-input">
        <button type="submit" class="btn btn--primary btn--inline">Upload &amp; Import</button>
      </form>
    </section>

    <?php if ($result): ?>
      <section class="panel-section">
        <h2 class="section-title">Import Results</h2>

        <div class="stats-grid">
          <div class="stat-card">
            <span class="stat-card__value"><?= (int) $result['imported'] ?></span>
            <span class="stat-card__label">New</span>
          </div>
          <div class="stat-card">
            <span class="stat-card__value"><?= (int) $result['updated'] ?></span>
            <span class="stat-card__label">Updated</span>
          </div>
          <div class="stat-card">
            <span class="stat-card__value"><?= (int) $result['unchanged'] ?></span>
            <span class="stat-card__label">Unchanged</span>
          </div>
          <div class="stat-card">
            <span class="stat-card__value"><?= count($result['skipped']) ?></span>
            <span class="stat-card__label">Skipped</span>
          </div>
        </div>

        <?php if (!empty($result['skipped'])): ?>
          <h3 class="section-title section-title--small">Skipped Rows</h3>
          <ul class="skipped-list">
            <?php foreach ($result['skipped'] as $reason): ?>
              <li><?= htmlspecialchars($reason, ENT_QUOTES) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>
    <?php endif; ?>

  </main>
</div>
</body>
</html>
