import { store } from './store.js';

const INITIAL_APPS = [
  { id: 'whatsapp', name: 'واتساب برو للتنبيهات', desc: 'إرسال تنبيهات تلقائية للعملاء السوريين برقم تتبع الشحنة ورابط الفاتورة عند شحن الطلب.', icon: 'fab fa-whatsapp', color: '#25D366', cost: 'مجاني', installed: false },
  { id: 'mobile-val', name: 'مدقق الهواتف السورية', desc: 'التحقق من صحة أرقام الموبايل (سيريتل MTN) المدخلة من قبل المشتري في الفروع أو المتجر الإلكتروني.', icon: 'fas fa-mobile-alt', color: '#ff5722', cost: 'مجاني', installed: true },
  { id: 'easytax', name: 'الفواتير الضريبية المبسطة', desc: 'توليد فواتير بيع معتمدة ومطابقة لنسب ضريبة الدخل والمبيعات السورية المحلية وللهيئة العامة للضرائب.', icon: 'fas fa-file-invoice-dollar', color: '#00bcd4', cost: 'مجاني', installed: true },
  { id: 'seo-booster', name: 'SEO Booster لغوغل', desc: 'توليد تلقائي للكلمات المفتاحية والأوصاف التعريفية لمتجرك ليتصدر نتائج البحث في المحافظات السورية.', icon: 'fas fa-search-dollar', color: '#ff9800', cost: 'مجاني', installed: false },
  { id: 'kadam-express', name: 'شحن قدم السريع (Kadam)', desc: 'ربط ومزامنة شحناتك مع شركة قدم للمقاولات والتوصيل الداخلي السريع بين المحافظات السورية.', icon: 'fas fa-shipping-fast', color: '#4caf50', cost: 'مجاني', installed: false },
  { id: 'bemo-pay', name: 'بوابة دفع بمو السورية', desc: 'قبول الدفع الإلكتروني عبر حسابات بنك بيمو السعودي الفرنسي المحلي في سوريا.', icon: 'fas fa-university', color: '#3f51b5', cost: 'مجاني', installed: false }
];

export function initApps() {
  // Ensure seed apps database exists
  let apps = JSON.parse(localStorage.getItem('ns_apps'));
  if (!apps) {
    apps = INITIAL_APPS;
    localStorage.setItem('ns_apps', JSON.stringify(apps));
  }

  renderAppsMarketplace();
  renderInstalledApps();
}

export function renderAppsMarketplace() {
  const container = document.getElementById('apps-marketplace-list');
  if (!container) return;

  const apps = JSON.parse(localStorage.getItem('ns_apps')) || INITIAL_APPS;
  const marketplaceApps = apps.filter(app => !app.installed);

  if (marketplaceApps.length === 0) {
    container.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 32px; color: var(--text-muted);">جميع التطبيقات المتوفرة تم تثبيتها بالفعل!</div>`;
    return;
  }

  container.innerHTML = marketplaceApps.map(app => `
    <div style="border: 1px solid var(--border-color); border-radius: var(--border-radius); background: var(--bg-secondary); padding: 20px; display: flex; flex-direction: column; gap: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: transform 0.2s;" class="app-card">
      <div style="display: flex; align-items: center; gap: 15px;">
        <div style="width: 48px; height: 48px; border-radius: 8px; background-color: ${app.color}15; display: flex; align-items: center; justify-content: center; color: ${app.color}; font-size: 24px;">
          <i class="${app.icon}"></i>
        </div>
        <div>
          <h4 style="font-weight: 700; font-size: 15px; color: var(--text-primary);">${app.name}</h4>
          <span style="font-size: 11px; color: var(--text-muted); font-weight: 600;">التكلفة: ${app.cost}</span>
        </div>
      </div>
      <p style="font-size: 12px; color: var(--text-muted); line-height: 1.5; flex-grow: 1;">${app.desc}</p>
      <button class="btn btn-primary btn-sm btn-install-app" data-id="${app.id}" style="width: 100%;">
        <i class="fas fa-download"></i> تثبيت التطبيق
      </button>
    </div>
  `).join('');

  // Bind install buttons
  container.querySelectorAll('.btn-install-app').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const appId = e.currentTarget.getAttribute('data-id');
      installApp(appId, e.currentTarget);
    });
  });
}

export function renderInstalledApps() {
  const container = document.getElementById('apps-installed-list');
  if (!container) return;

  const apps = JSON.parse(localStorage.getItem('ns_apps')) || INITIAL_APPS;
  const installedApps = apps.filter(app => app.installed);

  if (installedApps.length === 0) {
    container.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 32px; color: var(--text-muted);">لا توجد تطبيقات مثبتة حالياً. تصفح سوق التطبيقات لتثبيتها!</div>`;
    return;
  }

  container.innerHTML = installedApps.map(app => `
    <div style="border: 1px solid var(--border-color); border-radius: var(--border-radius); background: var(--bg-secondary); padding: 20px; display: flex; flex-direction: column; gap: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);" class="app-card">
      <div style="display: flex; align-items: center; gap: 15px;">
        <div style="width: 48px; height: 48px; border-radius: 8px; background-color: ${app.color}15; display: flex; align-items: center; justify-content: center; color: ${app.color}; font-size: 24px;">
          <i class="${app.icon}"></i>
        </div>
        <div>
          <h4 style="font-weight: 700; font-size: 15px; color: var(--text-primary);">${app.name}</h4>
          <span class="badge badge-success" style="font-size: 9px; padding: 2px 6px; background-color: #eef5f2; color: #1b4d3e;">نشط ومفعّل</span>
        </div>
      </div>
      <p style="font-size: 12px; color: var(--text-muted); line-height: 1.5; flex-grow: 1;">${app.desc}</p>
      <div style="display: flex; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 12px;">
        <button class="btn btn-secondary btn-sm btn-app-settings" data-id="${app.id}" style="flex: 1;"><i class="fas fa-cog"></i> الإعدادات</button>
        <button class="btn btn-secondary btn-sm btn-uninstall-app" data-id="${app.id}" style="color: hsla(var(--danger), 1); border-color: hsla(var(--danger), 0.2);"><i class="fas fa-trash"></i> تعطيل</button>
      </div>
    </div>
  `).join('');

  // Bind settings buttons
  container.querySelectorAll('.btn-app-settings').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const appId = e.currentTarget.getAttribute('data-id');
      const apps = JSON.parse(localStorage.getItem('ns_apps')) || INITIAL_APPS;
      const app = apps.find(a => a.id === appId);
      alert(`إعدادات تطبيق (${app.name}): التطبيق يعمل حالياً بالكامل بالخلفية بمزامنة فورية.`);
    });
  });

  // Bind uninstall buttons
  container.querySelectorAll('.btn-uninstall-app').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const appId = e.currentTarget.getAttribute('data-id');
      uninstallApp(appId);
    });
  });
}

function installApp(id, btnElement) {
  btnElement.disabled = true;
  btnElement.innerHTML = `<i class="fas fa-spinner fa-spin"></i> جاري التثبيت...`;

  setTimeout(() => {
    let apps = JSON.parse(localStorage.getItem('ns_apps')) || INITIAL_APPS;
    const app = apps.find(a => a.id === id);
    if (app) {
      app.installed = true;
      localStorage.setItem('ns_apps', JSON.stringify(apps));
      
      store.addActivity('success', 'تثبيت تطبيق جديد', `تم تثبيت وتفعيل التطبيق الملحق "${app.name}" بنجاح في متجرك`);
      alert(`نجح التثبيت! تم تفعيل تطبيق (${app.name}) وإضافته لمتجرك بنجاح.`);
      
      // Update views
      renderAppsMarketplace();
      renderInstalledApps();
    }
  }, 1200);
}

function uninstallApp(id) {
  if (confirm('هل أنت متأكد من رغبتك في تعطيل وإزالة هذا التطبيق؟ قد تفقد المزامنة الفورية الخاصة بخصائصه.')) {
    let apps = JSON.parse(localStorage.getItem('ns_apps')) || INITIAL_APPS;
    const app = apps.find(a => a.id === id);
    if (app) {
      app.installed = false;
      localStorage.setItem('ns_apps', JSON.stringify(apps));
      
      store.addActivity('danger', 'تعطيل تطبيق', `تم إلغاء تثبيت وتعطيل التطبيق الملحق "${app.name}" من متجرك`);
      alert(`تم تعطيل تطبيق (${app.name}) وإزالته بنجاح.`);
      
      // Update views
      renderAppsMarketplace();
      renderInstalledApps();
    }
  }
}
