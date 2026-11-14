# API Contract — Sprint 1

Status: Draft for agreement before feature integration. This is documentation, not executable code.

## Common request rules

- Protected routes require a Firebase ID token in the authorization header.
- The PHP API validates the token; the verified Firebase user ID determines record ownership.
- Request bodies contain feature data, not trusted identity claims.
- Responses and error behavior must be agreed before frontend and backend features integrate.
- Firebase registration and login use Firebase Authentication; they are not MySQL password endpoints.

## Proposed response behavior

- Successful reads return the requested record or a collection of records.
- Successful creates return the created record and its stable identifier.
- Validation and authorization failures return an explicit error code and user-safe message.
- A failed database or provider operation must not be reported as a successful save.

## Proposed feature endpoints

| Use case | Proposed operation | Request information | Successful result |
|---|---|---|---|
| US003 Manage subjects | `GET` and `POST /api/subjects` | Subject name and any fields agreed by the team | Subject identifiers and the user's subject list |
| US004 Create academic tasks | `POST /api/tasks` | Subject ID, title, details, and General/Exam Prep type | Saved task identifier and task summary |
| US005 Identify task obstacles | `POST /api/tasks/{taskId}/obstacles` | Agreed obstacle answers | Saved obstacle summary |
| US009 Request task hint | `POST /api/tasks/{taskId}/hints` | Request context and step ID when available | Hint text and associated task/step |

Route paths and methods are proposals. Finalize them with the team before implementation; do not make a client depend on an undocumented route.

## Shared data conventions

- Subject, task, and step identifiers are stable opaque IDs.
- Task records refer to subjects by subject ID, not subject name.
- Every user-owned record is scoped to the verified Firebase user ID.
- MySQL is the source of truth for these records.
- US009's step-level input depends on checklist-step behavior currently assigned to Sprint 2; confirm the Sprint 1 fallback or deferral.
