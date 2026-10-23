const scanner = document.querySelector('[data-barcode-scanner]');
if (scanner) {
 const start = scanner.querySelector('[data-scan-start]');
 const stop = scanner.querySelector('[data-scan-stop]');
 const video = scanner.querySelector('video');
 const feedback = scanner.querySelector('[data-scan-feedback]');
 let stream, running = false, generation = 0;
 const close = () => {
  generation++; running = false;
  stream?.getTracks().forEach(track => track.stop());
  stream = null; video.srcObject = null; video.hidden = true; stop.hidden = true; start.disabled = false;
 };
 stop.addEventListener('click', close);
 window.addEventListener('pagehide', close);
 document.addEventListener('visibilitychange', () => { if (document.hidden) close(); });
 start.addEventListener('click', async () => {
  start.disabled = true;
  const attempt = ++generation;
  try {
   if (!window.BarcodeDetector || !navigator.mediaDevices?.getUserMedia || !(await BarcodeDetector.getSupportedFormats()).includes('qr_code')) throw new Error('unsupported');
   const detector = new BarcodeDetector({formats:['qr_code']});
   const acquired = await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'}},audio:false});
   if (attempt !== generation) { acquired.getTracks().forEach(track => track.stop()); return; }
   stream = acquired; video.srcObject = stream; video.hidden = false; stop.hidden = false;
   await video.play();
   if (attempt !== generation) return;
   running = true; feedback.textContent = 'Arahkan kamera ke QR Code kartu anggota.';
   const detect = async () => {
    if (!running || attempt !== generation) return;
    try {
     const codes = await detector.detect(video);
     const code = codes.find(item => /^[0-9]{12,20}$/.test(item.rawValue));
     if (code && running && attempt === generation) {
      document.querySelector('.scan-search [name="q"]').value = code.rawValue;
      close(); document.querySelector('.scan-search').requestSubmit(); return;
     }
    } catch { feedback.textContent = 'QR Code belum terbaca. Dekatkan kartu atau gunakan pencarian manual.'; }
    if (running && attempt === generation) setTimeout(detect, 250);
   };
   detect();
  } catch (error) {
   if (attempt !== generation) return;
   close(); feedback.textContent = error.message === 'unsupported' ? 'Browser belum mendukung scan QR Code. Gunakan scanner USB atau ketik kode kartu.' : 'Kamera tidak dapat dibuka. Periksa izin kamera atau gunakan pencarian manual.';
  }
 });
}

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

document.querySelectorAll('[data-attendance-camera]').forEach(widget => {
 const video = widget.querySelector('video');
 const preview = widget.querySelector('[data-photo-preview]');
 const input = widget.querySelector('input[type=file]');
 const start = widget.querySelector('[data-photo-start]');
 const capture = widget.querySelector('[data-photo-capture]');
 const stop = widget.querySelector('[data-photo-stop]');
 const feedback = widget.querySelector('[data-photo-feedback]');
 let stream, generation = 0, previewUrl;
 const close = () => {
  generation++;
  stream?.getTracks().forEach(track => track.stop());
  stream = null; video.srcObject = null; video.hidden = true;
  stop.hidden = true; capture.hidden = true; start.disabled = false;
 };
 const showPreview = file => {
  if (previewUrl) URL.revokeObjectURL(previewUrl);
  if (!file) { preview.hidden = true; return; }
  previewUrl = URL.createObjectURL(file);
  preview.src = previewUrl; preview.hidden = false;
  feedback.textContent = 'Foto siap dikirim. Waktu absensi dan cap foto ditentukan server.';
 };
 input.addEventListener('change', () => { close(); showPreview(input.files[0]); });
 stop.addEventListener('click', close);
 window.addEventListener('pagehide', close);
 document.addEventListener('visibilitychange', () => { if (document.hidden) close(); });
 start.addEventListener('click', async () => {
  close(); input.value = ''; showPreview(null);
  const attempt = generation;
  start.disabled = true;
  try {
   if (!navigator.mediaDevices?.getUserMedia) throw new Error('unavailable');
   const acquired = await navigator.mediaDevices.getUserMedia({video:{facingMode:'user',width:{ideal:1280},height:{ideal:720}},audio:false});
   if (attempt !== generation) { acquired.getTracks().forEach(track => track.stop()); return; }
   stream = acquired; video.srcObject = stream; video.hidden = false;
   await video.play();
   if (attempt !== generation) return;
   capture.hidden = false; stop.hidden = false;
   feedback.textContent = 'Arahkan kamera ke wajah, lalu tekan Ambil foto.';
  } catch {
   close(); feedback.textContent = 'Kamera tidak tersedia atau izin ditolak. Gunakan pilihan foto, atau hubungi management untuk pencatatan manual.';
  }
 });
 capture.addEventListener('click', () => {
  if (!video.videoWidth || !stream) return;
  const attempt = generation;
  const canvas = document.createElement('canvas');
  canvas.width = video.videoWidth; canvas.height = video.videoHeight;
  canvas.getContext('2d').drawImage(video, 0, 0);
  canvas.toBlob(blob => {
   if (!blob || attempt !== generation) return;
   const transfer = new DataTransfer();
   transfer.items.add(new File([blob], 'attendance.jpg', {type:'image/jpeg'}));
   input.files = transfer.files;
   close(); showPreview(input.files[0]);
  }, 'image/jpeg', 0.88);
 });
});

document.querySelectorAll('[data-progress-exercises]').forEach(widget => {
 const rows = widget.querySelector('[data-exercise-rows]');
 const add = widget.querySelector('[data-exercise-add]');
 const status = widget.querySelector('[data-exercise-status]');
 const sync = () => { add.disabled = rows.children.length >= 20; };
 add.addEventListener('click', () => {
  const index = rows.children.length;
  if (index >= 20) return;
  const row = rows.firstElementChild.cloneNode(true);
  row.querySelector('legend').textContent = `Gerakan ${index + 1}`;
  row.querySelectorAll('[role="alert"]').forEach(error => error.remove());
  row.querySelectorAll('input').forEach(input => {
   const key = input.name.match(/\[([^\]]+)\]$/)[1];
   const id = `exercise_${index}_${key}`;
   input.closest('.field').querySelector('label').htmlFor = id;
   input.id = id; input.name = `exercises[${index}][${key}]`; input.value = '';
  });
  rows.append(row); row.querySelector('input').focus();
  status.textContent = `${index + 1} baris gerakan tersedia, maksimal 20.`;
  sync();
 });
 sync();
});
