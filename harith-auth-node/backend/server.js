require('dotenv').config();           // must run before ./src/db is loaded
const path = require('path');
const express = require('express');
const pool = require('./src/db');

const app = express();
app.disable('x-powered-by');
app.use(express.json({ limit: '10kb' }));

// Never cache API answers (login state must always be fresh)
app.use('/api', (req, res, next) => { res.set('Cache-Control', 'no-store'); next(); });

app.use('/api', require('./src/routes/auth'));              // US001, US002
app.use('/api/subjects', require('./src/routes/subjects')); // list only, used by US002
app.use('/api', (req, res) => res.status(404).json({ error: 'Not found.' }));

// Frontend (HTML + JS) lives in ../frontend and is served from here
app.use(express.static(path.join(__dirname, '..', 'frontend')));

// Last-resort error handler
app.use((err, req, res, next) => {
  if (err.type === 'entity.parse.failed') return res.status(400).json({ error: 'Bad request.' });
  console.error(err);
  res.status(500).json({ error: 'Something went wrong on our side. Please try again.' });
});

const port = process.env.PORT || 3000;
app.listen(port, async () => {
  console.log(`StartSmart AI running at http://localhost:${port}`);
  try {
    await pool.query('SELECT 1');
    console.log('Database connected.');
  } catch (e) {
    console.error('Database NOT connected:', e.code || e.message);
    console.error('Check DB_HOST / DB_USER / DB_PASSWORD / DB_NAME in your .env file and that MySQL is running.');
  }
});
