// Optional: creates the prototype's demo student so you can test Log in quickly.
//   npm run seed      ->  aina@student.utm.my / studysmart  (with 3 subjects)
require('dotenv').config();
const bcrypt = require('bcryptjs');
const pool = require('../src/db');

(async () => {
  try {
    const email = 'aina@student.utm.my';
    const [found] = await pool.query('SELECT id FROM users WHERE email = ?', [email]);
    if (found.length) {
      console.log('Demo student already exists. Nothing to do.');
    } else {
      const hash = await bcrypt.hash('studysmart', 10);
      const [r] = await pool.query(
        'INSERT INTO users (name, email, password_hash, xp) VALUES (?, ?, ?, ?)',
        ['Aina Rahman', email, hash, 420]
      );
      const names = ['Applications Development', 'Data Structures', 'Database Systems'];
      for (let i = 0; i < names.length; i++) {
        await pool.query('INSERT INTO subjects (user_id, name, position) VALUES (?, ?, ?)', [r.insertId, names[i], i]);
      }
      console.log('Demo student created: aina@student.utm.my / studysmart');
    }
  } catch (e) {
    console.error('Seed failed:', e.code || e.message);
    process.exitCode = 1;
  } finally {
    await pool.end();
  }
})();
