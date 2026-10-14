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
