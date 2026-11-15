import { api, getToken, setToken } from './api.js';
import { validateRegister, validateLogin } from './validate.js';

const $app = document.getElementById('app');

/* ---------- state (same idea as S in the prototype) ---------- */
const S = {
  view: 'loading',   // 'loading' | 'auth' | 'home'
  mode: 'register',  // 'register' | 'login'   (the two tabs)
  user: null,        // { id, name, email, xp }
  subs: [],          // this student's subjects
  subsError: false,
  f: {},             // form values kept on screen: n, em, pw
  e: {},             // errors: n, em, pw (per field) and top (whole form)
  busy: false,
  focus: null,       // id of the field to focus after the next render
};

const esc = (s) =>
  String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const lvl = (xp) => Math.floor(xp / 150) + 1;
const val = (id) => (document.getElementById(id)?.value ?? '').trim();

/* ---------- screens ---------- */
function vLoading() {
  return `<div class="pad"><p class="sm">Loading…</p></div>`;
}

// US001 Register / US002 Log in share one screen with two tabs, like the prototype
function vAuth() {
  const r = S.mode === 'register';
  const f = S.f;

  const field = (id, label, type, auto, hint = '') => `
    <div>
      <label for="${id}">${label}</label>
      <input id="${id}" name="${id}" type="${type}" class="in ${S.e[id] ? 'bad' : ''}"
             value="${esc(f[id])}" autocomplete="${auto}"
             ${S.e[id] ? `aria-invalid="true" aria-describedby="err-${id}"` : ''}>
      ${S.e[id] ? `<div class="err" id="err-${id}" role="alert">${esc(S.e[id])}</div>` : hint}
    </div>`;

  const label = S.busy ? (r ? 'Creating account…' : 'Logging in…') : (r ? 'Create account' : 'Log in');

  return `
  <div class="auth">
    <div class="brand">
      <div class="logo" aria-hidden="true">✓</div>
      <h1>StartSmart AI</h1>
      <p>From “I don’t know where to start” to “I know what to do next.”</p>
    </div>

    <div class="tabs">
      <button type="button" class="${r ? '' : 'on'}" data-a="mode" data-v="login" aria-pressed="${!r}">Log in</button>
      <button type="button" class="${r ? 'on' : ''}" data-a="mode" data-v="register" aria-pressed="${r}">Register</button>
    </div>

    <form id="authForm" class="fields" novalidate style="flex:1;display:flex;flex-direction:column;gap:24px">
      <div class="fields">
        ${r ? field('n', 'Name', 'text', 'name') : ''}
        ${field('em', 'Email', 'email', 'email')}
        ${field('pw', 'Password', 'password', r ? 'new-password' : 'current-password',
                r ? '<span class="xs">At least 8 characters</span>' : '')}
        ${S.e.top ? `<div class="note" role="alert">${esc(S.e.top)}</div>` : ''}
      </div>
      <div class="actions">
        <button class="btn" type="submit" ${S.busy ? 'disabled' : ''}>${label}</button>
        <p class="xs">Your subjects, checklists and XP are saved to your account.</p>
        ${r ? '' : '<p class="xs">Demo login: aina@student.utm.my / studysmart</p>'}
      </div>
    </form>
  </div>`;
}

// US001 step 4 / US002 "Show home screen with saved subjects"
function vHome() {
  const xp = S.user.xp || 0;
  const pc = ((xp % 150) / 150) * 100;
  const first = S.user.name.split(' ')[0];

  return `
  <div class="pad"><span class="sm">Hello, ${esc(first)}</span><h1>Your subjects</h1></div>

  <div class="xpcard">
    <div class="lv">${lvl(xp)}</div>
    <div class="xpcol">
      <div class="xprow"><span>Level ${lvl(xp)}</span><span>${xp} XP</span></div>
      <div class="xptrack"><i style="width:${pc}%"></i></div>
    </div>
  </div>

  <div class="col">
    ${S.subsError ? `<div class="note">We could not load your subjects. <button class="lnk" data-a="retry">Retry</button></div>` : ''}
    ${S.subs.map((s) => `<div class="card"><h3>${esc(s.name)}</h3></div>`).join('')}
    ${!S.subsError && !S.subs.length ? '<p class="xs empty">No subjects yet. Add your first course to begin.</p>' : ''}
    <!-- US003 (Manage subjects) adds the "+ Add subject" button and subject menu here -->
  </div>

  <div class="foot"><button class="btn ghost" data-a="logout">Log out</button></div>`;
}

function render() {
  $app.innerHTML = S.view === 'home' ? vHome() : S.view === 'auth' ? vAuth() : vLoading();
  if (S.focus) { document.getElementById(S.focus)?.focus(); S.focus = null; }
}

/* ---------- actions ---------- */
const firstInvalid = (errors) => ['n', 'em', 'pw'].find((k) => errors[k]) || null;

// US001 (Register) and US002 (Log in) both submit from the same form
async function submitAuth() {
  if (S.busy) return;
  const r = S.mode === 'register';
  S.f = { n: val('n'), em: val('em'), pw: document.getElementById('pw').value }; // do not trim the password
  S.e = {};

  // US001 E2 / US002 E2: check on the device first
  const errors = r ? validateRegister(S.f) : validateLogin(S.f);
  if (Object.keys(errors).length) {
    S.e = errors; S.focus = firstInvalid(errors); render(); return;
  }

  S.busy = true; render();
  const res = r
    ? await api.register({ name: S.f.n, email: S.f.em, password: S.f.pw })
    : await api.login({ email: S.f.em, password: S.f.pw });
  S.busy = false;

  if (!res.ok) {
    // field errors (E1 email in use, E2) or one message for the whole form (US002 E1, US001 E3).
    // S.f is kept, so the student does not retype anything.
    if (res.data.errors) { S.e = res.data.errors; S.focus = firstInvalid(res.data.errors); }
    else S.e = { top: res.data.error || 'Something went wrong. Please try again.' };
    render(); return;
  }

  setToken(res.data.token);
  S.user = res.data.user;

  if (r) {
    S.subs = res.data.subjects || [];   // new account: empty subject list
    S.subsError = false;
  } else {
    const sr = await api.subjects();    // US002: getSubjects(token) - this student's subjects only
    S.subs = sr.ok ? sr.data.subjects : [];
    S.subsError = !sr.ok;
  }
  S.f = {}; S.e = {}; S.view = 'home'; render();
}

function endSession(message) {
  setToken(null);
  Object.assign(S, { user: null, subs: [], subsError: false, f: {}, e: message ? { top: message } : {}, mode: 'login', view: 'auth' });
  render();
}

// US002 A1: Log out
async function logout() {
  await api.logout();   // server deletes the session; we log out locally even if this call fails
  endSession();
}

async function loadSubjects() {
  const res = await api.subjects();
  if (res.status === 401) return endSession('Your session has ended. Please log in again.');
  S.subs = res.ok ? res.data.subjects : [];
  S.subsError = !res.ok;
  render();
}

/* ---------- events ---------- */
$app.addEventListener('click', (e) => {
  const el = e.target.closest('[data-a]');
  if (!el) return;
  const a = el.dataset.a;
  if (a === 'mode') { S.mode = el.dataset.v; S.e = {}; S.f = {}; S.focus = S.mode === 'register' ? 'n' : 'em'; render(); }
  else if (a === 'logout') logout();
  else if (a === 'retry') loadSubjects();
});

$app.addEventListener('submit', (e) => {
  e.preventDefault();   // Enter key works because inputs live inside a <form>
  if (e.target.id === 'authForm') submitAuth();
});

/* ---------- start: restore a saved session if there is one ---------- */
(async function init() {
  if (!getToken()) { S.view = 'auth'; render(); return; }
  render();                                   // "Loading…"
  const me = await api.me();
  if (me.ok) {
    S.user = me.data.user;
    const sr = await api.subjects();
    S.subs = sr.ok ? sr.data.subjects : [];
    S.subsError = !sr.ok;
    S.view = 'home';
  } else {
    if (me.status === 401) setToken(null);    // expired or ended session
    S.mode = 'login'; S.view = 'auth';
    S.e = me.status === 401 ? {} : { top: me.data.error };
  }
  render();
})();
