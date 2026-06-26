const INITIAL_EMPLOYEES = [
  { id: '1', name: 'أحمد السديري', email: 'a.sudairy@matjer.net', role: 'مدير المتجر', branch: 'مستودع دمشق الرئيسي', status: 'نشط' },
  { id: '2', name: 'سلطان القحطاني', email: 's.qahtani@matjer.net', role: 'أمين المستودع', branch: 'مستودع دمشق الرئيسي', status: 'نشط' },
  { id: '3', name: 'مها الشمري', email: 'm.shammari@matjer.net', role: 'كاشير مبيعات', branch: 'معرض حلب الفوري', status: 'نشط' },
  { id: '4', name: 'رائد الهذلي', email: 'r.hothali@matjer.net', role: 'مساعد كاشير', branch: 'معرض دمشق الفوري', status: 'إجازة' }
];

const DEFAULT_ROLE_PERMISSIONS = {
  'manager': ['dash', 'inv', 'pos', 'ord', 'fin', 'team', 'sets'],
  'keeper': ['inv', 'ord'],
  'cashier': ['pos', 'ord']
};

export function initTeam() {
  loadEmployees();
  loadPermissionsGrid();
  setupAddEmployee();
  initAuditLogs();
}

function loadEmployees() {
  const tbody = document.getElementById('team-employees-table-body');
  if (!tbody) return;

  let employees = JSON.parse(localStorage.getItem('ns_employees')) || INITIAL_EMPLOYEES;
  localStorage.setItem('ns_employees', JSON.stringify(employees));

  if (employees.length === 0) {
    tbody.innerHTML = `<tr><td colspan="5" class="text-center" style="padding:16px; color:var(--text-muted);">لا يوجد موظفون مضافون بعد.</td></tr>`;
    return;
  }

  tbody.innerHTML = employees.map(emp => `
    <tr>
      <td>
        <div style="font-weight: 700; color: var(--text-primary);">${emp.name}</div>
        <div style="font-size: 11px; color: var(--text-muted); font-family: var(--font-english);">${emp.email}</div>
      </td>
      <td><span style="font-weight: 600; font-size:13.5px;">${emp.role}</span></td>
      <td>${emp.branch}</td>
      <td><span class="badge ${emp.status === 'نشط' ? 'badge-success' : 'badge-warning'}">${emp.status}</span></td>
      <td>
        <button class="btn btn-secondary btn-sm delete-employee-btn" data-id="${emp.id}" style="color:var(--text-on-primary); background-color:hsla(var(--danger), 1); border:none;"><i class="fas fa-trash"></i></button>
      </td>
    </tr>
  `).join('');

  // Bind deletes
  tbody.querySelectorAll('.delete-employee-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = e.currentTarget.getAttribute('data-id');
      deleteEmployee(id);
    });
  });
}

function deleteEmployee(id) {
  let employees = JSON.parse(localStorage.getItem('ns_employees')) || INITIAL_EMPLOYEES;
  employees = employees.filter(e => e.id !== id);
  localStorage.setItem('ns_employees', JSON.stringify(employees));
  loadEmployees();
}

function loadPermissionsGrid() {
  const grid = document.getElementById('team-permissions-grid');
  if (!grid) return;

  let rolePermissions = JSON.parse(localStorage.getItem('ns_role_permissions')) || DEFAULT_ROLE_PERMISSIONS;
  localStorage.setItem('ns_role_permissions', JSON.stringify(rolePermissions));

  const roleConfigs = [
    { key: 'manager', title: 'مدير النظام / المتجر', desc: 'الوصول الكامل لجميع الأقسام وتعديل الإعدادات والماليات.', icon: 'fa-user-tie' },
    { key: 'keeper', title: 'أمين المستودع (Stock Keeper)', desc: 'إدارة جرد المخازن وتوريد الشحنات وتحديث المنتجات فقط.', icon: 'fa-boxes' },
    { key: 'cashier', title: 'كاشير نقطة البيع (POS Cashier)', desc: 'استخدام شاشة نقطة البيع وتسجيل الطلبيات والفواتير اليومية.', icon: 'fa-cash-register' }
  ];

  const permissions = [
    { key: 'dash', title: 'عرض لوحة الإحصائيات (Dashboard)' },
    { key: 'inv', title: 'إدارة المستودع والجرد والمنتجات' },
    { key: 'pos', title: 'استخدام شاشة نقطة البيع (POS)' },
    { key: 'ord', title: 'عرض وتعديل الطلبات وفواتير البيع' },
    { key: 'fin', title: 'الوصول للحسابات والمالية والمصاريف' },
    { key: 'team', title: 'إدارة شؤون الموظفين وتعديل الأدوار' },
    { key: 'sets', title: 'تعديل إعدادات المتجر ونسب الضرائب' }
  ];

  grid.innerHTML = roleConfigs.map(role => {
    const activePerms = rolePermissions[role.key] || [];

    const checkboxesHTML = permissions.map(perm => {
      const checked = activePerms.includes(perm.key) ? 'checked' : '';
      return `
        <label class="permission-checkbox-item">
          <input type="checkbox" class="perm-chk" data-role="${role.key}" data-perm="${perm.key}" ${checked}>
          <span>${perm.title}</span>
        </label>
      `;
    }).join('');

    return `
      <div class="role-card">
        <div class="role-card-header">
          <h4 class="role-title">
            <i class="fas ${role.icon}" style="color: hsla(var(--primary), 1);"></i>
            <span>${role.title}</span>
          </h4>
          <span style="font-size: 11px; font-weight:700; color:var(--text-muted);">أدوار مخصصة</span>
        </div>
        <p class="role-desc">${role.desc}</p>
        
        <div class="permission-list">
          ${checkboxesHTML}
        </div>
        
        <button class="btn btn-primary btn-sm save-role-perms-btn" data-role="${role.key}" style="margin-top:12px; width:100%;">
          <i class="fas fa-save"></i> حفظ صلاحيات الدور
        </button>
      </div>
    `;
  }).join('');

  // Bind save buttons
  grid.querySelectorAll('.save-role-perms-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const roleKey = e.currentTarget.getAttribute('data-role');
      saveRolePermissions(roleKey);
    });
  });
}

function saveRolePermissions(roleKey) {
  let rolePermissions = JSON.parse(localStorage.getItem('ns_role_permissions')) || DEFAULT_ROLE_PERMISSIONS;
  const checkboxes = document.querySelectorAll(`.perm-chk[data-role="${roleKey}"]`);
  
  const enabledPerms = [];
  checkboxes.forEach(chk => {
    if (chk.checked) {
      enabledPerms.push(chk.getAttribute('data-perm'));
    }
  });

  rolePermissions[roleKey] = enabledPerms;
  localStorage.setItem('ns_role_permissions', JSON.stringify(rolePermissions));
  
  alert('تم تحديث صلاحيات الدور الأمني بنجاح!');
}

function setupAddEmployee() {
  const form = document.getElementById('add-employee-form');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    
    const name = document.getElementById('emp-name').value;
    const email = document.getElementById('emp-email').value;
    const role = document.getElementById('emp-role').value;
    const branch = document.getElementById('emp-branch').value;

    let employees = JSON.parse(localStorage.getItem('ns_employees')) || INITIAL_EMPLOYEES;
    const newEmp = {
      id: Date.now().toString(),
      name,
      email,
      role,
      branch,
      status: 'نشط'
    };

    employees.push(newEmp);
    localStorage.setItem('ns_employees', JSON.stringify(employees));
    loadEmployees();

    form.reset();
  });
}

export function initAuditLogs() {
  const tbody = document.getElementById('team-audit-table-body');
  if (!tbody) return;

  const activities = JSON.parse(localStorage.getItem('ns_activities')) || [];
  const employees = JSON.parse(localStorage.getItem('ns_employees')) || INITIAL_EMPLOYEES;

  if (activities.length === 0) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center" style="padding:16px; color:var(--text-muted);">لا توجد عمليات مسجلة حالياً.</td></tr>`;
    return;
  }

  tbody.innerHTML = activities.map((act, index) => {
    let user = 'نظام تلقائي';
    if (act.title.includes('مخزون') || act.title.includes('نقل') || act.title.includes('وارد') || act.title.includes('تعديل المخزون') || act.title.includes('تالف')) {
      const sk = employees.find(e => e.role === 'أمين المستودع') || employees[0];
      user = sk ? sk.name : 'سلطان القحطاني';
    } else if (act.title.includes('بيع') || act.title.includes('POS') || act.title.includes('طلب')) {
      const cs = employees.find(e => e.role === 'كاشير مبيعات') || employees[0];
      user = cs ? cs.name : 'مها الشمري';
    } else {
      const mg = employees.find(e => e.role === 'مدير المتجر') || employees[0];
      user = mg ? mg.name : 'أحمد السديري';
    }

    const ips = ['192.168.1.115', '192.168.1.120', '172.16.5.42', '185.140.22.9', '94.233.15.11'];
    const ip = ips[index % ips.length];

    let dateStr = act.time;
    if (act.time === 'الآن') {
      dateStr = new Date().toLocaleTimeString('ar-SA') + ' - اليوم';
    }

    return `
      <tr>
        <td style="font-family: var(--font-english); font-size:12.5px; white-space: nowrap;">${dateStr}</td>
        <td>
          <div style="font-weight: 700; color: var(--text-primary);">${user}</div>
        </td>
        <td>
          <div style="display:flex; align-items:center; gap:8px;">
            <span class="badge ${
              act.type === 'success' ? 'badge-success' :
              act.type === 'warning' ? 'badge-warning' :
              act.type === 'danger' ? 'badge-danger' : 'badge-info'
            }" style="font-size: 10px; padding: 3px 6px;">${act.title}</span>
            <span style="font-size: 12px; color: var(--text-muted);">${act.desc}</span>
          </div>
        </td>
        <td style="font-family: var(--font-english); font-size: 12px; color: var(--text-muted);">${ip}</td>
      </tr>
    `;
  }).join('');
}
