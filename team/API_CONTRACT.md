# Sprint 1 PHP Pages

The app uses simple PHP pages, HTML forms, `$_POST`, and `mysqli_query()`. It is not a JSON API. Text entered by users is escaped with `mysqli_real_escape_string()` before it is added to SQL.

## Features

| Story | What the app does |
|---|---|
| US001 Register | Creates an account with a unique email and hashed password. |
| US002 Log in | Checks the email and password, starts a PHP session, and shows the student's own data. |
| US003 Manage subjects | Adds, renames, reorders, and deletes subjects. |
| US004 Create tasks | Creates a task under one of the student's subjects. Types: General, Exam Prep, Major Project. Exam Prep requires a future date. |
| US005 Identify obstacles | Saves up to three fixed-choice answers and optional notes. Skip saves “Unclear where to start.” No AI is used. |
| US009 Request a hint | Records a hint request and optional note. The app does not generate a hint. |

## Pages

All pages are in `backend/public/`.

| Page | Purpose |
|---|---|
| `index.php` | Opens login or home, depending on session. |
| `register.php`, `login.php`, `logout.php` | Account and session actions. |
| `home.php`, `delete_subject.php` | View and manage subjects and tasks. |
| `create_task.php` | Create a task. |
| `task.php` | View a task, its obstacle response, and hint request count. |
| `obstacles.php` | Save obstacle answers. |
| `request_hint.php` | Save a hint request. |

Protected pages check `$_SESSION['studentId']`. Database connection settings are in `backend/config/database.php`.

## Database

For a new database, import `database/schema.sql`. If the database was created using the older schema, run `database/migrations/001_sprint1_features.sql` once.

Sprint 1 uses `students`, `subjects`, `academic_tasks`, `task_obstacles`, and `task_hint_requests`.

## Not included

This version uses PHP sessions, not Firebase. It does not import Moodle calendars, generate AI questions or hints, or implement Sprint 2 features.
