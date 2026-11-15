# API Contract — Current StartSmart PHP App

Status: Current implementation contract for the PHP + HTML + MySQL application.

This project is not a JSON REST API yet. It uses PHP server-rendered pages with HTML forms and session-based authentication. The app is designed to run behind a PHP-enabled web server such as XAMPP, WAMP, MAMP, or Apache + PHP.

## 1. Application type

- Runtime: PHP web application
- Interface: HTML forms and server-side rendering
- Auth model: PHP sessions, not Firebase
- Persistence: MySQL via `backend/config/database.php`
- Request pattern: `POST` form submission + redirect, not AJAX/JSON API calls

## 2. Current authenticated flow

- `index.php` redirects to either `login.php` or `home.php`
- `register.php` creates a new `students` row and logs the user in via session
- `login.php` checks `students.email` and verifies the hashed password
- `home.php` loads that student’s subject list and allows adding new subjects
- `delete_subject.php?id={subjectId}` deletes only the subject owned by the current student
- `logout.php` destroys the session

## 3. Route contract

| Route | Method | Purpose | Auth required |
|---|---|---|---|
| `/backend/public/index.php` | `GET` | Redirect to login or home | No |
| `/backend/public/register.php` | `GET`, `POST` | Create student account | No |
| `/backend/public/login.php` | `GET`, `POST` | Sign in to the app | No |
| `/backend/public/home.php` | `GET`, `POST` | View dashboard and add subjects | Yes |
| `/backend/public/delete_subject.php?id=123` | `GET` | Delete a subject by ID for current student | Yes |
| `/backend/public/logout.php` | `GET` | End session | Yes |

## 4. Request and response conventions

### Register form

Request fields:
- `name` — full student name
- `email` — unique email address
- `password` — minimum 8 characters

Behavior:
- Validates required fields and password length
- Checks whether email already exists
- Hashes password with `password_hash()`
- Stores record in `students`
- Creates session variables:
  - `studentId`
  - `studentName`
- Redirects to `home.php`

### Login form

Request fields:
- `email`
- `password`

Behavior:
- Normalizes email to lowercase
- Looks up `students` by email
- Verifies password with `password_verify()`
- Stores session variables and redirects to `home.php`
- On failure, shows in-page error message

### Subject form on home.php

Request fields:
- `subject_name` — required subject name
- `subject_code` — optional code

Behavior:
- Validates subject name is not empty
- Prevents duplicate subject names for the same student
- Inserts into `subjects`
- Displays success/error message in page

### Delete subject action

Query parameter:
- `id` — subjectId to delete

Behavior:
- Confirms the subject belongs to the logged-in student
- Executes `DELETE FROM subjects WHERE subjectId = ? AND studentId = ?`
- Redirects back to `home.php`

## 5. Data model used by the current app

### students

| Column | Type | Notes |
|---|---|---|
| `studentId` | INT PK | Auto-increment |
| `name` | VARCHAR(100) | Required |
| `email` | VARCHAR(150) | Unique |
| `password` | VARCHAR(255) | Hashed password |
| `createdAt` | TIMESTAMP | Default current timestamp |

### subjects

| Column | Type | Notes |
|---|---|---|
| `subjectId` | INT PK | Auto-increment |
| `studentId` | INT FK | Owner of the subject |
| `subjectName` | VARCHAR(100) | Required |
| `subjectCode` | VARCHAR(20) | Optional |
| `sortOrder` | INT | Default 0 |
| `createdAt` | TIMESTAMP | Default current timestamp |

## 6. Current implementation caveats

- This is still a basic prototype, not a full production API.
- There is no JSON response format yet.
- There is no Firebase token layer in the current app.
- There is no `api/` route structure yet.
- The app assumes the database is already created and the `students` and `subjects` tables exist.

## 7. Recommended next step

If the team wants to evolve this into a cleaner system, the next version should either:

- keep the current PHP server-rendered app and maintain these page contracts, or
- migrate to a proper REST API with JSON endpoints and explicit auth tokens.

For the current codebase, the official contract is the session-based PHP page flow above, not Firebase or JSON API conventions.
