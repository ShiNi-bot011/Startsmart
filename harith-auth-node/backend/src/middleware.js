const crypto = require('crypto');
const pool = require('./db');

const SESSION_DAYS = Number(process.env.SESSION_DAYS || 7);
const sha256 = (t) => crypto.createHash('sha256').update(t).digest('hex');

// Creates a session and returns the raw token (only its hash is stored)
async function createSession(userId) {
  const token = crypto.randomBytes(32).toString('hex');
  await pool.query(
    'INSERT INTO sessions (token_hash, user_id, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))',
    [sha256(token), userId, SESSION_DAYS]
  );
  return token;
}

// Protects routes: expects header  Authorization: Bearer <token>
async function requireAuth(req, res, next) {
  try {
    const header = req.get('Authorization') || '';
    const token = header.startsWith('Bearer ') ? header.slice(7) : '';
    if (!token) return res.status(401).json({ error: 'Please log in.' });

    const hash = sha256(token);
    const [rows] = await pool.query(
      `SELECT u.id, u.name, u.email, u.xp
         FROM sessions s JOIN users u ON u.id = s.user_id
        WHERE s.token_hash = ? AND s.expires_at > NOW()`,
      [hash]
    );
    if (!rows.length) return res.status(401).json({ error: 'Your session has ended. Please log in again.' });

    req.user = rows[0];
    req.tokenHash = hash;
    next();
  } catch (err) {
    next(err);
  }
}

module.exports = { createSession, requireAuth };
