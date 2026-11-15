const express = require('express');
const bcrypt = require('bcryptjs');
const pool = require('../db');
const { createSession, requireAuth } = require('../middleware');
const { validateRegister, validateLogin, MSG, str } = require('../validate');

const router = express.Router();

// Used when the email does not exist, so a wrong email and a wrong password
// take the same time and give the same message (E1 of US002).
const DUMMY_HASH = bcrypt.hashSync('not-a-real-password', 10);

const publicUser = (u) => ({ id: u.id, name: u.name, email: u.email, xp: u.xp });

/* ------------------------------------------------------------------ */
/* US001  POST /api/register   { name, email, password }               */
/* ------------------------------------------------------------------ */
router.post('/register', async (req, res) => {
  const name = str(req.body.name).trim();
  const email = str(req.body.email).trim().toLowerCase();
  const password = str(req.body.password);

  // E2: invalid email format or short password -> field-level messages
  const errors = validateRegister({ name, email, password });
  if (Object.keys(errors).length) return res.status(400).json({ errors });

  try {
    // E1: email already registered ("Find user by email")
    const [found] = await pool.query('SELECT id FROM users WHERE email = ?', [email]);
    if (found.length) return res.status(409).json({ errors: { em: MSG.emailInUse } });

    // "Create account with hashed password"
    const hash = await bcrypt.hash(password, 10);
    let userId;
    try {
      const [r] = await pool.query(
        'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)',
        [name, email, hash]
      );
      userId = r.insertId;
    } catch (e) {
      // two people registering the same email at the same moment
      if (e.code === 'ER_DUP_ENTRY') return res.status(409).json({ errors: { em: MSG.emailInUse } });
      throw e;
    }

    // "Session token" -> student is logged in, home screen with empty subject list
    const token = await createSession(userId);
    res.status(201).json({
      token,
      user: { id: userId, name, email, xp: 0 },
      subjects: [],
    });
  } catch (err) {
    // E3: account creation fails -> client shows the error and keeps the entered data
    console.error('register failed:', err);
    res.status(500).json({ error: 'We could not create your account. Please try again.' });
  }
});

/* ------------------------------------------------------------------ */
/* US002  POST /api/login      { email, password }                     */
/* ------------------------------------------------------------------ */
router.post('/login', async (req, res) => {
  const email = str(req.body.email).trim().toLowerCase();
  const password = str(req.body.password);

  // E2: a field is empty -> highlight the empty field
  const errors = validateLogin({ email, password });
  if (Object.keys(errors).length) return res.status(400).json({ errors });

  try {
    const [rows] = await pool.query(
      'SELECT id, name, email, xp, password_hash FROM users WHERE email = ?',
      [email]
    );
    const user = rows[0];
    const ok = await bcrypt.compare(password, user ? user.password_hash : DUMMY_HASH);

    // E1: wrong email or password -> same message either way, stay on login screen
    if (!user || !ok) return res.status(401).json({ error: 'Email or password is incorrect.' });

    // tidy up this student's old expired sessions, then issue a new token
    await pool.query('DELETE FROM sessions WHERE user_id = ? AND expires_at <= NOW()', [user.id]);
    const token = await createSession(user.id);
    res.json({ token, user: publicUser(user) });
  } catch (err) {
    console.error('login failed:', err);
    res.status(500).json({ error: 'We could not log you in right now. Please try again.' });
  }
});

/* ------------------------------------------------------------------ */
/* US002  A1 Log out           POST /api/logout  (Bearer token)        */
/* ------------------------------------------------------------------ */
router.post('/logout', requireAuth, async (req, res) => {
  try {
    await pool.query('DELETE FROM sessions WHERE token_hash = ?', [req.tokenHash]); // "Session ended"
    res.json({ ok: true });
  } catch (err) {
    console.error('logout failed:', err);
    res.status(500).json({ error: 'We could not log you out. Please try again.' });
  }
});

/* Restores the logged-in session after a page refresh */
router.get('/me', requireAuth, (req, res) => res.json({ user: publicUser(req.user) }));

module.exports = router;
