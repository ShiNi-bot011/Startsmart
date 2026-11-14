# GitHub Setup Checklist

Use one shared GitHub repository for the complete StartSmart application. The local workspace already has Git initialized; it does not yet have a configured GitHub remote.

## Create the shared repository

- [ ] Create one repository in the team's GitHub organization/account.
- [ ] Suggested name: `startsmart-app`.
- [ ] Choose private/public visibility with the team.
- [ ] Create it empty; do not generate a second README or license because this workspace already has project documentation.
- [ ] Add the GitHub remote to this existing local repository and push the current planning scaffold.
- [ ] Confirm every member can clone, create a branch, and open a pull request.

## Branch and pull request workflow

- [ ] Keep `main` as the stable branch.
- [ ] Create `sprint-1` as the integration branch for the upcoming sprint.
- [ ] Create one short-lived feature branch per assigned use case, for example `feat/us004-create-academic-task`.
- [ ] Open pull requests from feature branches into `sprint-1`; merge `sprint-1` into `main` after the sprint review.
- [ ] Require at least one teammate review; have each owner avoid editing another owner's feature files without agreement.
- [ ] Keep pull requests small and describe the use case, acceptance criteria, screenshots or test evidence, and any API/schema changes.
- [ ] Avoid committing directly to the shared integration branches.

## Before implementation begins

- [ ] Read `docs/SPRINT_1_ARCHITECTURE.md` and confirm member assignments.
- [ ] Agree the PHP version, API routing approach, and Firebase ID-token verification approach.
- [ ] Agree request/response and error contracts in `team/API_CONTRACT.md`.
- [ ] Agree the MySQL schema and migration naming with Md Tamim before feature code depends on tables.
- [ ] Resolve whether US005 and US009 deliver AI-generated behavior in Sprint 1 or defer that part to Sprint 2.
- [ ] Add and review ignore rules for local environment files and dependencies before adding credentials or packages.
- [ ] Store runtime secrets outside the repository. Never commit MySQL passwords, Firebase service-account keys, or private credentials.

## Suggested ownership boundaries

- Joyce: US004 task-creation flow and task API contract.
- Lai: US003 subject flow and subject API contract.
- Mohammad: US001/US002 authentication UI, Firebase browser setup, and shared API client.
- Md Tamim: MySQL schema, data ownership, and US005/US009 data/API requirements.

The API contract and database schema are shared integration surfaces; review changes to them before other feature branches depend on them.
