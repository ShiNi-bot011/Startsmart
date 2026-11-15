const express = require('express');
const pool = require('../db');
const { requireAuth } = require('../middleware');

const router = express.Router();

// US002: "Get subjects of this student only"
// US003 (Manage subjects) will add POST / PUT / DELETE routes to this file.
router.get('/', requireAuth, async (req, res) => {
  try {
    const [rows] = await pool.query(
      'SELECT id, name FROM subjects WHERE user_id = ? ORDER BY position, id',
      [req.user.id]
    );
    res.json({ subjects: rows });
  } catch (err) {
    console.error('get subjects failed:', err);
    res.status(500).json({ error: 'We could not load your subjects.' });
  }
});

module.exports = router;
