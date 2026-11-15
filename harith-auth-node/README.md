# US001 Register + US002 Log in (Node.js + MySQL version)

Owner: Mohammad Harith Haikal

```
harith-auth-node/
├─ frontend/                 what the student sees (HTML + JavaScript, no build step)
│  ├─ index.html
│  ├─ css/style.css
│  └─ js/
│     ├─ app.js              screens: register, log in, home, log out
│     ├─ api.js              all calls to the backend (adds the login token)
│     └─ validate.js         instant form checks
├─ backend/                  the server (Node.js + Express)
│  ├─ server.js              starts the server, serves ../frontend
│  ├─ src/
│  │  ├─ db.js               MySQL connection
│  │  ├─ validate.js         server-side validation
│  │  ├─ middleware.js       sessions + login check
│  │  └─ routes/auth.js, subjects.js
│  ├─ scripts/seed.js        demo student
│  ├─ package.json
│  └─ .env.example
└─ database/
   └─ schema.sql             tables: users, sessions, subjects
```

The frontend only talks to the backend through `frontend/js/api.js` (URLs starting with `/api`).
The backend is the only part that touches MySQL.

## Setup (first time)

1. Install **Node.js LTS** and **XAMPP** (start MySQL), or MySQL Server.
2. Create the tables: import `database/schema.sql` in phpMyAdmin (Import tab),
   or from the project folder run `mysql -u root -p -e "source database/schema.sql"`.
3. Open a terminal **in the `backend` folder**:
   ```
   cd backend
   copy .env.example .env      (Mac/Linux: cp .env.example .env)
   ```
   Edit `.env`: set `DB_USER` and `DB_PASSWORD` (XAMPP: root and empty).
4. Install and run (still in `backend`):
   ```
   npm install
   npm run seed
   npm start
   ```
5. Open http://localhost:3000

All npm commands are run inside `backend/`. If PowerShell blocks npm, use Command Prompt.

## Test checklist
1. Register with a new email, you reach the home screen with "No subjects yet."
2. Register with empty name, email `abc`, password `123`: each field shows its rule (E2).
3. Register the same email again: "This email is already in use." (E1)
4. Log in as `aina@student.utm.my` / `studysmart`: 3 subjects shown.
5. Log in with an empty password: that field is highlighted (E2).
6. Log in with a wrong password: "Email or password is incorrect." (E1)
7. Log out returns to the login screen (A1). Refresh while logged in keeps you logged in.

## Notes for the team
- This version stores password hashes in MySQL and uses its own session tokens.
  The team architecture document plans Firebase Authentication + a PHP API instead.
  Treat this folder as a working reference until the team decides.
- Never commit `backend/.env` (it holds the database password). `backend/.gitignore` already excludes it.
