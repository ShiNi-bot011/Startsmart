// Validation rules from the SRS. Keep these in sync with public/js/validate.js
const EMAIL_RE = /^\S+@\S+\.\S+$/;

const MSG = {
  name: 'Enter your name.',
  email: 'Enter a valid email, like name@student.utm.my.',
  pwShort: 'Use at least 8 characters.',
  pwLong: 'Use 72 characters or fewer.',   // bcrypt only reads the first 72 bytes
  emailInUse: 'This email is already in use. Try logging in instead.',
  loginEmail: 'Enter your email.',
  loginPw: 'Enter your password.',
};

// US001 acceptance criteria: valid email format, password at least 8 characters (E2)
function validateRegister({ name, email, password }) {
  const e = {};
  if (!name) e.n = MSG.name;
  else if (name.length > 100) e.n = 'Use 100 characters or fewer.';
  if (!EMAIL_RE.test(email) || email.length > 255) e.em = MSG.email;
  if (password.length < 8) e.pw = MSG.pwShort;
  else if (Buffer.byteLength(password) > 72) e.pw = MSG.pwLong;
  return e;
}

// US002 exception E2: a field is empty
function validateLogin({ email, password }) {
  const e = {};
  if (!email) e.em = MSG.loginEmail;
  if (!password) e.pw = MSG.loginPw;
  return e;
}

const str = (v) => (typeof v === 'string' ? v : '');

module.exports = { validateRegister, validateLogin, MSG, str };
