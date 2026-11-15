// Talks to the server (App -> Server in the sequence diagrams).
const TOKEN_KEY = 'startsmart_token';

export const getToken = () => localStorage.getItem(TOKEN_KEY);
export const setToken = (t) => (t ? localStorage.setItem(TOKEN_KEY, t) : localStorage.removeItem(TOKEN_KEY));

async function request(path, { method = 'GET', body } = {}) {
  const headers = {};
  if (body) headers['Content-Type'] = 'application/json';
  const token = getToken();
  if (token) headers.Authorization = 'Bearer ' + token;

  let res;
  try {
    res = await fetch('/api' + path, { method, headers, body: body ? JSON.stringify(body) : undefined });
  } catch {
    return { ok: false, status: 0, data: { error: 'Cannot reach the server. Check your connection and try again.' } };
  }
  let data = {};
  try { data = await res.json(); } catch { /* empty or non-JSON body */ }
  return { ok: res.ok, status: res.status, data };
}

export const api = {
  register: (body) => request('/register', { method: 'POST', body }), // US001
  login:    (body) => request('/login',    { method: 'POST', body }), // US002
  logout:   ()     => request('/logout',   { method: 'POST' }),       // US002 A1
  me:       ()     => request('/me'),                                 // restore session on refresh
  subjects: ()     => request('/subjects'),                           // US002 getSubjects(token)
};
