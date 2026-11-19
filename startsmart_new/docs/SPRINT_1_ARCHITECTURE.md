# Sprint 1 Architecture and Work Handoffs

## Goal and boundary

Prepare one shared repository so each member can work on a separate feature slice and integrate through agreed interfaces. This is a planning document; source placeholders are empty.

Target preparation date: Tuesday, 17 November 2026.

### Agreed technology direction

- Browser interface: HTML and JavaScript.
- Identity: Firebase Authentication.
- Server API: PHP.
- Application records: MySQL, accessed only by the PHP API.
- Firebase is not a second store for task or subject records.

The browser obtains a Firebase ID token after authentication and sends it to the PHP API. The API verifies that token, derives the user's identity from the verified token, then reads or writes that user's records in MySQL. Never trust a user ID supplied only in a request body, put database credentials in frontend files, or connect browser code directly to MySQL.

## Sprint allocation

| Member | Sprint 1 | Sprint 2 (AI Agent) |
|---|---|---|
| Joyce Puyang Maurice Utap | US004 — Create academic tasks | New edit/delete academic tasks; US011 — Set focus session; US012 — Perform self check-in |
| Lai Shi Ni | US003 — Manage subjects | US013 — View session wrap-up; US016 — View task history |
| Mohammad Harith Haikal | US001 — Register account; US002 — Log in | US006 — Generate checklist; US007 — Edit checklist |
| Md Tamim Shams | US005 — Identify task obstacles; US009 — Request task hint | US008 — Update task progress; US010 — View and edit task steps |

US014 and US015 are not assigned in the provided Sprint 1/Sprint 2 allocation.

## Member contracts

### Joyce — US004: Create academic tasks

- **Input:** selected subject identifier, task title, task details, and task type (General or Exam Prep).
- **Process:** collect and validate the form, confirm the subject exists through the subjects API, submit the task, and present success or recoverable validation/server errors.
- **Output:** created task identifier and the saved task summary.
- **Interface:** HTML task form and a JavaScript feature module call the agreed PHP task endpoint; consume the subject identifier returned by Lai's US003 feature. Do not import Lai's internal implementation.
- **Suggested files:** `frontend/src/features/us004-create-task.js`; `backend/src/Controllers/TaskController.php`; `backend/src/Services/TaskService.php`; `backend/src/Repositories/TaskRepository.php`.

### Lai — US003: Manage subjects

- **Input:** subject name and any subject fields confirmed in acceptance criteria; subject identifier for later selection.
- **Process:** validate subject data, apply the team-agreed create/list/update/archive scope, and persist the user's subject through the API.
- **Output:** subject identifier and a user-owned subject summary/list.
- **Interface:** provide Joyce's task form with stable subject identifiers via the agreed subjects endpoint; use the shared response and error format.
- **Suggested files:** `frontend/src/features/us003-subjects.js`; `backend/src/Controllers/SubjectController.php`; `backend/src/Services/SubjectService.php`; `backend/src/Repositories/SubjectRepository.php`.

### Mohammad — US001 and US002: Register and log in

- **Input:** registration name/email/password, or login email/password.
- **Process:** use Firebase Authentication for account creation and sign-in; keep passwords out of MySQL and application logs. After authentication, send the Firebase ID token to protected API requests.
- **Output:** authenticated Firebase user identity and short-lived ID token; clear success and user-safe failure states.
- **Interface:** provide the shared frontend API client with a way to obtain the current ID token. The PHP API verifies the token before returning protected data.
- **Suggested files:** `frontend/src/features/us001-register.js`; `frontend/src/features/us002-login.js`; `frontend/src/services/firebase-auth.js`; `frontend/src/services/api-client.js`.

### Md Tamim — US005 and US009: Identify obstacles and request a hint

- **Input:** task identifier and obstacle response for US005; task/step context and the user's request for US009.
- **Process:** define what obstacle information is captured, what is stored, and the hint request/response boundary. Keep AI-provider behavior behind a service boundary; do not fabricate AI output.
- **Output:** saved obstacle summary and, when an agreed provider is available, a hint associated with the requested task/step.
- **Interface:** consume task identifiers from Joyce's task flow and expose documented request/response contracts to the frontend. Sprint 2 checklist generation creates a dependency for task-step-specific hints.
- **Suggested files:** `frontend/src/features/us005-obstacles.js`; `frontend/src/features/us009-task-hint.js`; `backend/src/Controllers/ObstacleController.php`; `backend/src/Controllers/HintController.php`; matching service and repository files.
- **Sprint boundary decision required:** US005 and US009 are listed in Sprint 1, but AI Agent work is listed in Sprint 2. Agree whether Sprint 1 captures obstacle/hint requests without generated AI responses, or whether the AI-dependent part moves to Sprint 2.

## Shared contracts and dependency rules

1. **Authentication contract:** authenticated API requests carry a Firebase ID token. The PHP API validates it and uses the verified identity as the record owner.
2. **Identifiers:** use stable IDs named by entity (`subjectId`, `taskId`, and `stepId` when steps exist). Do not use display names as relationships.
3. **API contract:** agree route, request fields, response fields, status/error behavior, and ownership checks in `team/API_CONTRACT.md` before feature modules integrate.
4. **Error shape:** use one documented error response format with a stable error code and a user-safe message; never disguise a failed request as success.
5. **Persistence:** MySQL is the source of truth for application records. Repositories own SQL access; controllers handle HTTP, and services handle feature rules.
6. **Parallel work:** keep each assigned use case in its own frontend feature module and PHP controller/service/repository slice. Integrate only through the shared API contract, not another member's internal functions.
7. **Shared-file ownership:** Md Tamim coordinates schema and data ownership; Mohammad coordinates Firebase browser setup and API client; Joyce coordinates the shared task fields and US004 contract. Changes to shared contracts are reviewed before other members depend on them.
8. **No speculative dependencies:** do not add a framework, AI provider, or synchronization between MySQL and Firestore without an explicit team decision.

## Proposed repository layout

```text
frontend/
  index.html
  src/
    services/
      api-client.js
      firebase-auth.js
    features/
      us001-register.js
      us002-login.js
      us003-subjects.js
      us004-create-task.js
      us005-obstacles.js
      us009-task-hint.js
backend/
  public/
    index.php
  src/
    Controllers/
      SubjectController.php
      TaskController.php
      ObstacleController.php
      HintController.php
    Services/
      SubjectService.php
      TaskService.php
      ObstacleService.php
      HintService.php
    Repositories/
      SubjectRepository.php
      TaskRepository.php
      ObstacleRepository.php
      HintRepository.php
    Middleware/
      VerifyFirebaseToken.php
    Config/
      Database.php
database/
  schema.sql
  migrations/
tests/
  acceptance/
  integration/
  unit/
team/
  API_CONTRACT.md
```

The listed empty source files are starting locations, not complete implementations. Add further PHP service/repository files within the matching use-case slice as needed; do not put all feature logic in the entry point. Framework, routing mechanism, PHP version, and Firebase token-verification package must be agreed before backend implementation.

## Sprint 1 integration checkpoints

1. Confirm US003 subject fields and the minimum US004 task fields.
2. Agree the API contract, error format, authentication rule, and MySQL ownership model.
3. Resolve the US005/US009 AI scope decision.
4. Each owner builds only their assigned feature slice on a separate branch.
5. Test each feature against the shared contract, then integrate through pull requests into the Sprint 1 branch.
