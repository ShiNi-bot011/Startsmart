# StartSmart — Sprint 1 Repository Starter

Planning-first starter for the StartSmart student task and study application. Source placeholders are intentionally empty; this repository contains no implementation yet.

## Proposed stack
- Frontend: HTML and JavaScript
- Identity: Firebase Authentication
- Backend: PHP REST API
- Application data: MySQL, accessed only through the PHP API

## Start here
1. Read [Sprint 1 architecture](./docs/SPRINT_1_ARCHITECTURE.md) for the architecture, member responsibilities, data handoffs, and proposed file map.
2. Read [GitHub setup checklist](./docs/GITHUB_SETUP_CHECKLIST.md) to create and organize the shared GitHub repository.
3. Read the relevant `START_HERE.txt` in your project area before taking an assigned feature.

## Sprint 1 use cases
- Joyce Puyang Maurice Utap — US004: Create academic tasks
- Lai Shi Ni — US003: Manage subjects
- Mohammad Harith Haikal — US001: Register account; US002: Log in
- Md Tamim Shams — US005: Identify task obstacles; US009: Request task hint

Sprint 2 (AI Agent) items are listed separately in the architecture document and are not Sprint 1 scope.

## Important
- The PHP API is the only component that accesses MySQL; browser code must never connect directly to the database.
- Firebase Authentication establishes identity. The API must verify the Firebase ID token before accessing a user's MySQL records.
- Confirm Sprint 1 behavior for US005 and US009: both are assigned to Sprint 1, while AI Agent work is identified as Sprint 2.
- Empty source files are signposts for future work, not runnable application code.
