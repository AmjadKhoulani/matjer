// SaaS Central Dashboard Logic (Super Admin and Merchant Account) - Database Driven

let activePerspective = 'super-admin';
let activeSaaSTab = 'overview'; // Default tab

// Seeding Fallback
const INITIAL_STORES = [
  { id: '1', name: 'المتجر النموذجي', owner: 'عبد العزيز الحربي', plan: 'Pro', registeredDate: '2026-04-18', bill: 79, status: 'Active', slug: 'demo' },
  { id: '2', name: 'بقالة النخبة الغذائية', owner: 'محمد عبد الله الشمراني', plan: 'Starter', registeredDate: '2026-05-10', bill: 29, status: 'Active', slug: 'al-nokhbah' }
];

let stores = [];

document.addEventListener('DOMContentLoaded', async () => {
  // Load stores dynamically from DB
  await loadSaaSStores();
  initSaaSDashboard();
});

async function loadSaaSStores() {
  try {
    const res = await fetch('api/tenants.php?action=list');
    if (res.ok) {
      const dbTenants = await res.json();
      stores = dbTenants.map(t => ({
        id: t.id.toString(),
        name: t.name,
        owner: t.owner_name,
        plan: t.plan,
        registeredDate: t.created_at.split(' ')[0],
        bill: t.status === 'Suspended' ? 0 : (t.plan === 'Starter' ? 29 : t.plan === 'Pro' ? 79 : 199),
        status: t.status,
        slug: t.slug
      }));
      localStorage.setItem('ns_saas_stores', JSON.stringify(stores));
    } else {
      throw new Error('Non-ok response');
    }
  } catch (err) {
    console.warn('Failed fetching tenants from DB API. Using cached/seeded stores.', err);
    const stored = localStorage.getItem('ns_saas_stores');
    stores = stored ? JSON.parse(stored) : INITIAL_STORES;
  }
}

function initSaaSDashboard() {
  initTheme();
  
  const selector = document.getElementById('perspective-selector');
  selector.addEventListener('change', (e) => {
    switchPerspective(e.target.value);
  });

  switchPerspective(activePerspective);
}

function initTheme() {
  const theme = localStorage.getItem('ns_theme') || 'light';
  document.documentElement.setAttribute('data-theme', theme);
  updateThemeIcon(theme);
  
  document.getElementById('btn-theme-toggle').addEventListener('click', () => {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('ns_theme', newTheme);
    updateThemeIcon(newTheme);
    
    if (activePerspective === 'super-admin') {
      renderSaaSCharts();
    }
  });
}

function updateThemeIcon(theme) {
  const icon = document.querySelector('#btn-theme-toggle i');
  if (icon) {
    if (theme === 'dark') {
      icon.className = 'fas fa-sun';
    } else {
      icon.className = 'fas fa-moon';
    }
  }
}

async function switchPerspective(type) {
  activePerspective = type;
  
  const sidebarUserAvatar = document.getElementById('sidebar-user-avatar');
  const sidebarUserName = document.getElementById('sidebar-user-name');
  const sidebarUserRole = document.getElementById('sidebar-user-role');
  
  const superAdminView = document.getElementById('view-super-admin');
  const merchantView = document.getElementById('view-merchant');

  if (type === 'super-admin') {
    sidebarUserAvatar.innerText = 'SA';
    sidebarUserName.innerText = 'سوبر أدمن';
    sidebarUserRole.innerText = 'الوصول الكامل للمنصة';

    superAdminView.style.display = 'block';
    merchantView.style.display = 'none';

    activeSaaSTab = 'overview';
    renderSuperAdminSidebar();
    switchTab('overview');
    
    await loadSaaSStores();
    renderStoresTable();
    renderSaaSCharts();
    setupSuperAdminSearch();

  } else {
    sidebarUserAvatar.innerText = 'متجر';
    sidebarUserName.innerText = 'المتجر النموذجي';
    sidebarUserRole.innerText = 'باقة احترافية (Pro)';

    superAdminView.style.display = 'none';
    merchantView.style.display = 'block';

    activeSaaSTab = 'merchant-overview';
    renderMerchantSidebar();
    switchTab('merchant-overview');
  }
}

function renderSuperAdminSidebar() {
  const menu = document.getElementById('saas-sidebar-menu');
  menu.innerHTML = `
    <li class="menu-item active" data-tab="overview">
      <a class="menu-link">
        <i class="fas fa-th-large"></i>
        <span>نظرة عامة (SaaS)</span>
      </a>
    </li>
    <li class="menu-item" data-tab="stores">
      <a class="menu-link">
        <i class="fas fa-store"></i>
        <span>إدارة المتاجر</span>
      </a>
    </li>
    <li class="menu-item" data-tab="settings">
      <a class="menu-link">
        <i class="fas fa-sliders-h"></i>
        <span>إعدادات المنصة</span>
      </a>
    </li>
  `;
  bindSidebarTabs();
}

function renderMerchantSidebar() {
  const menu = document.getElementById('saas-sidebar-menu');
  menu.innerHTML = `
    <li class="menu-item active" data-tab="merchant-overview">
      <a class="menu-link">
        <i class="fas fa-crown"></i>
        <span>اشتراكي بالمنصة</span>
      </a>
    </li>
    <li class="menu-item" data-tab="merchant-billing">
      <a class="menu-link">
        <i class="fas fa-file-invoice-dollar"></i>
        <span>الفواتير والدفع</span>
      </a>
    </li>
    <li class="menu-item">
      <a href="store-manager.php?tenant=demo" class="menu-link" style="color: hsla(var(--primary), 1); font-weight: 700;">
        <i class="fas fa-external-link-alt"></i>
        <span>تشغيل مستودع المتجر</span>
      </a>
    </li>
  `;
  bindSidebarTabs();
}

function bindSidebarTabs() {
  const links = document.querySelectorAll('.sidebar-menu .menu-item');
  links.forEach(link => {
    const tabName = link.getAttribute('data-tab');
    if (!tabName) return;
    
    link.addEventListener('click', (e) => {
      e.preventDefault();
      links.forEach(l => l.classList.remove('active'));
      link.classList.add('active');
      switchTab(tabName);
    });
  });
}

function switchTab(tabName) {
  activeSaaSTab = tabName;
  
  const sections = document.querySelectorAll('.saas-section');
  sections.forEach(sec => sec.classList.remove('active'));
  
  const titleMap = {
    'overview': { title: 'نظرة عامة على المنصة', subtitle: 'إحصائيات إيرادات المنصة والمتاجر المشتركة' },
    'stores': { title: 'إدارة المتاجر المشتركة', subtitle: 'التحكم بنشاط المتاجر، تجميد أو تنشيط الاشتراكات' },
    'settings': { title: 'إعدادات المنصة العامة', subtitle: 'إدارة بوابات الدفع، الضرائب، وحسابات الإدارة للـ SaaS' },
    'merchant-overview': { title: 'لوحة تحكم اشتراك المتجر', subtitle: 'بيانات حساب متجرك ونظام مستودعاتك السحابية' },
    'merchant-billing': { title: 'إدارة الفواتير والفوترة', subtitle: 'كشوفات الحساب السابقة وبيانات بطاقة الدفع المعتمدة' }
  };

  const activeSectionId = tabName === 'merchant-overview' ? 'merchant-overview' :
                            tabName === 'merchant-billing' ? 'merchant-billing' :
                            `super-admin-${tabName}`;
                            
  const target = document.getElementById(activeSectionId);
  if (target) {
    target.classList.add('active');
  }

  if (titleMap[tabName]) {
    document.getElementById('active-page-title').innerText = titleMap[tabName].title;
    document.getElementById('active-page-subtitle').innerText = titleMap[tabName].subtitle;
  }
}

function renderStoresTable() {
  const tbody = document.getElementById('saas-stores-table-body');
  const searchVal = document.getElementById('stores-search-input')?.value.toLowerCase() || '';
  
  const filtered = stores.filter(s => s.name.toLowerCase().includes(searchVal) || s.owner.toLowerCase().includes(searchVal));
  
  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center" style="text-align: center; padding: 24px; color: var(--text-muted);">لا توجد متاجر مطابقة.</td></tr>`;
    return;
  }
  
  const tierLabels = { 'Starter': 'باقة المبتدئين', 'Pro': 'الباقة الاحترافية', 'Enterprise': 'باقة الشركات' };
  const statusBadges = {
    'Active': 'badge-success',
    'Trial': 'badge-info',
    'Suspended': 'badge-danger'
  };
  const statusTexts = {
    'Active': 'نشط',
    'Trial': 'فترة تجريبية',
    'Suspended': 'مجمد'
  };

  tbody.innerHTML = filtered.map(s => `
    <tr>
      <td>
        <div style="font-weight: 700; color: var(--text-primary);">${s.name}</div>
        <div style="font-size: 11px; color: var(--text-muted);">${s.owner} (slug: ${s.slug})</div>
      </td>
      <td>
        <span style="font-weight: 600; font-size: 13px;">${tierLabels[s.plan] || s.plan}</span>
      </td>
      <td style="font-family: var(--font-english); font-size: 13px;">${s.registeredDate}</td>
      <td style="font-family: var(--font-english); font-weight: 700; color: hsla(var(--primary), 1);">$${s.bill}/شهرياً</td>
      <td><span class="badge ${statusBadges[s.status]}">${statusTexts[s.status]}</span></td>
      <td>
        <div style="display: flex; gap: 8px; align-items: center;">
          <!-- Dynamic Launch Link -->
          <a href="store-manager.php?tenant=${s.slug}" class="btn btn-secondary btn-sm" title="دخول إدارة المتجر والمستودعات"><i class="fas fa-external-link-alt"></i> دخول</a>
          <!-- Suspend/Activate Switch -->
          <button class="btn btn-secondary btn-sm btn-icon toggle-store-status-btn" data-id="${s.id}" style="color: ${s.status === 'Suspended' ? 'hsla(var(--success), 1)' : 'hsla(var(--danger), 1)'};" title="${s.status === 'Suspended' ? 'تنشيط الحساب' : 'تجميد الحساب'}">
            <i class="fas ${s.status === 'Suspended' ? 'fa-play' : 'fa-pause'}"></i>
          </button>
        </div>
      </td>
    </tr>
  `).join('');

  document.querySelectorAll('.toggle-store-status-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = e.currentTarget.getAttribute('data-id');
      toggleStoreStatus(id);
    });
  });
}

async function toggleStoreStatus(storeId) {
  const storeItem = stores.find(s => s.id === storeId);
  if (storeItem) {
    const isSuspended = storeItem.status === 'Suspended';
    const newStatus = isSuspended ? 'Active' : 'Suspended';
    
    try {
      const res = await fetch('api/tenants.php?action=toggle_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: storeId, status: newStatus })
      });
      const result = await res.json();
      if (result.success) {
        storeItem.status = newStatus;
        localStorage.setItem('ns_saas_stores', JSON.stringify(stores));
        renderStoresTable();
        calculateSaaSMETRICs();
      } else {
        alert(result.message);
      }
    } catch (err) {
      alert('فشل تحديث حالة المتجر على الخادم.');
    }
  }
}

function setupSuperAdminSearch() {
  const searchInput = document.getElementById('stores-search-input');
  if (searchInput) {
    searchInput.addEventListener('input', renderStoresTable);
  }
}

function calculateSaaSMETRICs() {
  const activeCount = stores.filter(s => s.status === 'Active' || s.status === 'Trial').length;
  document.getElementById('kpi-saas-stores').innerText = activeCount;
  
  const activeMRR = stores
    .filter(s => s.status === 'Active')
    .reduce((sum, s) => sum + s.bill, 0);
  document.getElementById('kpi-saas-mrr').innerText = `$${activeMRR.toLocaleString()}`;
}

let mrrChart = null;
let distributionChart = null;

function renderSaaSCharts() {
  calculateSaaSMETRICs();

  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const gridColor = isDark ? '#374151' : '#e2e8f0';
  const textColor = isDark ? '#9ca3af' : '#475569';
  const primaryColor = isDark ? '#818cf8' : '#4f46e5';
  
  if (mrrChart) mrrChart.destroy();
  if (distributionChart) distributionChart.destroy();

  const mrrCtx = document.getElementById('saasRevenueChart').getContext('2d');
  mrrChart = new Chart(mrrCtx, {
    type: 'line',
    data: {
      labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'],
      datasets: [{
        label: 'الإيرادات المتكررة (MRR) بالدولار ($)',
        data: [2500, 3400, 5200, 6800, 7900, 8450],
        borderColor: primaryColor,
        backgroundColor: isDark ? 'rgba(129, 140, 248, 0.15)' : 'rgba(79, 70, 229, 0.05)',
        fill: true,
        tension: 0.3,
        borderWidth: 3
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: textColor, font: { family: 'Cairo' } } },
        y: { grid: { color: gridColor }, ticks: { color: textColor, font: { family: 'Plus Jakarta Sans' } } }
      }
    }
  });

  const plansCtx = document.getElementById('saasPlansChart').getContext('2d');
  
  const starterCount = stores.filter(s => s.plan === 'Starter').length;
  const proCount = stores.filter(s => s.plan === 'Pro').length;
  const entCount = stores.filter(s => s.plan === 'Enterprise').length;

  distributionChart = new Chart(plansCtx, {
    type: 'doughnut',
    data: {
      labels: ['باقة المبتدئين', 'الباقة الاحترافية', 'باقة الشركات'],
      datasets: [{
        data: [starterCount, proCount, entCount],
        backgroundColor: [
          '#06b6d4',
          primaryColor,
          '#10b981'
        ],
        borderWidth: isDark ? 2 : 1,
        borderColor: isDark ? '#111827' : '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: textColor, font: { family: 'Cairo', size: 11 }, padding: 15 }
        }
      },
      cutout: '70%'
    }
  });
}
