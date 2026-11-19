<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['studentId'])) {
    header('Location: login.php');
    exit;
}

$studentId = (int) $_SESSION['studentId'];
$error = '';
$message = '';
$importedCount = 0;
$skippedCount = 0;
$semesterNumber = 1;

function unescapeCalendarText(string $value): string
{
    $value = preg_replace_callback('/\\\\([nN,;\\\\])/', function ($match) {
        switch ($match[1]) {
            case 'n':
            case 'N':
                return "\n";
            default:
                return $match[1];
        }
    }, $value);

    return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function parseCalendarDate(array $property): ?DateTime
{
    $value = $property['value'];
    if (preg_match('/^\d{8}$/', $value)) {
        $date = DateTime::createFromFormat('!Ymd', $value, new DateTimeZone('UTC'));
    } elseif (preg_match('/^\d{8}T\d{6}Z$/', $value)) {
        $date = DateTime::createFromFormat('!Ymd\THis\Z', $value, new DateTimeZone('UTC'));
    } elseif (preg_match('/^\d{8}T\d{6}$/', $value)) {
        $timezoneName = $property['params']['TZID'] ?? 'UTC';
        $timezone = in_array($timezoneName, DateTimeZone::listIdentifiers(), true)
            ? new DateTimeZone($timezoneName)
            : new DateTimeZone('UTC');
        $date = DateTime::createFromFormat('!Ymd\THis', $value, $timezone);
    } else {
        return null;
    }

    if (!$date) {
        return null;
    }

    $dateErrors = DateTime::getLastErrors();
    return $dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)
        ? null
        : $date;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $semesterNumber = filter_var($_POST['semester_number'] ?? null, FILTER_VALIDATE_INT);
    if ($semesterNumber === false || $semesterNumber === null || $semesterNumber < 1 || $semesterNumber > 8) {
        $error = 'Choose a valid semester.';
    } elseif (!isset($_FILES['calendar_file'])) {
        $error = 'Choose a Moodle/UTM calendar export file.';
    } elseif ($_FILES['calendar_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'The calendar file could not be uploaded.';
    } elseif (strtolower(pathinfo($_FILES['calendar_file']['name'], PATHINFO_EXTENSION)) !== 'ics') {
        $error = 'Upload a calendar file with the .ics extension.';
    } elseif ($_FILES['calendar_file']['size'] > 5 * 1024 * 1024) {
        $error = 'The calendar file must be 5 MB or smaller.';
    } else {
        $calendarData = file_get_contents($_FILES['calendar_file']['tmp_name']);
        if ($calendarData === false || strpos($calendarData, 'BEGIN:VCALENDAR') === false) {
            $error = 'The uploaded file is not a readable iCalendar export.';
        } else {
            $lines = preg_split('/\r\n|\n|\r/', $calendarData);
            $unfoldedLines = [];
            foreach ($lines as $line) {
                if ($line !== '' && ($line[0] === ' ' || $line[0] === "\t") && count($unfoldedLines) > 0) {
                    $lastIndex = count($unfoldedLines) - 1;
                    $unfoldedLines[$lastIndex] .= substr($line, 1);
                } else {
                    $unfoldedLines[] = $line;
                }
            }

            $events = [];
            $currentEvent = null;
            foreach ($unfoldedLines as $line) {
                if (strtoupper($line) === 'BEGIN:VEVENT') {
                    $currentEvent = [];
                    continue;
                }
                if (strtoupper($line) === 'END:VEVENT') {
                    if ($currentEvent !== null) {
                        $events[] = $currentEvent;
                    }
                    $currentEvent = null;
                    continue;
                }
                if ($currentEvent === null || strpos($line, ':') === false) {
                    continue;
                }

                [$propertyName, $value] = explode(':', $line, 2);
                $parts = explode(';', $propertyName);
                $name = strtoupper(array_shift($parts));
                $params = [];
                foreach ($parts as $part) {
                    if (strpos($part, '=') !== false) {
                        [$key, $paramValue] = explode('=', $part, 2);
                        $params[strtoupper($key)] = trim($paramValue, '"');
                    }
                }
                $currentEvent[$name] = ['value' => $value, 'params' => $params];
            }

            $transactionStarted = false;
            try {
                $subjectLookup = mysqli_prepare(
                    $conn,
                    'SELECT subjectId FROM subjects WHERE studentId = ? AND subjectCode = ? AND semesterNumber = ? LIMIT 1'
                );
                $subjectInsert = mysqli_prepare(
                    $conn,
                    'INSERT INTO subjects (studentId, subjectName, subjectCode, semesterNumber) VALUES (?, ?, ?, ?)'
                );
                $taskUpsert = mysqli_prepare(
                    $conn,
                    "INSERT INTO academic_tasks
                        (studentId, subjectId, title, description, taskType, dueDate, externalEventUid)
                     VALUES (?, ?, ?, ?, 'General', ?, ?)
                     ON DUPLICATE KEY UPDATE
                        subjectId = VALUES(subjectId),
                        title = VALUES(title),
                        description = VALUES(description),
                        dueDate = VALUES(dueDate)"
                );

                if (!$subjectLookup || !$subjectInsert || !$taskUpsert) {
                    $error = 'Calendar import is unavailable because its database migration has not been applied.';
                } elseif (!mysqli_begin_transaction($conn)) {
                    $error = 'Could not start the calendar import.';
                } else {
                    $transactionStarted = true;
                    $databaseFailed = false;
                    $databaseError = '';

                    foreach ($events as $event) {
                        $uid = trim($event['UID']['value'] ?? '');
                        $title = unescapeCalendarText(trim($event['SUMMARY']['value'] ?? ''));
                        $date = isset($event['DTSTART']) ? parseCalendarDate($event['DTSTART']) : null;
                        $categoryValue = unescapeCalendarText($event['CATEGORIES']['value'] ?? '');
                        $courseCode = trim(explode(',', $categoryValue, 2)[0]);
                        $description = unescapeCalendarText($event['DESCRIPTION']['value'] ?? '');

                        $titleLength = preg_match_all('/./us', $title);
                        if ($uid === '' || $title === '' || $courseCode === '' || strlen($courseCode) > 20 || !$date || $titleLength === false || $titleLength > 150) {
                            $skippedCount++;
                            continue;
                        }

                        $subjectId = null;
                        if (!mysqli_stmt_bind_param($subjectLookup, 'isi', $studentId, $courseCode, $semesterNumber) ||
                            !mysqli_stmt_execute($subjectLookup) ||
                            !mysqli_stmt_bind_result($subjectLookup, $subjectId) ||
                            !mysqli_stmt_store_result($subjectLookup)) {
                            $databaseFailed = true;
                            $databaseError = mysqli_stmt_error($subjectLookup);
                            break;
                        }
                        if (mysqli_stmt_num_rows($subjectLookup) > 0 && !mysqli_stmt_fetch($subjectLookup)) {
                            $databaseFailed = true;
                            $databaseError = mysqli_stmt_error($subjectLookup);
                            break;
                        }
                        mysqli_stmt_free_result($subjectLookup);

                        if ($subjectId === null) {
                            $subjectName = $courseCode;
                            if (!mysqli_stmt_bind_param($subjectInsert, 'issi', $studentId, $subjectName, $courseCode, $semesterNumber) ||
                                !mysqli_stmt_execute($subjectInsert)) {
                                $databaseFailed = true;
                                $databaseError = mysqli_stmt_error($subjectInsert);
                                break;
                            }
                            $subjectId = mysqli_insert_id($conn);
                        }

                        $dueDate = $date->format('Y-m-d');
                        if (!mysqli_stmt_bind_param($taskUpsert, 'iissss', $studentId, $subjectId, $title, $description, $dueDate, $uid) ||
                            !mysqli_stmt_execute($taskUpsert)) {
                            $databaseFailed = true;
                            $databaseError = mysqli_stmt_error($taskUpsert);
                            break;
                        }
                        $importedCount++;
                    }

                    mysqli_stmt_close($subjectLookup);
                    mysqli_stmt_close($subjectInsert);
                    mysqli_stmt_close($taskUpsert);

                    if ($databaseFailed) {
                        mysqli_rollback($conn);
                        $transactionStarted = false;
                        error_log('UTM calendar import failed: ' . $databaseError);
                        $error = 'Calendar import failed while saving events. Check the server log and try again.';
                    } elseif (!mysqli_commit($conn)) {
                        error_log('UTM calendar import could not commit: ' . mysqli_error($conn));
                        mysqli_rollback($conn);
                        $transactionStarted = false;
                        $error = 'Could not finish saving the calendar import.';
                    } else {
                        $transactionStarted = false;
                        $message = "Imported or updated $importedCount event(s).";
                        if ($skippedCount > 0) {
                            $message .= " Skipped $skippedCount event(s) with missing or invalid course, title, or date information.";
                        }
                    }
                }
            } catch (mysqli_sql_exception $exception) {
                if ($transactionStarted) {
                    mysqli_rollback($conn);
                }
                error_log('UTM calendar import failed: ' . $exception->getMessage());
                $error = 'Calendar import failed. Check the server log and try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Import UTM Calendar - StartSmart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="navbar">
        <a class="brand" href="home.php">🚀 StartSmart</a>
        <nav>
            <a href="home.php">Home</a>
            <a href="create_task.php">New Task</a>
            <a href="logout.php">Log Out</a>
        </nav>
    </header>

    <main class="container">
        <section class="card">
            <h1>Import UTM e-learning calendar</h1>
            <p class="note">Upload a Moodle/UTM calendar export (.ics). Events are saved as academic tasks under the matching course code in the semester you choose. Re-importing updates matching events; events missing from a later export are not deleted.</p>

            <?php if ($message !== ''): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form action="import_calendar.php" method="POST" enctype="multipart/form-data">
                <label class="field">Semester
                    <select name="semester_number" required>
                        <?php for ($semester = 1; $semester <= 8; $semester++): ?>
                            <?php $year = intdiv($semester - 1, 2) + 1; $inYear = (($semester - 1) % 2) + 1; ?>
                            <option value="<?php echo $semester; ?>" <?php echo $semesterNumber === $semester ? 'selected' : ''; ?>>
                                Year <?php echo $year; ?>, Semester <?php echo $inYear; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label class="field">Moodle/UTM calendar export (.ics)
                    <input type="file" name="calendar_file" accept=".ics,text/calendar" required>
                </label>
                <div class="btn-row">
                    <button type="submit">Import Calendar</button>
                    <a class="btn btn-ghost" href="home.php">Back to subjects</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
