@extends('layouts.app')

@section('content')
@php($sizeLabels = \App\Support\ProductSizeInventory::sizeLabels())
<div class="page-header d-flex align-items-center justify-content-between">
  <h2>Products</h2>
  <div class="d-flex align-items-center gap-2">
    <a class="btn btn-boot-outline" href="{{ route('products.list', [], false) }}">View Products</a>
    <button class="btn btn-boot-primary" data-bs-toggle="modal" data-bs-target="#productModal" onclick="openAddModal()">Add Product</button>
  </div>
</div>

<div id="productsPanel" class="card">
  <div class="card-body">
    <div id="productsAlert"></div>
    <div class="table-responsive">
      <table class="table align-middle" id="productsTable">
        <thead>
          <tr>
            <th style="width: 50px;"></th>
            <th style="width: 80px;">ID</th>
            <th>Title</th>
            <th style="width: 120px;">Price</th>
            <th style="width: 100px;">Stock</th>
            <th style="width: 200px;">Category</th>
            <th style="width: 160px;">Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="productModalTitle">Add Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="productForm">
          <input type="hidden" id="product_id">
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" class="form-control" id="title" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Price</label>
            <input type="number" step="0.01" min="0" class="form-control" id="price" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Discount (%)</label>
            <input type="number" step="0.01" min="0" max="100" class="form-control" id="discount" placeholder="0">
            <div class="form-text">Set a percentage discount (e.g. 10 for 10% off). Discounted price will be calculated automatically.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Size Stocks</label>
            <div class="row g-2">
              @foreach ($sizeLabels as $sizeKey => $sizeLabel)
                <div class="col-md-4 col-6">
                  <label class="form-label small text-muted mb-1" for="size_stock_{{ $sizeKey }}">{{ $sizeLabel }}</label>
                  <input type="number" min="0" class="form-control size-stock-input" id="size_stock_{{ $sizeKey }}" value="0">
                </div>
              @endforeach
            </div>
            <div class="form-text">Manage inventory per size. Total stock is calculated automatically.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Total Stock</label>
            <input type="number" min="0" class="form-control" id="stock" placeholder="0" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label d-block">Pre-order</label>
            <input class="visually-hidden" type="checkbox" id="is_preorder">
            <button type="button" class="btn btn-outline-secondary w-100 text-start" id="preorderToggleBtn" aria-pressed="false"></button>
            <div class="form-text">Customers can still order sold-out sizes as pre-orders when this is enabled.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Pre-order Note</label>
            <input type="text" class="form-control" id="preorder_note" maxlength="255" placeholder="Optional, e.g. Ships in 2-3 weeks">
            <div class="form-text">Optional customer-facing note shown when a size is being pre-ordered.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Category</label>
            <input type="text" class="form-control" id="category">
          </div>
          <div class="mb-3">
            <label class="form-label">Image URL</label>
            <input type="url" class="form-control" id="image">
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" id="description" rows="3"></textarea>
          </div>
          <button type="submit" class="btn btn-boot-primary w-100" id="saveBtn">Save</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div id="productsManageConfig"
  data-api-base="{{ route('staff.products.api', [], false) }}"
  data-size-labels='@json($sizeLabels)'
  hidden></div>

<script>
const productsManageConfig = document.getElementById('productsManageConfig');
const apiBase = productsManageConfig?.dataset.apiBase || '';
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const sizeLabels = JSON.parse(productsManageConfig?.dataset.sizeLabels || '{}');

function alertBox(message, type='danger'){
  const iconMap = {
    success: 'success',
    danger: 'error',
    warning: 'warning',
    info: 'info'
  };
  const icon = iconMap[type] || 'info';
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: icon,
    title: message,
    showConfirmButton: false,
    timer: 4000,
    timerProgressBar: true
  });
}

async function fetchJSON(url, options={}){
  const finalOptions = { ...options };
  finalOptions.headers = { ...(options.headers || {}) };
  if (csrfToken) {
    finalOptions.headers['X-CSRF-TOKEN'] = csrfToken;
  }
  if (!finalOptions.headers['Accept']) {
    finalOptions.headers['Accept'] = 'application/json';
  }
  const res = await fetch(url, finalOptions);
  const data = await res.json().catch(()=>({success:false,message:'Invalid JSON'}));
  return data;
}

async function loadProducts(){
  const tbody = document.querySelector('#productsTable tbody');
  tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Loading…</td></tr>';
  const data = await fetchJSON(`${apiBase}?action=list`);
  if(!data.success){
    tbody.innerHTML = '<tr><td colspan="7" class="text-danger">Failed to load products.</td></tr>';
    return;
  }
  const products = Array.isArray(data.products) ? data.products : [];
  if(products.length === 0){
    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No products found.</td></tr>';
    return;
  }
  tbody.innerHTML = '';
  for(const p of products){
    const tr = document.createElement('tr');
    tr.setAttribute('draggable', 'true');
    tr.setAttribute('data-product-id', p.id ?? '');
    tr.classList.add('draggable-row');
    const stock = Number(p.stock ?? 0);
    const sizeSummary = formatSizeSummary(p);
    const preorderBadge = isPreorderEnabled(p) ? '<span class="badge bg-info text-dark ms-2">Pre-order</span>' : '';
    const preorderNote = escapeHtml(getPreorderNote(p));
    if (stock === 0) {
      tr.classList.add('out-of-stock');
    }
    let priceHtml = `$${Number(p.price ?? 0).toFixed(2)}`;
    if (p.discount && Number(p.discount) > 0) {
      priceHtml += `<br><span class='badge bg-success'>-${Number(p.discount).toFixed(2)}% = $${Number(p.discounted_price ?? p.price).toFixed(2)}</span>`;
    }
    tr.innerHTML = `
      <td class="drag-handle" style="cursor: move;">
        <i class="fas fa-grip-vertical text-muted"></i>
      </td>
      <td>${p.id ?? ''}</td>
      <td>
        <div class="fw-semibold">${escapeHtml(p.title ?? '')}${preorderBadge}</div>
        ${preorderNote ? `<div class="small text-muted mt-1">${preorderNote}</div>` : ''}
      </td>
      <td>${priceHtml}</td>
      <td>${stock === 0 ? '<span class="text-danger">Out of Stock</span>' : stock}<div class="small text-muted mt-1">${sizeSummary}</div></td>
      <td>${escapeHtml(p.category ?? '')}</td>
      <td>
        <div class="d-grid gap-2">
          <button class="btn btn-sm w-100 btn-boot-primary" onclick='openEditModal(${JSON.stringify(p.id ?? '')})'>Edit</button>
          <button class="btn btn-danger btn-sm w-100" onclick='confirmDelete(${JSON.stringify(p.id ?? '')})'>Delete</button>
        </div>
      </td>`;
    tbody.appendChild(tr);
  }
  initializeDragAndDrop();
}

function escapeHtml(s){
  return String(s).replace(/[&<>"]+/g, c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"}[c]));
}

function getSizeStockMap(product = {}) {
  const map = {};
  Object.keys(sizeLabels).forEach((sizeKey) => {
    map[sizeKey] = Math.max(0, Number(product?.size_stock_map?.[sizeKey] ?? 0));
  });
  return map;
}

function isPreorderEnabled(product = {}) {
  const value = product?.is_preorder;
  return value === true || value === 1 || value === '1' || value === 'true';
}

function getPreorderNote(product = {}) {
  return String(product?.preorder_note ?? '').trim();
}

function formatSizeSummary(product) {
  const stockMap = getSizeStockMap(product);
  return Object.entries(sizeLabels)
    .map(([sizeKey, label]) => `${shortSizeLabel(sizeKey)}: ${stockMap[sizeKey] || 0}`)
    .join(' · ');
}

function shortSizeLabel(sizeKey) {
  if (sizeKey === 'small') return 'S';
  if (sizeKey === 'medium') return 'M';
  if (sizeKey === 'large') return 'L';
  return sizeKey.toUpperCase();
}

function recalculateTotalStock() {
  let total = 0;
  document.querySelectorAll('.size-stock-input').forEach((input) => {
    total += Math.max(0, Number(input.value || 0));
  });
  document.getElementById('stock').value = total;
}

function syncPreorderNoteState() {
  const preorderCheckbox = document.getElementById('is_preorder');
  const preorderNoteInput = document.getElementById('preorder_note');
  const preorderToggleBtn = document.getElementById('preorderToggleBtn');
  if (!preorderCheckbox || !preorderNoteInput || !preorderToggleBtn) {
    return;
  }

  const enabled = preorderCheckbox.checked;

  preorderNoteInput.disabled = !preorderCheckbox.checked;
  preorderNoteInput.placeholder = preorderCheckbox.checked
    ? 'Optional, e.g. Ships in 2-3 weeks'
    : 'Enable pre-order first';

  preorderToggleBtn.classList.toggle('btn-boot-primary', enabled);
  preorderToggleBtn.classList.toggle('btn-outline-secondary', !enabled);
  preorderToggleBtn.setAttribute('aria-pressed', enabled ? 'true' : 'false');
  preorderToggleBtn.innerHTML = enabled
    ? '<i class="fas fa-clock me-2"></i>Pre-order Enabled'
    : '<i class="fas fa-ban me-2"></i>Pre-order Disabled';
}

function openAddModal(){
  document.getElementById('productModalTitle').textContent = 'Add Product';
  document.getElementById('product_id').value = '';
  document.getElementById('title').value = '';
  document.getElementById('price').value = '';
  document.getElementById('discount').value = '';
  Object.keys(sizeLabels).forEach((sizeKey) => {
    document.getElementById(`size_stock_${sizeKey}`).value = 0;
  });
  document.getElementById('stock').value = '0';
  document.getElementById('is_preorder').checked = false;
  document.getElementById('preorder_note').value = '';
  document.getElementById('category').value = '';
  document.getElementById('image').value = '';
  document.getElementById('description').value = '';
  recalculateTotalStock();
  syncPreorderNoteState();
}

async function openEditModal(id){
  const data = await fetchJSON(`${apiBase}?action=get&id=${encodeURIComponent(id)}`);
  if(!data.success){ alertBox('Failed to load product for edit'); return; }
  const p = data.product || {};
  document.getElementById('productModalTitle').textContent = 'Edit Product';
  document.getElementById('product_id').value = p.id || '';
  document.getElementById('title').value = p.title || '';
  document.getElementById('price').value = p.price || '';
  document.getElementById('discount').value = p.discount || '';
  const stockMap = getSizeStockMap(p);
  Object.keys(sizeLabels).forEach((sizeKey) => {
    document.getElementById(`size_stock_${sizeKey}`).value = stockMap[sizeKey] || 0;
  });
  document.getElementById('stock').value = p.stock || 0;
  document.getElementById('is_preorder').checked = isPreorderEnabled(p);
  document.getElementById('preorder_note').value = getPreorderNote(p);
  document.getElementById('category').value = p.category || '';
  document.getElementById('image').value = p.image || '';
  document.getElementById('description').value = p.description || '';
  recalculateTotalStock();
  syncPreorderNoteState();
  const modal = new bootstrap.Modal(document.getElementById('productModal'));
  modal.show();
}

async function confirmDelete(id){
  const result = await Swal.fire({
    title: 'Delete Product?',
    text: 'Are you sure you want to delete this product?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, delete it',
    cancelButtonText: 'Cancel'
  });

  if (!result.isConfirmed) return;

  const form = new URLSearchParams();
  form.set('action','delete');
  form.set('product_id', id);
  const data = await fetchJSON(apiBase, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: form.toString() });
  if(data.success){ alertBox('Product deleted', 'success'); loadProducts(); }
  else { alertBox(data.message || 'Delete failed'); }
}

const productForm = document.getElementById('productForm');
productForm.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const id = document.getElementById('product_id').value.trim();
  const title = document.getElementById('title').value.trim();
  const price = document.getElementById('price').value.trim();
  const discount = document.getElementById('discount').value.trim();
  const isPreorder = document.getElementById('is_preorder').checked;
  const preorderNote = document.getElementById('preorder_note').value.trim();
  const category = document.getElementById('category').value.trim();
  const image = document.getElementById('image').value.trim();
  const description = document.getElementById('description').value.trim();
  if(!title || Number(price) <= 0){ alertBox('Please provide a valid title and price'); return; }

  const form = new URLSearchParams();
  form.set('title', title);
  form.set('price', price);
  form.set('discount', discount);
  form.set('is_preorder', isPreorder ? '1' : '0');
  form.set('preorder_note', preorderNote);
  form.set('category', category);
  form.set('image', image);
  form.set('description', description);
  Object.keys(sizeLabels).forEach((sizeKey) => {
    form.set(`size_stock[${sizeKey}]`, document.getElementById(`size_stock_${sizeKey}`).value.trim() || '0');
  });

  let action = 'add';
  if(id){ action = 'update'; form.set('product_id', id); }
  form.set('action', action);

  const actionText = action === 'add' ? 'Add' : 'Update';
  const result = await Swal.fire({
    title: `${actionText} Product?`,
    text: `Are you sure you want to ${action === 'add' ? 'add' : 'update'} "${title}"?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#1c1d1e',
    cancelButtonColor: '#6c757d',
    confirmButtonText: `Yes, ${action} it`,
    cancelButtonText: 'Cancel'
  });

  if (!result.isConfirmed) return;

  const data = await fetchJSON(apiBase, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: form.toString() });
  if(data.success){
    alertBox('Product saved', 'success');
    const modalEl = document.getElementById('productModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.hide();
    loadProducts();
  } else {
    alertBox(data.message || 'Save failed');
  }
});

let draggedElement = null;

function initializeDragAndDrop() {
  const rows = document.querySelectorAll('#productsTable tbody tr.draggable-row');

  rows.forEach(row => {
    row.addEventListener('dragstart', function(e) {
      draggedElement = this;
      this.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/html', this.innerHTML);
    });

    row.addEventListener('dragend', function(e) {
      this.classList.remove('dragging');
      document.querySelectorAll('#productsTable tbody tr').forEach(r => {
        r.classList.remove('drag-over');
      });
    });

    row.addEventListener('dragover', function(e) {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';

      if (this === draggedElement) return;

      document.querySelectorAll('#productsTable tbody tr').forEach(r => {
        r.classList.remove('drag-over');
      });
      this.classList.add('drag-over');
    });

    row.addEventListener('drop', async function(e) {
      e.preventDefault();
      e.stopPropagation();

      if (this === draggedElement) return;

      const tbody = document.querySelector('#productsTable tbody');
      const allRows = Array.from(tbody.querySelectorAll('tr.draggable-row'));
      const draggedIndex = allRows.indexOf(draggedElement);
      const targetIndex = allRows.indexOf(this);

      if (draggedIndex < targetIndex) {
        this.parentNode.insertBefore(draggedElement, this.nextSibling);
      } else {
        this.parentNode.insertBefore(draggedElement, this);
      }

      this.classList.remove('drag-over');

      await saveProductOrder();
    });
  });
}

async function saveProductOrder() {
  const tbody = document.querySelector('#productsTable tbody');
  const rows = tbody.querySelectorAll('tr.draggable-row');
  const order = Array.from(rows).map(row => row.getAttribute('data-product-id'));

  const formData = new URLSearchParams();
  formData.set('action', 'reorder');
  formData.set('order', JSON.stringify(order));

  const data = await fetchJSON(apiBase, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: formData.toString()
  });

  if (data.success) {
    alertBox('Product order updated', 'success');
  } else {
    alertBox(data.message || 'Failed to save order', 'danger');
  }
}

document.querySelectorAll('.size-stock-input').forEach((input) => {
  input.addEventListener('input', recalculateTotalStock);
});

const preorderCheckbox = document.getElementById('is_preorder');
const preorderToggleBtn = document.getElementById('preorderToggleBtn');
if (preorderCheckbox && preorderToggleBtn) {
  preorderCheckbox.addEventListener('change', syncPreorderNoteState);
  preorderToggleBtn.addEventListener('click', function() {
    preorderCheckbox.checked = !preorderCheckbox.checked;
    syncPreorderNoteState();
  });
}

recalculateTotalStock();
syncPreorderNoteState();

loadProducts();
</script>

<style>
#productsPanel:hover { border-color: transparent !important; }

.btn-boot-primary {
  background: #0d6efd;
  color: #fff;
  border: none;
  padding: 0.6rem 1.5rem;
  font-weight: 500;
  border-radius: 8px;
  transition: background-color .15s ease, color .15s ease, box-shadow .15s ease;
}
.btn-boot-primary:hover {
  background: #0b5ed7;
  color: rgba(255,255,255,0.92);
}
.btn-boot-primary:focus,
.btn-boot-primary:active {
  color: rgba(255,255,255,0.92);
  box-shadow: 0 0 0 0.25rem rgba(13,110,253,0.25);
  outline: none;
}

.btn-boot-outline {
  background: transparent;
  color: #0d6efd;
  border: 1px solid #0d6efd;
  padding: 0.55rem 1.25rem;
  border-radius: 8px;
  transition: background-color .12s ease, color .12s ease, border-color .12s ease;
}
.btn-boot-outline:hover {
  background: rgba(13,110,253,0.06);
  color: #0d6efd;
  text-decoration: none;
}
.btn-boot-outline:focus {
  box-shadow: 0 0 0 0.15rem rgba(13,110,253,0.12);
  outline: none;
}

.draggable-row {
  cursor: move;
  transition: all 0.2s ease;
}

.draggable-row.dragging {
  opacity: 0.5;
  background-color: #f8f9fa;
}

.draggable-row.out-of-stock {
  opacity: 0.5;
  background-color: #f5f5f5;
}

.draggable-row.drag-over {
  border-top: 3px solid #1c1d1e;
  background-color: rgba(28,29,30,0.04);
}

.drag-handle:hover {
  background-color: #f8f9fa;
}
</style>
@endsection
