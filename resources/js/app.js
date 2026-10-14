document.querySelectorAll('form').forEach(form => {
 form.addEventListener('submit', () => {
  if (!form.checkValidity()) return;
  const button = form.querySelector('button[type="submit"],button:not([type])');
  if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); }
 });
});
document.querySelector('[data-error-summary]')?.focus();
document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => window.print()));

const memberLinks = [...document.querySelectorAll('[data-member-nav]')];
if (memberLinks.length) {
 const setActiveMemberLink = () => {
  const section = location.hash.slice(1) || 'home';
  memberLinks.forEach(link => {
   if (link.dataset.memberNav === section) link.setAttribute('aria-current', section === 'home' ? 'page' : 'location');
   else link.removeAttribute('aria-current');
  });
  if (section === 'profile') document.querySelector('#profile')?.setAttribute('open', '');
 };
 memberLinks.forEach(link => link.addEventListener('click', event => {
  if (link.dataset.memberNav === 'home') {
   event.preventDefault();
   history.replaceState(null, '', location.pathname + location.search);
   window.scrollTo({ top: 0, behavior: 'instant' });
   setActiveMemberLink();
  } else if (link.dataset.memberNav === 'profile') {
   document.querySelector('#profile')?.setAttribute('open', '');
  }
 }));
 window.addEventListener('hashchange', setActiveMemberLink);
 setActiveMemberLink();
 document.addEventListener('focusin', event => {
  if (!event.target.matches('input, select, textarea')) return;
  const nav = document.querySelector('.bottom-nav');
  if (!nav || getComputedStyle(nav).display === 'none') return;
  const overlap = event.target.getBoundingClientRect().bottom - nav.getBoundingClientRect().top;
  if (overlap >= 0) window.scrollBy({ top: overlap + 24, behavior: 'instant' });
 });
}

// Real member routes retain browser history, link behavior and server authorization.
const mobileDock = document.querySelector('[data-mobile-nav]');
if (mobileDock) {
 mobileDock.querySelectorAll('a').forEach(link => link.addEventListener('click', event => {
  if (!event.ctrlKey && !event.metaKey && !event.shiftKey && event.button === 0) link.setAttribute('data-pending', '');
 }));
 window.addEventListener('pageshow', () => mobileDock.querySelectorAll('[data-pending]').forEach(link => link.removeAttribute('data-pending')));
 document.addEventListener('focusin', event => {
  if (!(event.target instanceof HTMLElement) || !event.target.matches('input, select, textarea') || getComputedStyle(mobileDock).display === 'none') return;
  const overlap = event.target.getBoundingClientRect().bottom - mobileDock.getBoundingClientRect().top;
  if (overlap >= 0) window.scrollBy({ top: overlap + 24, behavior: 'instant' });
 });
}

const workspaceMore = document.querySelector('.workspace-more');
if (workspaceMore) {
 document.addEventListener('click', event => { if (!workspaceMore.contains(event.target)) workspaceMore.open = false; });
 document.addEventListener('keydown', event => { if (event.key === 'Escape' && workspaceMore.open) { workspaceMore.open = false; workspaceMore.querySelector('summary').focus(); } });
 window.matchMedia('(min-width: 640px)').addEventListener('change', event => { if (event.matches) workspaceMore.open = false; });
}

const registration = document.querySelector('[data-quick-registration]');
if (registration) {
 const plan = registration.querySelector('[name="plan_id"]');
 const status = registration.querySelector('[name="payment_status"]');
 const payment = registration.querySelector('[data-registration-payment]');
 const account = registration.querySelector('[name="create_account"]');
 const accountFields = registration.querySelector('[data-account-fields]');
 const syncRegistration = () => {
  const paid = status.value === 'paid';
  payment.hidden = !paid;
  payment.disabled = !paid;
  payment.querySelector('[name="confirmed"]').required = paid;
  plan.required = paid;
  accountFields.hidden = !account.checked;
  accountFields.querySelector('input').disabled = !account.checked;
  accountFields.querySelector('input').required = account.checked;
  registration.querySelector('[name="email"]').required = account.checked;
  const price = Number(plan.selectedOptions[0]?.dataset.price || 0);
  document.querySelector('[data-registration-total]').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(price);
  document.querySelector('[data-registration-note]').textContent = paid ? 'Pembayaran tercatat dan membership langsung aktif setelah disimpan.' : 'Anggota tersimpan. Membership aktif setelah pembayaran dikonfirmasi tim.';
 };
 registration.addEventListener('change', syncRegistration);
 syncRegistration();
}

const railToggle = document.querySelector('[data-rail-toggle]');
if (railToggle) {
 const rail = railToggle.closest('aside');
 const applyRailState = collapsed => {
  document.body.classList.toggle('is-sidebar-collapsed', collapsed);
  railToggle.setAttribute('aria-expanded', String(!collapsed));
  const label = collapsed ? 'Perluas sidebar' : 'Ringkas sidebar';
  railToggle.setAttribute('aria-label', label);
  railToggle.title = label;
 };
 rail.querySelectorAll('nav > a').forEach(link => {
  const label = link.textContent.trim();
  link.setAttribute('aria-label', label);
  link.title = label;
 });
 try { applyRailState(localStorage.getItem('sparta.sidebar.collapsed') === 'true'); } catch { applyRailState(false); }
 railToggle.addEventListener('click', () => {
  const collapsed = !document.body.classList.contains('is-sidebar-collapsed');
  applyRailState(collapsed);
  try { localStorage.setItem('sparta.sidebar.collapsed', String(collapsed)); } catch { /* Navigation still works without browser storage. */ }
 });
}

const userForm = document.querySelector('[data-user-form]');
if (userForm) {
 const role = userForm.querySelector('[name="role"]');
 const memberFields = userForm.querySelector('[data-user-member]');
 const trainerFields = userForm.querySelector('[data-user-trainer]');
 const syncUserRole = () => {
  memberFields.hidden = role.value !== 'member';
  memberFields.querySelector('select').disabled = role.value !== 'member';
  memberFields.querySelector('select').required = role.value === 'member';
  trainerFields.hidden = role.value !== 'personal_trainer';
  trainerFields.disabled = role.value !== 'personal_trainer';
 };
 role.addEventListener('change', syncUserRole);
 syncUserRole();
}
