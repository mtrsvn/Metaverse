@extends('layouts.app')

@section('content')
<div class="page-header">
  <h2>Your Cart</h2>
</div>

@if (empty($cartItems))
  <div class="card">
    <div class="card-body text-center py-5">
      <p style="color: #64748b; margin-bottom: 1rem;">Your cart is empty.</p>
      <a href="{{ route('products.list', [], false) }}" class="btn btn-primary">Browse Products</a>
    </div>
  </div>
@else
  <style>
    .quantity-control {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .quantity-control .btn-qty {
      width: 32px;
      height: 32px;
      padding: 0;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 1px solid #1c1d1e;
      background: white;
      color: #1c1d1e;
      transition: all 0.2s ease;
      font-weight: 600;
    }
    .qty-display {
      min-width: 40px;
      text-align: center;
      font-weight: 600;
      font-size: 1.1rem;
      color: #1e293b;
    }
    .btn-remove {
      color: #dc2626;
      transition: all 0.2s ease;
    }
    .btn-remove:hover {
      color: #b91c1c;
      transform: scale(1.1);
    }
    .out-of-stock {
      opacity: 0.6;
      background-color: #f8f9fa;
    }
    .out-of-stock .btn-qty {
      opacity: 0.5;
      cursor: not-allowed;
    }
  </style>

  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th></th></tr>
      </thead>
      <tbody>
        @foreach ($cartItems as $pid => $item)
          @php
            $productId = (int) ($item['product_id'] ?? 0);
            $name = (string) ($item['name'] ?? 'Unknown Product');
            $price = (float) ($item['price'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 1);
            $image = (string) ($item['image'] ?? '');
            $size = (string) ($item['size'] ?? 'medium');
            $sizeLabel = (string) ($item['size_label'] ?? 'Medium');
            $subtotal = $price * $qty;
            $stock = (int) ($item['stock'] ?? 0);
            $maxQuantity = (int) ($item['max_quantity'] ?? max(1, $stock));
            $isPreorder = (bool) ($item['is_preorder'] ?? false);
            $preorderNote = trim((string) ($item['preorder_note'] ?? ''));
            $isOutOfStock = (bool) ($item['is_out_of_stock'] ?? ($stock === 0));
          @endphp
          <tr class="{{ $isOutOfStock ? 'out-of-stock' : '' }}">
            <td>
              <div class="d-flex align-items-center gap-3">
                @if ($image)
                <img src="{{ $image }}" alt="{{ $name }}" style="width: 60px; height: 60px; object-fit: contain; border-radius: 0.5rem; border: 1px solid #e2e8f0; padding: 0.25rem;">
                @endif
                <span>
                  <strong>{{ $name }}</strong>
                  <span class="d-block text-muted small mt-1">Size: {{ $sizeLabel }}</span>
                  @if ($isPreorder)
                  <span class="d-block small mt-1">
                    <span class="badge bg-info text-dark">Pre-order</span>
                    @if ($preorderNote !== '')
                    <span class="text-muted ms-1">{{ $preorderNote }}</span>
                    @endif
                  </span>
                  @endif
                </span>
                @if ($isOutOfStock)
                <span class="badge bg-danger">Out of Stock</span>
                @endif
              </div>
            </td>
            <td><span class="text-muted currency-amount" data-amount-usd="{{ $price }}">${{ number_format($price, 2) }}</span></td>
            <td>
              <form method="post" class="quantity-control" onsubmit="return false;">
                @csrf
                <button type="button" class="btn btn-qty js-qty-btn" data-product-id="{{ $productId }}" data-product-size="{{ $size }}" data-new-qty="{{ $qty - 1 }}" {{ ($qty <= 1 || $isOutOfStock) ? 'disabled' : '' }}>
                  <i class="fas fa-minus"></i>
                </button>
                <span class="qty-display">{{ $qty }}</span>
                <button type="button" class="btn btn-qty js-qty-btn" data-product-id="{{ $productId }}" data-product-size="{{ $size }}" data-new-qty="{{ $qty + 1 }}" {{ ($qty >= $maxQuantity || $isOutOfStock) ? 'disabled' : '' }}>
                  <i class="fas fa-plus"></i>
                </button>
              </form>
            </td>
            <td><strong class="currency-amount" data-amount-usd="{{ $subtotal }}" style="color:#1c1d1e">${{ number_format($subtotal, 2) }}</strong></td>
            <td>
              <form method="post" style="margin: 0;">
                @csrf
                <input type="hidden" name="product_id" value="{{ $productId }}">
                <input type="hidden" name="product_size" value="{{ $size }}">
                <button type="submit" name="remove_item" class="btn btn-link btn-remove p-0" title="Remove from cart">
                  <i class="fas fa-trash-alt fa-lg"></i>
                </button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td colspan="3" class="text-end"><strong style="font-size: 1.1rem;">Total:</strong></td>
          <td colspan="2"><strong class="currency-amount" data-amount-usd="{{ $total }}" style="font-size: 1.5rem; color:#1c1d1e">${{ number_format($total, 2) }}</strong></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="d-flex justify-content-end mt-4">
    <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#checkoutModal" {{ $hasOutOfStock ? 'disabled' : '' }}>
      Proceed to Checkout
    </button>
  </div>

  <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="checkoutModalLabel">Confirm Your Order</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to complete this purchase?</p>
          <p class="mb-0"><strong>Total: <span class="currency-amount" data-amount-usd="{{ $total }}">${{ number_format($total, 2) }}</span></strong></p>
          <p class="text-muted" style="font-size: 0.9rem; margin-top: 1rem;">You will receive a shipping confirmation email once your order is dispatched.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <form method="post" style="margin: 0;">
            @csrf
            <button type="submit" name="checkout" class="btn btn-primary">
              <i class="fas fa-check me-2"></i>Confirm Order
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.js-qty-btn').forEach(function (button) {
        button.addEventListener('click', function () {
          updateQuantity(this.dataset.productId, this.dataset.productSize, this.dataset.newQty);
        });
      });
    });

    function updateQuantity(productId, productSize, newQty) {
      if (newQty < 1) newQty = 1;
      if (newQty > 99) newQty = 99;

      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '';

      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      if (token) {
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = token;
        form.appendChild(tokenInput);
      }

      const pidInput = document.createElement('input');
      pidInput.type = 'hidden';
      pidInput.name = 'product_id';
      pidInput.value = productId;

      const sizeInput = document.createElement('input');
      sizeInput.type = 'hidden';
      sizeInput.name = 'product_size';
      sizeInput.value = productSize;

      const qtyInput = document.createElement('input');
      qtyInput.type = 'hidden';
      qtyInput.name = 'quantity';
      qtyInput.value = newQty;

      const updateInput = document.createElement('input');
      updateInput.type = 'hidden';
      updateInput.name = 'update_quantity';
      updateInput.value = '1';

      form.appendChild(pidInput);
  form.appendChild(sizeInput);
      form.appendChild(qtyInput);
      form.appendChild(updateInput);
      document.body.appendChild(form);
      form.submit();
    }
  </script>
@endif

@if (!empty(session('checkout_success')))
<script>
  document.addEventListener('DOMContentLoaded', function(){
    if (typeof showToast === 'function') {
      showToast("Your order has been checked out and is awaiting approval.", 'success');
    }
  });
</script>
@php session()->forget('checkout_success'); @endphp
@endif
@endsection
