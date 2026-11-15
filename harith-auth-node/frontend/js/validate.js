// Client-side checks so the student gets instant feedback (the server checks again).
// Keep these in sync with src/validate.js
const EMAIL_RE = /^\S+@\S+\.\S+$/;

// US001 E2: invalid email format or short password
export function validateRegister({ n, em, pw }) {
  const e = {};
  if (!n) e.n = 'Enter your name.';
  else if (n.length > 100) e.n = 'Use 100 characters or fewer.';
  if (!EMAIL_RE.test(em) || em.length > 255) e.em = 'Enter a valid email, like name@student.utm.my.';
  if (pw.length < 8) e.pw = 'Use at least 8 characters.';
  else if (new TextEncoder().encode(pw).length > 72) e.pw = 'Use 72 characters or fewer.';
  return e;
}

// US002 E2: a field is empty
export function validateLogin({ em, pw }) {
  const e = {};
  if (!em) e.em = 'Enter your email.';
  if (!pw) e.pw = 'Enter your password.';
  return e;
}
