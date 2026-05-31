@extends('layouts.app')

@section('content')
<style>
  html, body {
    -ms-overflow-style: none;
    scrollbar-width: none;
  }
  html::-webkit-scrollbar, body::-webkit-scrollbar {
    width: 0px; height: 0px; background: transparent;
  }
</style>

<div class="position-fixed top-0 end-0 p-3" style="z-index: 11000;">
  <div id="cartToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header">
      <strong class="me-auto" id="toastTitle">Notification</strong>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="toastBody"></div>
  </div>
</div>

<style>
.filter-row .form-control,
.filter-row .form-select,
.filter-row .btn {
  height: calc(2.25rem + 2px);
  padding: .375rem .75rem;
  line-height: 1.5;
  border: 1px solid #ced4da;
}

.filter-row .input-group .form-control {
  border-right: none;
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}

.filter-row .input-group .btn {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
}

.filter-row input[type="search"]::-webkit-search-cancel-button {
  -webkit-appearance: none;
  appearance: none;
  height: 14px;
  width: 14px;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%231e293b'%3E%3Cpath d='M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z'/%3E%3C/svg%3E");
  background-size: 14px 14px;
  cursor: pointer;
}

.dropdown-select-btn {
  height: calc(2.25rem + 2px);
  padding: .375rem .75rem;
  line-height: 1.5;
  border: 1px solid #ced4da;
  background: #fff;
  color: #1c1d1e;
  border-radius: 4px;
  width: 100%;
  text-align: left;
}
.dropdown-select-btn .dropdown-label { display: inline-block; }
.dropdown.has-value .dropdown-select-btn {
  background-color: #1c1d1e !important;
  color: #ffffff !important;
  border-color: #1c1d1e !important;
}
.dropdown-select-btn:hover { background-color: #fff !important; color: #1c1d1e !important; }
.dropdown.has-value .dropdown-select-btn:hover { background-color: #1c1d1e !important; color: #ffffff !important; }

.dropdown-select-btn:focus,
.dropdown-select-btn:active,
.dropdown-select-btn:focus-visible,
.dropdown-select-btn:visited {
  background-color: #fff !important;
  color: #1c1d1e !important;
  border-color: #ced4da !important;
  box-shadow: none !important;
  outline: none !important;
}

.dropdown.has-value .dropdown-select-btn:focus,
.dropdown.has-value .dropdown-select-btn:active,
.dropdown.has-value .dropdown-select-btn:focus-visible,
.dropdown.has-value .dropdown-select-btn:visited {
  background-color: #1c1d1e !important;
  color: #ffffff !important;
  border-color: #1c1d1e !important;
  box-shadow: none !important;
  outline: none !important;
}

.filter-row .dropdown-menu .dropdown-item:hover,
.filter-row .dropdown-menu .dropdown-item:focus {
  background-color: transparent !important;
  color: inherit !important;
}

.filter-row .dropdown-menu .dropdown-item.active,
.filter-row .dropdown-menu .dropdown-item.active:hover,
.filter-row .dropdown-menu .dropdown-item.active:focus {
  background-color: #1c1d1e !important;
  color: #ffffff !important;
  box-shadow: none !important;
  cursor: default !important;
}

.native-select { display: none !important; }
</style>
<form class="row g-2 mb-5 filter-row" method="get" action="" data-filter-form="products-filter">
  <div class="col-md-6">
    <div class="input-group">
      <input type="search" name="q" class="form-control" placeholder="Search products..." value="{{ $q }}">
      <button class="btn btn-primary" type="submit">Search</button>
    </div>
  </div>
  <div class="col-md-3">
    <select name="category" class="form-select native-select d-none">
      <option value="all">All Categories</option>
      @foreach($categories as $cat)
        <option value="{{ $cat }}" {{ $cat === $categoryFilter ? 'selected' : '' }}>{{ $cat }}</option>
      @endforeach
    </select>

    <div class="dropdown category-dropdown {{ ($categoryFilter !== '' && $categoryFilter !== 'all') ? 'has-value' : '' }}">
      <button class="btn btn-light dropdown-toggle dropdown-select-btn" type="button" id="categoryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="dropdown-label">{{ $categoryFilter && $categoryFilter !== 'all' ? $categoryFilter : 'All Categories' }}</span>
      </button>
      <ul class="dropdown-menu" aria-labelledby="categoryDropdown">
        <li><a class="dropdown-item {{ ($categoryFilter === '' || $categoryFilter === 'all') ? 'active' : '' }}" href="#" data-value="all">All Categories</a></li>
        @foreach($categories as $cat)
          <li><a class="dropdown-item {{ $cat === $categoryFilter ? 'active' : '' }}" href="#" data-value="{{ $cat }}">{{ $cat }}</a></li>
        @endforeach
      </ul>
    </div>
  </div>
  <div class="col-md-3 col-md-2">
    <select name="sort" class="form-select native-select d-none">
      <option value="">Sort</option>
      <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
      <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
      <option value="date_asc" {{ $sort === 'date_asc' ? 'selected' : '' }}>Date: Oldest to Newest</option>
      <option value="date_desc" {{ $sort === 'date_desc' ? 'selected' : '' }}>Date: Newest to Oldest</option>
    </select>

    <div class="dropdown sort-dropdown {{ $sort !== '' ? 'has-value' : '' }}">
      <button class="btn btn-light dropdown-toggle dropdown-select-btn" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="dropdown-label">
          @if($sort === 'price_asc')
            Price: Low to High
          @elseif($sort === 'price_desc')
            Price: High to Low
          @elseif($sort === 'date_asc')
            Date: Oldest to Newest
          @elseif($sort === 'date_desc')
            Date: Newest to Oldest
          @else
            Sort
          @endif
        </span>
      </button>
      <ul class="dropdown-menu" aria-labelledby="sortDropdown">
        <li><a class="dropdown-item {{ $sort === '' ? 'active' : '' }}" href="#" data-value="">Sort</a></li>
        <li><a class="dropdown-item {{ $sort === 'price_asc' ? 'active' : '' }}" href="#" data-value="price_asc">Price: Low to High</a></li>
        <li><a class="dropdown-item {{ $sort === 'price_desc' ? 'active' : '' }}" href="#" data-value="price_desc">Price: High to Low</a></li>
        <li><a class="dropdown-item {{ $sort === 'date_asc' ? 'active' : '' }}" href="#" data-value="date_asc">Date: Oldest to Newest</a></li>
        <li><a class="dropdown-item {{ $sort === 'date_desc' ? 'active' : '' }}" href="#" data-value="date_desc">Date: Newest to Oldest</a></li>
      </ul>
    </div>
  </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const form = document.querySelector('form[data-filter-form="products-filter"]');
  const container = document.getElementById('productsContainer');
  if (!form || !container) return;

  function buildQuery() {
    const formData = new FormData(form);
    const params = new URLSearchParams();
    for (const [key, value] of formData.entries()) {
      if (value) params.append(key, value);
    }
    return params.toString();
  }

  async function fetchAndRender() {
    container.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status" style="color:#1c1d1e"><span class="visually-hidden">Loading...</span></div></div>';
    try {
      const url = window.location.pathname + '?' + buildQuery() + '&ajax=1';
      const res = await fetch(url, { cache: 'no-store' });
      if (!res.ok) throw new Error('Network response was not ok');
      const data = await res.json();
      renderProducts(data);
    } catch (e) {
      console.error('Fetch error', e);
      container.innerHTML = '<div class="alert alert-danger">Error loading products. Please try again.</div>';
    }
  }

  function escapeHtml(s){ return (s===null||s===undefined)?'':String(s).replace(/[&<>"']/g, function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c];}); }

  function renderProducts(items){
    if (!items || items.length === 0) {
      container.innerHTML = '<div class="alert alert-warning">No products found.</div>';
      return;
    }
    let html = '<div class="row g-4">';
    for (const p of items) {
      const name = escapeHtml(p.title || 'N/A');
      const desc = escapeHtml(p.description || '');
      const priceValue = (typeof p.price !== 'undefined') ? Number(p.price) : 0;
      const discounted = (typeof p.discounted_price !== 'undefined') ? Number(p.discounted_price) : priceValue;
      const price = priceValue.toFixed(2);
      let priceHtml = `<span style='font-size:1.5rem; font-weight:600; color:#1c1d1e;'>$${price}</span>`;
      if (p.discount && Number(p.discount) > 0) {
        priceHtml = `<span style='text-decoration:line-through; color:#64748b; font-size:1.1rem; margin-right:8px;'>$${price}</span>` +
                    `<span style='font-size:1.5rem; font-weight:600; color:#1c1d1e;'>$${discounted.toFixed(2)}</span>`;
      }
      const id = parseInt(p.id) || 0;
      const image = escapeHtml(p.image || '');
      const category = escapeHtml(p.category || '');
      const stock = Number(p.stock || 0);
      const isPreorder = isPreorderEnabled(p);
      const isOutOfStock = stock === 0 && !isPreorder;
      const preorderNote = escapeHtml(getPreorderNote(p));
      let stockHtml = `<span class="text-muted">Stock: ${stock}</span>`;
      if (stock === 0 && isPreorder) {
        stockHtml = `<span class="badge bg-info text-dark">Pre-order Available</span>${preorderNote ? `<div class="small text-muted mt-1">${preorderNote}</div>` : ''}`;
      } else if (isOutOfStock) {
        stockHtml = '<span class="badge bg-danger">Out of Stock</span>';
      }

      const encoded = encodeURIComponent(JSON.stringify(p));
      html += `\n<div class="col-md-4">\n  <div class="card h-100 product-card ${isOutOfStock ? 'out-of-stock' : ''}"
       data-product="${encoded}"
       data-product-id="${id}"
       data-product-title="${encodeURIComponent(name)}"
       data-product-price="${priceValue}"
       data-product-discount="${p.discount || 0}"
       data-product-image="${image}"
      data-product-category="${category}">\n    ${image?`<img src="${image}" class="card-img-top" alt="${name}" style="height: 250px; object-fit: contain; padding: 1rem;">` : ''}\n    <div class="card-body d-flex flex-column">\n      ${category?`<span class="badge bg-secondary mb-2 align-self-start">${category}</span>` : ''}\n      <h5 class="card-title mb-2" style="min-height: 3rem; font-size: 1rem;">${name}</h5>\n      <p class="card-text" style="color: #64748b; flex-grow: 1; font-size: 0.9rem; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical;">${desc.length>100?desc.substr(0,100)+'...':desc}</p>\n      <div class="mt-auto">\n        <div class="d-flex justify-content-between align-items-center">\n          <div class=\"price-wrap\">${priceHtml}</div>\n        </div>\n        <div class="mt-2">${stockHtml}</div>\n      </div>\n    </div>\n  </div>\n</div>`;
    }
    html += '\n</div>';
    container.innerHTML = html;
    if (window.cartifyCurrency) {
      window.cartifyCurrency.applyCurrencyToElements(container);
    }
    if (typeof animateProductCards === 'function') animateProductCards();
  }

  document.getElementById('productsContainer').addEventListener('click', function(evt){
    console.log('Product card clicked');
    const card = evt.target.closest && evt.target.closest('.product-card');
    if (!card) { console.log('No card found'); return; }
    console.log('Card found', card);
    if (card.classList.contains('out-of-stock')) {
      console.log('Card is out of stock, not opening modal');
      return;
    }
    const encoded = card.getAttribute('data-product');
    if (encoded) {
      try {
        const product = JSON.parse(decodeURIComponent(encoded));
        console.log('Using data-product', product);
        openProductModal(product);
        return;
      } catch (e) {
        console.error('Error parsing data-product', e);
      }
    }

    if (card.dataset && card.dataset.productId) {
      const p = {
        id: Number(card.dataset.productId) || 0,
        title: card.dataset.productTitle ? decodeURIComponent(card.dataset.productTitle) : (card.querySelector('.card-title') ? card.querySelector('.card-title').textContent.trim() : ''),
        price: Number(card.dataset.productPrice) || 0,
        discount: Number(card.dataset.productDiscount) || 0,
        image: card.dataset.productImage || '',
        category: card.dataset.productCategory || ''
      };
      console.log('Using dataset fallback', p);
      openProductModal(p);
      return;
    }

    console.log('No product data found');
  });

  form.querySelectorAll('select[name="category"], select[name="sort"]').forEach(function(el){
    function updateHasValue(){
      if (el.name === 'category') {
        el.classList.toggle('has-value', !!el.value && el.value !== 'all');
      } else if (el.name === 'sort') {
        el.classList.toggle('has-value', !!el.value && el.value !== '');
      }
    }
    el.addEventListener('change', function(){ updateHasValue(); fetchAndRender(); });
    updateHasValue();
  });

  document.querySelectorAll('.category-dropdown, .sort-dropdown').forEach(function(wrapper){
    const menu = wrapper.querySelector('.dropdown-menu');
    const labelEl = wrapper.querySelector('.dropdown-label');
    const isCategory = wrapper.classList.contains('category-dropdown');
    const hiddenSelect = form.querySelector('select[name="' + (isCategory ? 'category' : 'sort') + '"]');

    menu.addEventListener('click', function(e){
      const item = e.target.closest('.dropdown-item');
      if (!item) return;
      e.preventDefault();
      const value = (item.dataset.value !== undefined) ? item.dataset.value : item.getAttribute('data-value');
      const label = item.textContent.trim();

      labelEl.textContent = label;
      wrapper.querySelectorAll('.dropdown-item').forEach(i => i.classList.toggle('active', i === item));
      wrapper.classList.toggle('has-value', (isCategory ? (value && value !== 'all') : (value && value !== '')));

      if (hiddenSelect) {
        hiddenSelect.value = value;
        hiddenSelect.querySelectorAll('option').forEach(opt => opt.selected = (opt.value == value));
        hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
      }

      fetchAndRender();
    });
  });

  form.addEventListener('submit', function(ev){ ev.preventDefault(); fetchAndRender(); });

  function animateProductCards(){
    try {
      const containerEl = document.getElementById('productsContainer');
      if (!containerEl) return;
      const cards = Array.from(containerEl.querySelectorAll('.product-card'));
      if (!cards.length) return;

      const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      cards.forEach(c => { c.classList.add('fade-up'); c.classList.remove('show'); c.style.transitionDelay = ''; });

      if (reduce) {
        cards.forEach(c => c.classList.add('show'));
        return;
      }

      const FIRST_SPACING = 140;
      const REST_SPACING = 60;
      const firstCount = Math.min(3, cards.length);

      cards.forEach((card, idx) => {
        let delayMs;
        if (idx < firstCount) {
          delayMs = idx * FIRST_SPACING;
        } else {
          delayMs = firstCount * FIRST_SPACING + (idx - firstCount) * REST_SPACING;
        }
        card.style.transitionDelay = delayMs + 'ms';
      });

      requestAnimationFrame(() => {
        setTimeout(() => cards.forEach(c => c.classList.add('show')), 20);
      });

    } catch (e) { console.error('animateProductCards error', e); }
  }

  animateProductCards();
});
</script>

<div id="productsContainer">
@if (empty($products))
  <div class="alert alert-warning">No products available at the moment.</div>
@else
  <div class="row g-4">
  @foreach ($products as $product)
    @php
      $name = (string) ($product['title'] ?? 'N/A');
      $desc = (string) ($product['description'] ?? '');
      $priceValue = (float) ($product['price'] ?? 0);
      $price = number_format($priceValue, 2);
      $id = (int) ($product['id'] ?? 0);
      $image = (string) ($product['image'] ?? '');
      $category = (string) ($product['category'] ?? '');
      $stock = (int) ($product['stock'] ?? 0);
      $isPreorder = !empty($product['is_preorder']);
      $preorderNote = trim((string) ($product['preorder_note'] ?? ''));
      $isOutOfStock = $stock === 0 && ! $isPreorder;
      $discount = isset($product['discount']) ? (float) $product['discount'] : 0;
      $discounted = ($discount > 0) ? round($priceValue * (1 - $discount / 100), 2) : $priceValue;
      $encodedProduct = rawurlencode(json_encode($product));
    @endphp
    <div class="col-md-4">
      <div class="card h-100 product-card {{ $isOutOfStock ? 'out-of-stock' : '' }}"
         data-product="{{ $encodedProduct }}"
         data-product-id="{{ $id }}"
         data-product-title="{{ rawurlencode($name) }}"
         data-product-price="{{ $priceValue }}"
         data-product-discount="{{ $discount }}"
         data-product-image="{{ $image }}"
         data-product-category="{{ $category }}">
        @if ($image)
        <img src="{{ $image }}" class="card-img-top" alt="{{ $name }}" style="height: 250px; object-fit: contain; padding: 1rem;">
        @endif
        <div class="card-body d-flex flex-column">
          @if ($category)
          <span class="badge bg-secondary mb-2 align-self-start">{{ $category }}</span>
          @endif
          <h5 class="card-title mb-2" style="min-height: 3rem; font-size: 1rem;">{{ $name }}</h5>
          <p class="card-text" style="color: #64748b; flex-grow: 1; font-size: 0.9rem; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical;">{{ strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc }}</p>
          <div class="mt-auto">
            <div class="d-flex justify-content-between align-items-center">
              @if ($discount > 0)
                <div class="price-wrap" style="font-size:1rem;">
                  <span class="price-original text-muted" data-amount-usd="{{ $priceValue }}" style="text-decoration:line-through; margin-right:8px;">${{ $price }}</span>
                  <span class="price-discount" data-amount-usd="{{ $discounted }}" style="font-size:1.25rem; font-weight:600; color:#1c1d1e;">${{ number_format($discounted, 2) }}</span>
                </div>
              @else
                <span class="price-discount" data-amount-usd="{{ $priceValue }}" style="font-size:1.25rem; font-weight:600; color:#1c1d1e;">${{ $price }}</span>
              @endif
            </div>
            <div class="mt-2">
              @if ($stock === 0 && $isPreorder)
                <span class="badge bg-info text-dark">Pre-order Available</span>
                @if ($preorderNote !== '')
                  <div class="small text-muted mt-1">{{ $preorderNote }}</div>
                @endif
              @elseif ($isOutOfStock)
                <span class="badge bg-danger">Out of Stock</span>
              @else
                <span class="text-muted">Stock: {{ $stock }}</span>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  @endforeach
  </div>
@endif
</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="productModalLabel"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-4 align-items-stretch product-modal-layout">
          <div class="col-lg-6 d-flex product-modal-media-column">
            <div class="product-image-stage">
              <div class="product-image-zoom" id="modalImageZoom">
                <img id="modalProductImage" src="" alt="" class="img-fluid rounded">
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="mb-3">
              <span id="modalProductCategory" class="badge bg-secondary mb-2"></span>
              <h4 id="modalProductTitle" class="mb-3"></h4>
              <p id="modalProductDescription" class="text-muted mb-3" style="font-size: 0.95rem;"></p>
            </div>
            <div class="mb-4">
              <h3 id="modalProductPrice" class="mb-0" style="color:#1c1d1e"></h3>
            </div>
            <div class="mb-4">
              <label for="modalProductSize" class="form-label fw-bold">Size</label>
              <select id="modalProductSize" class="form-select"></select>
              <div id="modalProductSizeHint" class="form-text">Choose a size to see the available stock.</div>
            </div>
            @php
              $isAdminView = session('role') && in_array(session('role'), ['staff_user','administrator','admin_sec'], true);
              $isGuestUser = session('role') === 'guest_user';
              $isRegularUser = session('role') === 'regular_user';
            @endphp
            @if (! $isAdminView)
              @if ($isGuestUser)
                <div class="alert alert-warning" role="alert">
                  <i class="fas fa-exclamation-triangle me-2"></i>
                  Please verify your email to add items to cart.
                </div>
              @elseif ($isRegularUser)
              <div class="mb-4">
                <label class="form-label fw-bold">Quantity</label>
                <div class="quantity-selector d-flex align-items-center gap-3">
                  <button type="button" class="btn btn-outline-secondary quantity-btn" id="decreaseQty">
                    <i class="fas fa-minus"></i>
                  </button>
                  <input type="number" id="modalQuantity" class="form-control text-center" value="1" min="1" max="99" style="width: 80px; font-size: 1.1rem; font-weight: 600;">
                  <button type="button" class="btn btn-outline-secondary quantity-btn" id="increaseQty">
                    <i class="fas fa-plus"></i>
                  </button>
                </div>
              </div>
              <button type="button" class="btn btn-primary w-100 btn-lg" id="addToCartBtn" data-product-id="">
                <span id="addToCartBtnText">
                  <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                </span>
                <span id="addToCartBtnLoading" class="d-none">
                  <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Adding...
                </span>
              </button>
              @endif
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.product-card {
  position: relative;
  z-index: 0;
  cursor: pointer;
  transform: translateZ(0) scale(1);
  transform-origin: center center;
  transition: transform 260ms cubic-bezier(.2,.9,.2,1), box-shadow 260ms cubic-bezier(.2,.9,.2,1), border-color 200ms ease-in-out;
  border: 1px solid transparent;
  will-change: transform, box-shadow, border-color;
}
.product-card::after {
  content: "";
  position: absolute;
  left: 10px;
  right: 10px;
  bottom: 10px;
  height: 12px;
  background: radial-gradient(closest-side, rgba(2,6,23,0.12), rgba(2,6,23,0.06) 60%, transparent 70%);
  filter: blur(10px);
  opacity: 0;
  transition: opacity 260ms ease-in-out, transform 260ms ease-in-out;
  border-radius: 8px;
  pointer-events: none;
  z-index: 0;
  transform-origin: center center;
}
.product-card .card-img-top,
.product-card .card-body,
.product-card .card-title,
.product-card .price-wrap,
.product-card .card-text {
  transition: filter 260ms ease-in-out, text-shadow 260ms ease-in-out;
  will-change: filter, text-shadow;
}
.product-card:hover::after {
  opacity: 0;
  transform: none;
}
.product-card:hover {
  transform: none !important;
  box-shadow: none !important;
  border-color: rgba(28,29,30,0.02);
  z-index: 0;
}
.product-card:hover .card-img-top { filter: none; }
.product-card:hover .card-title,
.product-card:hover .price-wrap,
.product-card:hover .card-text {
  text-shadow: none;
  transform: none;
}
.product-card.out-of-stock {
  opacity: 0.6;
  filter: grayscale(50%);
  cursor: not-allowed;
  border: none;
  transform: none;
  box-shadow: none;
}
.product-card.out-of-stock:hover {
  transform: none;
  box-shadow: none;
  border: none;
}

.fade-up {
  opacity: 0;
  transform: translateY(18px) scale(0.998);
  transition: opacity 420ms cubic-bezier(.2,.9,.2,1), transform 420ms cubic-bezier(.2,.9,.2,1);
  will-change: opacity, transform;
}
.fade-up.show {
  opacity: 1;
  transform: translateY(0) scale(1);
}

.quantity-selector .quantity-btn {
  width: 45px;
  height: 45px;
  padding: 0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  border-width: 2px;
  font-weight: 600;
}
.quantity-selector .quantity-btn:hover {
  background: #1c1d1e;
  border-color: #1c1d1e;
  color: white;
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(28, 29, 30, 0.18);
}
.quantity-selector .quantity-btn:active { transform: scale(0.95); }
#addToCartBtn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(28, 29, 30, 0.25) !important; }
#addToCartBtn:active { transform: translateY(0); }
.quantity-selector input::-webkit-outer-spin-button,
.quantity-selector input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
.quantity-selector input[type=number] {
  -moz-appearance: textfield;
  appearance: textfield;
}

.product-image-stage {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  gap: 0.85rem;
  width: 100%;
}

.product-image-zoom {
  position: relative;
  flex: 1 1 auto;
  min-height: clamp(460px, 62vh, 620px);
  padding: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  border-radius: 1rem;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  cursor: zoom-in;
}

.product-image-zoom.is-zoomed {
  cursor: zoom-out;
}

.product-image-zoom img {
  max-width: 100%;
  max-height: 100%;
  width: auto;
  height: auto;
  object-fit: contain;
  user-select: none;
  pointer-events: none;
  transform-origin: center center;
  transition: transform 0.12s ease-out;
  will-change: transform;
}

@media (max-width: 991.98px) {
  .product-image-zoom {
    min-height: 420px;
  }
}
</style>

<div id="productsPageConfig"
     data-cart-url="{{ route('cart', [], false) }}"
     data-can-add-to-cart="{{ session('user_id') && session('role') === 'regular_user' ? '1' : '0' }}"
     data-size-labels='@json($sizeLabels)'
     hidden></div>

<script>
let currentProduct = null;
const productsPageConfig = document.getElementById('productsPageConfig');
const cartUrl = productsPageConfig?.dataset.cartUrl || '';
const canAddToCart = productsPageConfig?.dataset.canAddToCart === '1';
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const productSizeLabels = JSON.parse(productsPageConfig?.dataset.sizeLabels || '{}');
const addToCartButtonMarkup = '<span id="addToCartBtnText"><i class="fas fa-shopping-cart me-2"></i>Add to Cart</span><span id="addToCartBtnLoading" class="d-none"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Adding...</span>';
const preorderButtonMarkup = '<span id="addToCartBtnText"><i class="fas fa-clock me-2"></i>Pre-order Now</span><span id="addToCartBtnLoading" class="d-none"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Adding...</span>';

function isPreorderEnabled(product = {}) {
  const value = product?.is_preorder;
  return value === true || value === 1 || value === '1' || value === 'true';
}

function getPreorderNote(product = {}) {
  return String(product?.preorder_note ?? '').trim();
}

function showCartToast(message, type = 'success') {
  const iconMap = {
    success: 'success',
    danger: 'error',
    warning: 'warning',
    info: 'info'
  };
  const icon = iconMap[type] || 'success';

  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: icon,
    title: message,
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
      toast.addEventListener('mouseenter', Swal.stopTimer)
      toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
  });
}

function openProductModal(product) {
  console.log('Opening modal for', product.title);
  const modalEl = document.getElementById('productModal');
  if (!modalEl) {
    console.error('Modal element not found');
    return;
  }
  currentProduct = product;
  const safeTitle = product.title || 'Product';
  const safeDesc = product.description || 'No description available.';
  const safeImage = product.image || 'https://via.placeholder.com/400x300?text=No+Image';

  document.getElementById('productModalLabel').textContent = safeTitle;
  document.getElementById('modalProductImage').src = safeImage;
  document.getElementById('modalProductImage').alt = safeTitle;
  document.getElementById('modalProductCategory').textContent = product.category || 'Product';
  document.getElementById('modalProductTitle').textContent = safeTitle;
  document.getElementById('modalProductDescription').textContent = safeDesc;
  initializeImageZoom(safeImage);
  const priceEl = document.getElementById('modalProductPrice');
  if (priceEl) {
    const priceAmount = Number(product.price) || 0;
    const discount = Number(product.discount) || 0;
    const discounted = (discount > 0) ? (Math.round(priceAmount * (1 - discount / 100) * 100) / 100) : priceAmount;
    let html = '';
    if (discount > 0) {
      html = `<div style=\"font-size:1.1rem; color:#64748b;\"><span data-amount-usd=\"${priceAmount}\" style=\"text-decoration:line-through;\">$${priceAmount.toFixed(2)}</span></div>` +
             `<div style=\"font-size:1.5rem; font-weight:600; color:#1c1d1e;\"><span data-amount-usd=\"${discounted}\">$${discounted.toFixed(2)}</span> <span class=\"badge bg-success ms-2\">-${discount.toFixed(2)}%</span></div>`;
    } else {
      html = `<div style=\"font-size:1.5rem; font-weight:600; color:#1c1d1e;\"><span data-amount-usd=\"${priceAmount}\">$${priceAmount.toFixed(2)}</span></div>`;
    }
    priceEl.innerHTML = html;
  }
  const qtyEl = document.getElementById('modalQuantity');
  if (qtyEl) {
    qtyEl.value = 1;
    qtyEl.max = isPreorderEnabled(product) ? 99 : Math.max(1, Number(product.stock) || 1);
  }
  const addBtn = document.getElementById('addToCartBtn');
  if (addBtn) {
    addBtn.setAttribute('data-product-id', product.id);
    addBtn.setAttribute('data-is-preorder', '0');
    addBtn.innerHTML = addToCartButtonMarkup;
  }

  populateSizeSelector(product);

  const modal = new bootstrap.Modal(modalEl);
  console.log('Showing modal');
  modal.show();
}

function getProductSizeStockMap(product) {
  const stockMap = {};
  Object.keys(productSizeLabels).forEach((sizeKey) => {
    const value = product?.size_stock_map?.[sizeKey];
    stockMap[sizeKey] = Math.max(0, Number(value || 0));
  });

  return stockMap;
}

function populateSizeSelector(product) {
  const sizeSelect = document.getElementById('modalProductSize');
  if (!sizeSelect) {
    return;
  }

  const stockMap = getProductSizeStockMap(product);
  const preorderEnabled = isPreorderEnabled(product);
  const availableSizes = Object.keys(productSizeLabels).filter((sizeKey) => (stockMap[sizeKey] || 0) > 0);

  if (availableSizes.length === 0 && !preorderEnabled) {
    sizeSelect.innerHTML = '<option value="" selected disabled>All sizes are sold out</option>';
    sizeSelect.disabled = true;
  } else {
    sizeSelect.innerHTML = Object.entries(productSizeLabels)
      .map(([sizeKey, label]) => {
        const stock = stockMap[sizeKey] || 0;
        const disabled = stock === 0 && !preorderEnabled ? 'disabled' : '';
        const suffix = stock > 0 ? ` (${stock} left)` : preorderEnabled ? ' (pre-order)' : ' (sold out)';
        return `<option value="${sizeKey}" ${disabled}>${label}${suffix}</option>`;
      })
      .join('');
    sizeSelect.disabled = false;
    sizeSelect.value = availableSizes[0] || Object.keys(productSizeLabels)[0] || '';
  }

  updateModalStockState();
}

function updateModalStockState() {
  const sizeSelect = document.getElementById('modalProductSize');
  const sizeHint = document.getElementById('modalProductSizeHint');
  const qtyEl = document.getElementById('modalQuantity');
  const addBtn = document.getElementById('addToCartBtn');
  const stockMap = getProductSizeStockMap(currentProduct || {});
  const preorderEnabled = isPreorderEnabled(currentProduct || {});
  const preorderNote = getPreorderNote(currentProduct || {});
  const selectedSize = sizeSelect ? sizeSelect.value : '';
  const selectedStock = selectedSize ? (stockMap[selectedSize] || 0) : 0;
  const totalStock = Object.values(stockMap).reduce((sum, value) => sum + Number(value || 0), 0);
  const isPreorderSelection = preorderEnabled && selectedSize && selectedStock === 0;

  if (sizeHint) {
    if (!selectedSize) {
      sizeHint.textContent = totalStock === 0
        ? (preorderEnabled ? 'This product is available by pre-order.' : 'All sizes are currently sold out.')
        : 'Choose a size to see what is available.';
    } else if (isPreorderSelection) {
      sizeHint.textContent = preorderNote || `${productSizeLabels[selectedSize]} will be placed as a pre-order.`;
    } else {
      sizeHint.textContent = `${productSizeLabels[selectedSize]} stock: ${selectedStock}`;
    }
  }

  if (qtyEl) {
    qtyEl.disabled = !selectedSize || (selectedStock === 0 && !preorderEnabled);
    qtyEl.max = isPreorderSelection ? 99 : Math.max(1, selectedStock || 1);
    if (!isPreorderSelection && selectedStock > 0 && (parseInt(qtyEl.value, 10) || 1) > selectedStock) {
      qtyEl.value = selectedStock;
    }
    if ((selectedStock === 0 && !preorderEnabled) || !selectedSize) {
      qtyEl.value = 1;
    }
  }

  if (addBtn) {
    addBtn.setAttribute('data-product-size', selectedSize || '');
    addBtn.setAttribute('data-is-preorder', isPreorderSelection ? '1' : '0');
    if (!selectedSize || (selectedStock === 0 && !preorderEnabled)) {
      addBtn.disabled = true;
      addBtn.innerHTML = '<i class="fas fa-times me-2"></i>Out of Stock';
    } else {
      addBtn.disabled = false;
      addBtn.innerHTML = isPreorderSelection ? preorderButtonMarkup : addToCartButtonMarkup;
    }
  }
}

function initializeImageZoom(imageUrl) {
  const zoomBox = document.getElementById('modalImageZoom');
  const image = document.getElementById('modalProductImage');
  if (!zoomBox || !image) {
    return;
  }

  const safeUrl = imageUrl ? imageUrl.replace(/'/g, '%27') : '';
  const zoomScale = 2.2;

  const resetZoom = () => {
    const canZoom = Boolean(safeUrl) && window.innerWidth >= 768;

    zoomBox.classList.remove('is-zoomed');
    zoomBox.style.cursor = canZoom ? 'zoom-in' : 'default';
    image.style.transform = 'scale(1)';
    image.style.transformOrigin = 'center center';
  };

  const moveZoom = (event) => {
    if (!safeUrl || window.innerWidth < 768 || !image.complete) {
      resetZoom();
      return;
    }

    const imageRect = image.getBoundingClientRect();
    let x = event.clientX - imageRect.left;
    let y = event.clientY - imageRect.top;

    if (x < 0 || y < 0 || x > imageRect.width || y > imageRect.height) {
      resetZoom();
      return;
    }

    const originX = (x / Math.max(1, imageRect.width)) * 100;
    const originY = (y / Math.max(1, imageRect.height)) * 100;

    zoomBox.classList.add('is-zoomed');
    image.style.transformOrigin = `${originX}% ${originY}%`;
    image.style.transform = `scale(${zoomScale})`;
  };

  resetZoom();
  zoomBox.onmouseenter = moveZoom;
  zoomBox.onmousemove = moveZoom;
  zoomBox.onmouseleave = resetZoom;
  image.onload = resetZoom;
}

document.addEventListener('DOMContentLoaded', function() {
  const decBtn = document.getElementById('decreaseQty');
  const incBtn = document.getElementById('increaseQty');
  const qtyInputEl = document.getElementById('modalQuantity');
  const sizeSelect = document.getElementById('modalProductSize');
  if (decBtn && qtyInputEl) {
    decBtn.addEventListener('click', function() {
      let val = parseInt(qtyInputEl.value) || 1;
      if (val > 1) qtyInputEl.value = val - 1;
    });
  }
  if (incBtn && qtyInputEl) {
    incBtn.addEventListener('click', function() {
      let val = parseInt(qtyInputEl.value) || 1;
      const maxStock = parseInt(qtyInputEl.max) || 99;
      if (val < maxStock && val < 99) qtyInputEl.value = val + 1;
    });
  }
  if (qtyInputEl) {
    qtyInputEl.addEventListener('input', function() {
      let val = parseInt(this.value);
      const maxStock = parseInt(this.max) || 99;
      if (isNaN(val) || val < 1) this.value = 1;
      if (val > maxStock) this.value = maxStock;
      if (val > 99) this.value = 99;
    });
  }
  if (sizeSelect) {
    sizeSelect.addEventListener('change', updateModalStockState);
  }

  const addBtn = document.getElementById('addToCartBtn');
  if (addBtn) addBtn.addEventListener('click', function() {
    if (!canAddToCart) {
      bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
      setTimeout(function() {
        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        loginModal.show();
      }, 300);
      return;
    }

    const productId = this.getAttribute('data-product-id');
    const productSize = this.getAttribute('data-product-size');
    const isPreorder = this.getAttribute('data-is-preorder') === '1';
    const quantity = parseInt((document.getElementById('modalQuantity')||{value:1}).value) || 1;

    if (!productSize) {
      showCartToast('Please choose a size first.', 'warning');
      return;
    }

    const btnText = document.getElementById('addToCartBtnText');
    const btnLoading = document.getElementById('addToCartBtnLoading');
    if (btnText && btnLoading) {
      btnText.classList.add('d-none');
      btnLoading.classList.remove('d-none');
      this.disabled = true;
    }

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('product_size', productSize);
    formData.append('quantity', quantity);
    formData.append('product_name', currentProduct.title || 'Unknown Product');
    formData.append('product_price', currentProduct.price || 0);
    formData.append('product_image', currentProduct.image || '');
    formData.append('add_to_cart', '1');
    if (csrfToken) {
      formData.append('_token', csrfToken);
    }

    fetch(cartUrl, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    })
    .then(async response => {
      const data = await response.json().catch(() => ({}));
      if (!response.ok) {
        throw new Error(data.message || 'Failed to add item to cart.');
      }
      bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
      const sizeLabel = productSizeLabels[productSize] || productSize.toUpperCase();
      const fallbackMessage = isPreorder
        ? `${quantity} × ${currentProduct.title} (${sizeLabel}) added to cart as pre-order!`
        : `${quantity} × ${currentProduct.title} (${sizeLabel}) added to cart!`;
      showCartToast(data.message || fallbackMessage, 'success');
    })
    .catch(error => {
      console.error('Error:', error);
      showCartToast(error.message || 'Failed to add item to cart. Please try again.', 'danger');
    })
    .finally(() => {
      if (btnText && btnLoading) {
        btnText.classList.remove('d-none');
        btnLoading.classList.add('d-none');
        addBtn.disabled = false;
      }
    });
  });
});
</script>
@endsection
