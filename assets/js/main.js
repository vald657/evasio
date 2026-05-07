// =============================================
//  ÉVASIO — JavaScript Global
// =============================================

// --- TOASTS ---------------------------------------------------------
function showToast(type, message, duration = 4000) {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const icons = {
    success: 'fa-check-circle',
    error:   'fa-times-circle',
    warning: 'fa-exclamation-triangle',
    info:    'fa-info-circle'
  };

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `
    <i class="fas ${icons[type] || icons.info} toast-icon"></i>
    <span style="flex:1">${message}</span>
    <button class="toast-close" onclick="this.parentElement.remove()">
      <i class="fas fa-times"></i>
    </button>
  `;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = 'slideInRight 0.3s ease reverse forwards';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

// --- MODALS ---------------------------------------------------------
function fermerModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('active');
}

// Fermer modal en cliquant sur l'overlay
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('active');
  }
});

// Fermer modal avec Échap
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
  }
});

// --- SIDEBAR MOBILE -------------------------------------------------
document.addEventListener('DOMContentLoaded', function() {
  const menuBtn = document.getElementById('menuToggle');
  const sidebar  = document.getElementById('sidebar');
  const overlay  = document.getElementById('sidebarOverlay');

  function openSidebar()  {
    sidebar?.classList.add('open');
    overlay?.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('active');
    document.body.style.overflow = '';
  }

  menuBtn?.addEventListener('click', () =>
    sidebar?.classList.contains('open') ? closeSidebar() : openSidebar()
  );
  overlay?.addEventListener('click', closeSidebar);

  // Fermer sidebar si on clique un lien (mobile)
  sidebar?.querySelectorAll('.nav-item').forEach(link => {
    link.addEventListener('click', () => { if (window.innerWidth <= 900) closeSidebar(); });
  });

  // Afficher/masquer le bouton hamburger selon la largeur
  function checkViewport() {
    if (window.innerWidth <= 900) {
      menuBtn && (menuBtn.style.display = 'flex');
    } else {
      menuBtn && (menuBtn.style.display = 'none');
      closeSidebar();
    }
  }
  checkViewport();
  window.addEventListener('resize', checkViewport);

  // Marquer lien actif dans sidebar
  const currentPath = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-item').forEach(item => {
    const href = item.getAttribute('href');
    if (href && href.includes(currentPath) && currentPath !== '') {
      item.classList.add('active');
    }
  });

  // Auto-dismiss alertes après 6 secondes
  document.querySelectorAll('.alert').forEach(alert => {
    if (!alert.classList.contains('alert-error')) {
      setTimeout(() => {
        alert.style.transition = 'opacity 0.4s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 400);
      }, 6000);
    }
  });

  // Tooltips simples sur les boutons btn-icon
  document.querySelectorAll('[title]').forEach(el => {
    el.addEventListener('mouseenter', showTooltip);
    el.addEventListener('mouseleave', hideTooltip);
  });
});

// --- TOOLTIPS -------------------------------------------------------
let tooltipEl = null;
function showTooltip(e) {
  const title = this.getAttribute('title');
  if (!title) return;
  this._title = title;
  this.removeAttribute('title');

  tooltipEl = document.createElement('div');
  tooltipEl.textContent = title;
  tooltipEl.style.cssText = `
    position:fixed; background:#1b1b23; color:white; font-size:12px;
    padding:5px 10px; border-radius:6px; pointer-events:none; z-index:9999;
    white-space:nowrap; font-family:var(--font-body);
  `;
  document.body.appendChild(tooltipEl);
  positionTooltip(e);
}

function positionTooltip(e) {
  if (!tooltipEl) return;
  tooltipEl.style.left = (e.clientX + 10) + 'px';
  tooltipEl.style.top  = (e.clientY - 30) + 'px';
}

function hideTooltip() {
  if (tooltipEl) { tooltipEl.remove(); tooltipEl = null; }
  if (this._title) { this.setAttribute('title', this._title); delete this._title; }
}

// --- CONFIRMATION GÉNÉRIQUE ----------------------------------------
function confirmer(message, callback) {
  if (confirm(message)) callback();
}

// --- FORMAT DATE FR -------------------------------------------------
function formatDateFr(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleDateString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric' });
}

// --- CALCUL JOURS OUVRES -------------------------------------------
function calculerJoursOuvres(debut, fin) {
  let d = new Date(debut), f = new Date(fin), jours = 0;
  while (d <= f) {
    const dow = d.getDay();
    if (dow !== 0 && dow !== 6) jours++;
    d.setDate(d.getDate() + 1);
  }
  return jours;
}

// --- DEBOUNCE -------------------------------------------------------
function debounce(fn, delay) {
  let timer;
  return function(...args) {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(this, args), delay);
  };
}

// --- RECHERCHE LIVE -------------------------------------------------
const searchInputs = document.querySelectorAll('[data-search-table]');
searchInputs.forEach(input => {
  input.addEventListener('input', debounce(function() {
    const tableId = this.dataset.searchTable;
    const table   = document.getElementById(tableId);
    if (!table) return;
    const term = this.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
  }, 300));
});

// --- EXPORT CONFIRMATION -------------------------------------------
document.querySelectorAll('[data-export]').forEach(btn => {
  btn.addEventListener('click', function(e) {
    const format = this.dataset.export;
    if (!confirm(`Exporter les données en ${format.toUpperCase()} ?`)) e.preventDefault();
  });
});
