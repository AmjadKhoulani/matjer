import { store } from './store.js';
import { initDashboard } from './dashboard.js';
import { renderInventoryTable } from './inventory.js';

let activeSupplierId = null;

export function initSuppliers() {
  renderSuppliersTable();
  setupEventListeners();
}

export function renderSuppliersTable() {
  const suppliers = store.getSuppliers();
  const tbody = document.getElementById('suppliers-table-body');
  
  if (suppliers.length === 0) {
    tbody.innerHTML = `<tr><td colspan="5" class="text-center" style="text-align: center; padding: 32px; color: var(--text-muted);">لا يوجد موردين مضافين.</td></tr>`;
    return;
  }
  
  tbody.innerHTML = suppliers.map(s => `
    <tr>
      <td style="font-weight: 700; color: var(--text-primary);">${s.name}</td>
      <td>
        <div style="font-weight: 600;">${s.contactName}</div>
        <div style="font-size: 11px; color: var(--text-muted);">${s.products}</div>
      </td>
      <td style="font-family: var(--font-english);">${s.phone}</td>
      <td style="font-family: var(--font-english); font-size: 13px; color: var(--text-muted);">${s.email}</td>
      <td>${s.address}</td>
      <td>
        <div style="display: flex; gap: 6px;">
          <button class="btn btn-secondary btn-sm btn-icon btn-restock-shipment" data-id="${s.id}" title="توريد شحنة مخزون جديدة">
            <i class="fas fa-truck-loading"></i>
          </button>
        </div>
      </td>
    </tr>
  `).join('');
  
  // Attach shipment triggers
  document.querySelectorAll('.btn-restock-shipment').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = e.currentTarget.getAttribute('data-id');
      openInboundShipmentModal(id);
    });
  });
}

function setupEventListeners() {
  document.getElementById('btn-add-supplier').addEventListener('click', () => {
    openAddSupplierModal();
  });
  
  // Close modals
  document.querySelectorAll('.modal-close').forEach(btn => {
    btn.addEventListener('click', () => {
      closeModals();
    });
  });
  
  // Form submissions
  document.getElementById('supplier-form').addEventListener('submit', handleSupplierFormSubmit);
  document.getElementById('inbound-shipment-form').addEventListener('submit', handleInboundShipmentSubmit);
}

function closeModals() {
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.classList.remove('active');
  });
  activeSupplierId = null;
}

function openAddSupplierModal() {
  document.getElementById('supplier-form').reset();
  document.getElementById('supplier-modal').classList.add('active');
}

function openInboundShipmentModal(supplierId) {
  activeSupplierId = supplierId;
  const suppliers = store.getSuppliers();
  const products = store.getProducts();
  const supplier = suppliers.find(s => s.id === supplierId);
  
  if (supplier) {
    document.getElementById('shipment-supplier-name').innerText = supplier.name;
    
    // Populate products select dropdown
    const prodSelect = document.getElementById('shipment-product-select');
    prodSelect.innerHTML = products.map(p => `
      <option value="${p.id}">${p.name} (${p.sku})</option>
    `).join('');
    
    // Automatically fill current unit cost when selecting product
    const updateUnitCost = () => {
      const selectedId = prodSelect.value;
      const prod = products.find(p => p.id === selectedId);
      if (prod) {
        document.getElementById('shipment-unit-cost').value = prod.cost;
      }
    };
    
    prodSelect.addEventListener('change', updateUnitCost);
    updateUnitCost(); // trigger once initially
    
    document.getElementById('shipment-qty').value = 10;
    document.getElementById('inbound-shipment-modal').classList.add('active');
  }
}

function handleSupplierFormSubmit(e) {
  e.preventDefault();
  
  const name = document.getElementById('sup-name').value;
  const contactName = document.getElementById('sup-contact').value;
  const phone = document.getElementById('sup-phone').value;
  const email = document.getElementById('sup-email').value;
  const address = document.getElementById('sup-address').value;
  const products = document.getElementById('sup-products').value;
  
  const suppliers = store.getSuppliers();
  const newSupplier = {
    id: Date.now().toString(),
    name,
    contactName,
    phone,
    email,
    address,
    products
  };
  
  suppliers.push(newSupplier);
  store.saveSuppliers(suppliers);
  store.addActivity('success', 'إضافة مورد جديد', `تم إضافة المورد "${name}" بنجاح إلى النظام`);
  
  closeModals();
  renderSuppliersTable();

  // Refresh Supplier select in Create Purchase if open
  const purSupSelect = document.getElementById('pur-supplier-select');
  if (purSupSelect) {
    import('./purchases.js').then(module => {
      module.populateSuppliersSelect();
      purSupSelect.value = newSupplier.id;
    });
  }
}

function handleInboundShipmentSubmit(e) {
  e.preventDefault();
  
  const productId = document.getElementById('shipment-product-select').value;
  const qty = parseInt(document.getElementById('shipment-qty').value);
  const cost = parseFloat(document.getElementById('shipment-unit-cost').value);
  const paymentStatus = document.getElementById('shipment-payment-status').value;
  
  const products = store.getProducts();
  const product = products.find(p => p.id === productId);
  const suppliers = store.getSuppliers();
  const supplier = suppliers.find(s => s.id === activeSupplierId);
  
  if (product && supplier) {
    // Modify cost if customized on receipt
    product.cost = cost;
    store.saveProducts(products); // commit cost adjustment
    
    // Add to stock and log
    store.updateProductStock(productId, qty, `شحنة واردة من المورد "${supplier.name}"`);

    // Generate Purchase Invoice for accounting records
    const purchaseInvoices = JSON.parse(localStorage.getItem('ns_purchase_invoices')) || [];
    const invoiceId = (2000 + purchaseInvoices.length + 1).toString();
    
    const newInvoice = {
      id: invoiceId,
      supplierId: supplier.id,
      supplierName: supplier.name,
      date: new Date().toISOString(),
      total: qty * cost,
      status: paymentStatus, // 'Paid' or 'Pending'
      items: [{
        productId: product.id,
        productName: product.name,
        sku: product.sku,
        quantity: qty,
        cost: cost
      }]
    };
    
    purchaseInvoices.unshift(newInvoice);
    localStorage.setItem('ns_purchase_invoices', JSON.stringify(purchaseInvoices));
    
    closeModals();
    renderSuppliersTable();
    
    // Re-render other relevant tabs to keep state synced
    if (document.getElementById('inventory-table-body')) {
      renderInventoryTable();
    }
    
    initDashboard(); // Refresh metrics
  }
}
