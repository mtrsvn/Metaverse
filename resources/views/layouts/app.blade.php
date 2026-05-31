@php
  $role = session('role');
  $isAdminArea = in_array($role, ['staff_user', 'administrator', 'admin_sec'], true);
  $pageTitle = $isAdminArea ? 'Metaverse Records - Staff' : 'Metaverse Records';

  $loginError = session()->pull('login_error');
  $registerError = session()->pull('register_error');
  $registerSuccess = session()->pull('register_success');
  $showOtpModal = session()->pull('show_otp_modal');
  $otpEmail = session('otp_email', 'your email');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $pageTitle }}</title>
  <link rel="icon" href="{{ asset('assets/metaverse.png') }}" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    :root {
      --primary: #1c1d1e;
      --primary-hover: #1c1d1e;
      --bs-primary: #1c1d1e;
      --dark: #1e293b;
      --gray: #64748b;
      --light: #f8fafc;
      --border: #e2e8f0;
      --card-border: rgba(28,29,30,0.06);
    }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: var(--light);
      color: var(--dark);
    }
    .navbar {
      background: transparent !important;
      border-bottom: none !important;
      padding: 1rem 0;
    }
    .navbar-brand {
      display: inline-flex;
      align-items: center;
      padding: 0;
    }
    .navbar-brand img {
      height: 50px;
      width: auto;
      max-width: 100%;
    }
    .brand-text {
      display: none;
      font-size: 2.4rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--dark) !important;
      margin: 0;
      line-height: 1;
    }
    .nav-link {
      color: #1c1d1e !important;
      font-weight: 500;
      padding: 0.5rem 1rem !important;
      transition: color 0.2s;
    }
    .nav-link:hover { color: #1c1d1e !important; }
    .nav-link.active {
      color: var(--primary) !important;
      font-weight: 600;
    }
    #currencyDropdown.dropdown-toggle::after { display: none !important; }

    .dropdown-menu .dropdown-item.active,
    .dropdown-menu .dropdown-item:active {
      background-color: #1c1d1e !important;
      color: #ffffff !important;
    }
    .dropdown-menu .dropdown-item.active .nav-text,
    .dropdown-menu .dropdown-item:active .nav-text {
      color: #ffffff !important;
    }

    .filter-row .form-select option:checked,
    .form-select option:checked {
      background-color: #1c1d1e !important;
      color: #ffffff !important;
    }

    .filter-row .form-select {
      background-color: #ffffff;
      color: #1c1d1e;
      border-color: #e2e8f0;
      box-shadow: none;
    }

    .filter-row .form-select.has-value {
      background-color: #1c1d1e !important;
      color: #ffffff !important;
      border-color: #1c1d1e !important;
      background-image: linear-gradient(45deg, transparent 50%, #fff 50%), linear-gradient(135deg, #fff 50%, transparent 50%), linear-gradient(to right, rgba(0,0,0,0.05), rgba(0,0,0,0.05));
      background-position: calc(100% - 18px) calc(1em + 2px), calc(100% - 13px) calc(1em + 2px), calc(100% - 2.5rem) 0.75em;
      background-size: 5px 5px, 5px 5px, 1px 1.5em;
      background-repeat: no-repeat;
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
      padding-right: 2.5rem !important;
    }

    .filter-row .form-select.has-value:focus { box-shadow: none !important; }

    .dropdown-item:hover,
    .dropdown-item:focus {
      background-color: transparent !important;
      color: inherit !important;
      text-decoration: none !important;
    }

    .dropdown-menu .dropdown-item.active,
    .dropdown-menu .dropdown-item.active:hover,
    .dropdown-menu .dropdown-item.active:focus {
      background-color: #1c1d1e !important;
      color: #ffffff !important;
      text-decoration: none !important;
      box-shadow: none !important;
      cursor: default !important;
    }

    .filter-row .form-select:hover {
      background-color: #ffffff !important;
      color: #1c1d1e !important;
      border-color: #e2e8f0 !important;
      box-shadow: none !important;
    }
    .filter-row .form-select.has-value:hover {
      background-color: #1c1d1e !important;
      color: #ffffff !important;
      border-color: #1c1d1e !important;
      box-shadow: none !important;
    }

    .nav-text { position: relative; display: inline-block; line-height: 1; color: inherit; vertical-align: middle; }
    .nav-text::after {
      content: '';
      position: absolute;
      left: 0;
      bottom: -4px;
      width: 100%;
      height: 2px;
      background: currentColor;
      border-radius: 2px;
      transform: scaleX(0);
      transform-origin: left;
      transition: transform 220ms cubic-bezier(.4,0,.2,1);
      pointer-events: none;
    }
    .nav-link:hover .nav-text::after,
    .nav-link:focus .nav-text::after,
    .nav-link:focus-visible .nav-text::after,
    .nav-link.active .nav-text::after { transform: scaleX(1); }

    @media (max-width: 575.98px) {
      .nav-text::after { display: none !important; }

      .navbar .nav-item.dropdown { position: relative; }
      .navbar .nav-item.dropdown .dropdown-menu {
        position: fixed !important;
        top: calc(56px + 0.5rem);
        right: 12px;
        left: auto;
        z-index: 3000;
        display: none;
        min-width: 160px;
        white-space: nowrap;
        box-shadow: 0 8px 24px rgba(2,6,23,0.4);
      }
      .navbar .nav-item.dropdown.show .dropdown-menu { display: block; }
      .navbar .nav-item.dropdown .dropdown-menu.show { display: block !important; }

      .navbar .nav-link:focus { box-shadow: none !important; outline: none !important; }
    }
    .nav-link.logout-link:hover { color: #dc2626 !important; }
    .btn-primary {
      background: var(--primary);
      border: none;
      padding: 0.6rem 1.5rem;
      font-weight: 500;
      border-radius: 8px;
    }
    .btn-primary:hover { background: var(--primary-hover); }
    .btn-danger {
      border: none;
      padding: 0.6rem 1.5rem;
      font-weight: 500;
      border-radius: 8px;
    }
    .btn-secondary {
      background: #fff;
      border: 1px solid var(--border);
      color: var(--dark);
      padding: 0.6rem 1.5rem;
      font-weight: 500;
      border-radius: 8px;
    }
    .btn-secondary:hover { background: var(--light); border-color: var(--gray); }
    .card {
      border: 1px solid transparent;
      border-radius: 12px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
      background: #fff;
      transition: border-color 180ms ease;
      will-change: transform, border-color;
    }
    .card:hover {
      border-color: rgba(28,29,30,0.5);
    }
    .card-body { padding: 1.5rem; }
    .table {
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .table th {
      background: var(--light);
      font-weight: 600;
      color: var(--gray);
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.5px;
    }
    .table td, .table th { padding: 1rem; border-color: var(--border); }
    .form-control {
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 0.6rem 1rem;
    }
    .form-control:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(28,29,30,0.08);
    }
    .alert {
      border: none;
      border-radius: 10px;
      padding: 1rem 1.25rem;
    }
    h1, h2, h3 { font-weight: 700; color: #1c1d1e; }
    .text-primary { color: #1c1d1e !important; }
    a.text-primary:hover, a.text-primary:focus { color: #1c1d1e !important; }
    .page-header {
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--border);
    }
    .hero {
      background: #fff;
      border-radius: 16px;
      padding: 3rem;
      text-align: center;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
      margin-top: 2rem;
    }
    .hero h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
    .hero p { color: var(--gray); font-size: 1.1rem; }
    .form-card {
      background: #fff;
      border-radius: 16px;
      padding: 2rem;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
      max-width: 420px;
    }
    .password-hint {
      background: var(--light);
      border-radius: 8px;
      padding: 0.75rem 1rem;
      font-size: 0.85rem;
      color: var(--gray);
      margin-top: 0.5rem;
    }
    .password-wrapper {
      position: relative;
    }
    .password-wrapper input.form-control {
      padding-right: 2.5rem;
    }
    .password-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: var(--gray);
      user-select: none;
      font-size: 1rem;
      transition: color 0.2s ease;
    }
    .password-toggle:hover {
      color: var(--dark);
    }
    .password-requirements {
      background: var(--light);
      border-radius: 8px;
      padding: 0.75rem 1rem;
      font-size: 0.85rem;
      margin-top: 0.5rem;
    }
    .password-requirements div {
      margin: 0.25rem 0;
    }
    .requirement-met {
      color: #16a34a;
    }
    .requirement-unmet {
      color: var(--gray);
    }
    .otp-input { width: 56px; height: 56px; text-align: center; font-size: 1.5rem; border-radius: 12px; border: 1px solid #cbd5e1; background: #fff; color: var(--dark); }
    .otp-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(28,29,30,0.12); }
    .otp-input::placeholder { color: var(--gray); opacity: 0.6; }
    .otp-input::-webkit-outer-spin-button,
    .otp-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .otp-input { -moz-appearance: textfield; }
    #otpInputs { gap: 10px; }

    .page-fade {
      opacity: 0;
      transform: translateY(10px);
      transition: opacity 420ms cubic-bezier(.2,.8,.2,1), transform 420ms cubic-bezier(.2,.8,.2,1);
      will-change: opacity, transform;
    }
    .page-loaded .page-fade {
      opacity: 1;
      transform: translateY(0);
    }
    @media (prefers-reduced-motion: reduce) {
      .page-fade,
      .page-loaded .page-fade { transition: none !important; transform: none !important; opacity: 1 !important; }
    }

    .modal-backdrop { z-index: 11150 !important; }
    .modal { z-index: 11200 !important; }
    .swal2-container { z-index: 13000 !important; }
    @media (prefers-reduced-motion: reduce) {
      .modal, .modal-backdrop { transition: none !important; }
    }
  </style>
</head>
<body>
@php $logoSrc = asset('assets/metaversepng.png'); @endphp
<nav class="navbar navbar-expand-lg mb-0">
  <div class="container">
    <a class="navbar-brand" href="{{ route('products.list', [], false) }}">
      <img src="{{ $logoSrc }}" alt="Metaverse Records">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav align-items-center gap-1">
        @if(session('username'))
          @php
            $isAdminUser = in_array(session('role'), ['staff_user','administrator','admin_sec'], true);
            $canSeeAudit = in_array(session('role'), ['administrator','admin_sec'], true);
            $canManageProducts = in_array(session('role'), ['staff_user','administrator','admin_sec'], true);
            $isAdminSec = session('role') === 'admin_sec';
          @endphp
          @if ($canManageProducts)
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('staff.products') ? 'active' : '' }}" href="{{ route('staff.products', [], false) }}"><span class="nav-text">Products</span></a></li>
          @endif
          @if (!$isAdminUser)
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('products.index', 'products.list') ? 'active' : '' }}" href="{{ route('products.list', [], false) }}"><span class="nav-text">Products</span></a></li>
          @endif
          @if ($isAdminSec)
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.users-manage') ? 'active' : '' }}" href="{{ route('admin.users-manage', [], false) }}"><span class="nav-text">Staffs</span></a></li>
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('staff.orders') ? 'active' : '' }}" href="{{ route('staff.orders', [], false) }}"><span class="nav-text">Orders</span></a></li>
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.purchase-records') ? 'active' : '' }}" href="{{ route('admin.purchase-records', [], false) }}"><span class="nav-text">Purchase Records</span></a></li>
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.audit') ? 'active' : '' }}" href="{{ route('admin.audit', [], false) }}"><span class="nav-text">Audit</span></a></li>
          @elseif ($canSeeAudit)
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.audit') ? 'active' : '' }}" href="{{ route('admin.audit', [], false) }}"><span class="nav-text">Audit</span></a></li>
          @elseif (!$isAdminUser)
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('cart') ? 'active' : '' }}" href="{{ route('cart', [], false) }}"><span class="nav-text">Cart</span></a></li>
          @endif
          @if(session('role') === 'staff_user')
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('staff.orders') ? 'active' : '' }}" href="{{ route('staff.orders', [], false) }}"><span class="nav-text">Orders</span></a></li>
          @endif
          <li class="nav-item ms-2"><a class="nav-link logout-link" href="{{ route('auth.logout', [], false) }}"><span class="nav-text">Logout</span></a></li>
        @else
          <li class="nav-item"><a class="nav-link {{ request()->routeIs('products.index', 'products.list') ? 'active' : '' }}" href="{{ route('products.list', [], false) }}"><span class="nav-text">Products</span></a></li>
          <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#loginModal"><span class="nav-text">Login</span></a></li>
          <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#registerModal"><span class="nav-text">Register</span></a></li>
        @endif
        <li class="nav-item dropdown ms-2">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="currencyDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <img class="currency-flag me-2" data-currency-flag src="https://flagsapi.com/US/flat/24.png" alt="USD" style="width:20px;height:auto;border-radius:3px;box-shadow:0 1px 4px rgba(0,0,0,0.15);" onerror="this.style.display='none'; this.nextElementSibling && (this.nextElementSibling.style.display='inline-block');">
            <span class="currency-flag-emoji" data-currency-flag-emoji style="display:none">🇺🇸</span>
            <span class="nav-text ms-1">Currency: <span data-currency-label>USD</span></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="currencyDropdown">
            <li><a class="dropdown-item d-flex align-items-center" href="#" data-currency-option="USD"><img src="https://flagsapi.com/US/flat/24.png" alt="US" style="width:18px;height:auto;margin-right:8px;border-radius:3px;">USD - $</a></li>
            <li><a class="dropdown-item d-flex align-items-center" href="#" data-currency-option="PHP"><img src="https://flagsapi.com/PH/flat/24.png" alt="PH" style="width:18px;height:auto;margin-right:8px;border-radius:3px;">PHP - ₱</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="loginModalLabel">Login</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="loginMessage"></div>
        @if($loginError)
          <div class="alert alert-danger">{{ $loginError }}</div>
        @endif
        <form method="post" action="{{ route('auth.login', [], false) }}" id="loginForm">
          @csrf
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" required>
          </div>
          <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="password-wrapper">
              <input type="password" class="form-control" name="password" id="loginPassword" required>
              <span class="password-toggle" onclick="togglePassword('loginPassword', this)"><i class="fa-regular fa-eye"></i></span>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <p class="text-center mt-3 mb-0" style="color: #64748b;">
          Don't have an account?
          <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal">Register</a>
        </p>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="registerModalLabel">Create Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="registerMessage"></div>
        @if($registerError)
          <div class="alert alert-danger">{{ $registerError }}</div>
        @endif
        @if($registerSuccess)
          <div class="alert alert-success">{{ $registerSuccess }}</div>
        @endif
        <form method="post" action="{{ route('auth.register', [], false) }}" id="registerForm">
          @csrf
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="password-wrapper">
              <input type="password" class="form-control" name="password" id="registerPassword" required onkeyup="checkPasswordStrength()">
              <span class="password-toggle" onclick="togglePassword('registerPassword', this)"><i class="fa-regular fa-eye"></i></span>
            </div>
            <small id="passwordError" class="text-danger" style="display: none;">Password must be at least 8 characters with uppercase, lowercase, number, and special character</small>
          </div>
          <div class="mb-4">
            <label class="form-label">Confirm Password</label>
            <div class="password-wrapper">
              <input type="password" class="form-control" name="confirm_password" id="registerConfirmPassword" required>
              <span class="password-toggle" onclick="togglePassword('registerConfirmPassword', this)"><i class="fa-regular fa-eye"></i></span>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100">Register</button>
        </form>
        <p class="text-center mt-3 mb-0" style="color: #64748b;">
          Already have an account?
          <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Login</a>
        </p>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="otpModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="otpModalLabel">Verify Your Email</h5>
      </div>
      <div class="modal-body">
        <div id="otpMessage"></div>
        <p style="color: #64748b;" id="otpEmailMsg">
          A 6-digit verification code has been sent to <strong id="otpEmailDisplay"></strong>
        </p>
        <form method="post" action="{{ route('auth.otp.verify', [], false) }}" id="otpForm">
          @csrf
          <div class="d-flex justify-content-center mb-2" id="otpInputs">
            <input type="text" class="form-control otp-input" inputmode="numeric" pattern="\d*" maxlength="1" aria-label="Digit 1">
            <input type="text" class="form-control otp-input" inputmode="numeric" pattern="\d*" maxlength="1" aria-label="Digit 2">
            <input type="text" class="form-control otp-input" inputmode="numeric" pattern="\d*" maxlength="1" aria-label="Digit 3">
            <input type="text" class="form-control otp-input" inputmode="numeric" pattern="\d*" maxlength="1" aria-label="Digit 4">
            <input type="text" class="form-control otp-input" inputmode="numeric" pattern="\d*" maxlength="1" aria-label="Digit 5">
            <input type="text" class="form-control otp-input" inputmode="numeric" pattern="\d*" maxlength="1" aria-label="Digit 6">
          </div>
          <input type="hidden" name="otp_code" id="otpHidden">
          <button type="submit" class="btn btn-primary w-100">Verify email</button>
        </form>
        <p class="text-center mt-3 mb-0" style="color: #64748b; font-size: 0.9rem;">
          Didn't receive the code? <a href="#" id="resendOtpLink">Resend OTP</a>
        </p>
      </div>
    </div>
  </div>
</div>

<style>
  #globalToastContainer {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 1080;
  }
</style>

<div id="globalToastContainer" aria-live="polite" aria-atomic="true"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function showToast(message, type = 'danger'){
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
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
      }
    });
  }

  const currencyApiUrl = 'https://api.exchangerate.host/latest?base=USD&symbols=USD,PHP';
  const currencyState = { current: 'USD', rates: { USD: 1, PHP: null }, lastFetched: 0 };

  function formatCurrencyAmount(amountUsd, currencyCode) {
    const code = currencyCode === 'PHP' ? 'PHP' : 'USD';
    const rate = code === 'USD' ? 1 : (currencyState.rates.PHP || 1);
    const formatter = new Intl.NumberFormat('en-US', { style: 'currency', currency: code });
    return formatter.format(amountUsd * rate);
  }

  async function fetchCurrencyRates(force = false) {
    const sixHours = 6 * 60 * 60 * 1000;
    if (currencyState.rates.PHP && !force && (Date.now() - currencyState.lastFetched) < sixHours) return;
    try {
      const res = await fetch(currencyApiUrl, { cache: 'no-store' });
      if (!res.ok) throw new Error('Bad response');
      const data = await res.json();
      const rate = data && data.rates && data.rates.PHP ? Number(data.rates.PHP) : NaN;
      if (!Number.isFinite(rate)) throw new Error('Missing PHP rate');
      currencyState.rates.PHP = rate;
      currencyState.lastFetched = Date.now();
      localStorage.setItem('cartifyRatePHP', String(rate));
    } catch (err) {
      const cached = parseFloat(localStorage.getItem('cartifyRatePHP'));
      if (Number.isFinite(cached)) {
        currencyState.rates.PHP = cached;
      } else if (!currencyState.rates.PHP) {
        currencyState.rates.PHP = 56;
      }
      console.warn('Currency rate fetch failed, using cached/fallback rate.', err);
    }
  }

  function updateCurrencyLabels(selectedCode) {
    document.querySelectorAll('[data-currency-label]').forEach(el => {
      el.textContent = selectedCode;
    });
    document.querySelectorAll('[data-currency-option]').forEach(item => {
      if (!item.dataset) return;
      item.classList.toggle('active', item.dataset.currencyOption === selectedCode);
    });

    const flagEl = document.querySelector('[data-currency-flag]');
    const emojiEl = document.querySelector('[data-currency-flag-emoji]');
    if (flagEl) {
      const cc = selectedCode === 'PHP' ? 'PH' : 'US';
      flagEl.src = `https://flagsapi.com/${cc}/flat/24.png`;
      flagEl.alt = cc.toUpperCase() + ' flag';
      if (emojiEl) {
        emojiEl.textContent = selectedCode === 'PHP' ? '🇵🇭' : '🇺🇸';
        emojiEl.style.display = 'none';
      }
    }
  }

  function applyCurrencyToElements(root = document) {
    const rootNode = root || document;
    const nodes = [];
    if (rootNode.dataset && typeof rootNode.dataset.amountUsd !== 'undefined') {
      nodes.push(rootNode);
    }
    if (rootNode.querySelectorAll) {
      rootNode.querySelectorAll('[data-amount-usd]').forEach(el => nodes.push(el));
    }
    nodes.forEach(el => {
      const amt = parseFloat(el.dataset.amountUsd);
      if (!Number.isFinite(amt)) return;
      el.textContent = formatCurrencyAmount(amt, currencyState.current);
    });
    updateCurrencyLabels(currencyState.current);
  }

  async function setCurrency(currencyCode) {
    const target = currencyCode === 'PHP' ? 'PHP' : 'USD';
    currencyState.current = target;
    localStorage.setItem('cartifyCurrency', target);
    await fetchCurrencyRates();
    applyCurrencyToElements();
  }

  window.cartifyCurrency = {
    setCurrency,
    applyCurrencyToElements,
    format(amount) { return formatCurrencyAmount(amount, currencyState.current); },
    getCurrency() { return currencyState.current; }
  };

  function togglePassword(inputId, iconElement) {
    const input = document.getElementById(inputId);
    const icon = iconElement.querySelector('i');
    if (!icon) return;
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
    }
  }

  function checkPasswordStrength() {
    const password = document.getElementById('registerPassword').value;
    const errorMsg = document.getElementById('passwordError');

    const hasLength = password.length >= 8;
    const hasUppercase = /[A-Z]/.test(password);
    const hasLowercase = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);

    const isValid = hasLength && hasUppercase && hasLowercase && hasNumber && hasSpecial;

    if (password.length > 0 && !isValid) {
      errorMsg.style.display = 'block';
    } else {
      errorMsg.style.display = 'none';
    }
  }

  function updateRequirement(elementId, isMet) {
    const element = document.getElementById(elementId);
    if (isMet) {
      element.className = 'requirement-met';
      element.innerHTML = '✓ ' + element.textContent.substring(2);
    } else {
      element.className = 'requirement-unmet';
      element.innerHTML = '✗ ' + element.textContent.substring(2);
    }
  }

  document.addEventListener('DOMContentLoaded', function(){
    const productsUrl = @json(route('products.list', [], false));
    const otpResendUrl = @json(route('auth.otp.resend', [], false));
    const loginModalOnLoad = @json((bool) $loginError);
    const registerModalOnLoad = @json((bool) $registerError);
    const showOtpModalOnLoad = @json((bool) $showOtpModal);
    const otpEmailValue = @json($otpEmail);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const savedCurrency = localStorage.getItem('cartifyCurrency') || 'USD';
    if (window.cartifyCurrency) {
      window.cartifyCurrency.setCurrency(savedCurrency).catch(() => {
        window.cartifyCurrency.applyCurrencyToElements();
      });
    }
    document.querySelectorAll('[data-currency-option]').forEach(item => {
      item.addEventListener('click', function(e){
        e.preventDefault();
        if (window.cartifyCurrency) {
          window.cartifyCurrency.setCurrency(this.dataset.currencyOption || 'USD');
        }
      });
    });

    const loginForm = document.getElementById('loginForm');
    if(loginForm){
      loginForm.addEventListener('submit', function(e){
        e.preventDefault();
        const formData = new FormData(loginForm);
        fetch(loginForm.action, {
          method: 'POST',
          body: formData,
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json' }
        }).then(r => r.json())
        .then(data => {
          if(data.success){
            window.location = data.redirect || productsUrl;
          } else if(data.require_otp) {
            const loginModal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
            if(loginModal) loginModal.hide();
            document.getElementById('otpEmailDisplay').textContent = 'your email';
            const otpModal = new bootstrap.Modal(document.getElementById('otpModal'));
            otpModal.show();
            showToast(data.message || 'Please verify your OTP', 'warning');
          } else {
            showToast(data.message || 'Login failed', 'danger');
          }
        }).catch(() => {
          showToast('An error occurred. Please try again.', 'danger');
        });
      });
    }

    const registerForm = document.getElementById('registerForm');
    if(registerForm){
      registerForm.addEventListener('submit', function(e){
        e.preventDefault();
        const regBtn = registerForm.querySelector('button[type="submit"]');
        const originalBtnHtml = regBtn ? regBtn.innerHTML : '';
        if (regBtn) {
          regBtn.disabled = true;
          regBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Registering...';
        }
        const formData = new FormData(registerForm);
        fetch(registerForm.action, {
          method: 'POST',
          body: formData,
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json' }
        }).then(r => r.json())
        .then(data => {
          if(data.success){
            const regModal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
            if(regModal) regModal.hide();
            document.getElementById('otpEmailDisplay').textContent = data.email || 'your email';
            const otpModal = new bootstrap.Modal(document.getElementById('otpModal'));
            otpModal.show();
            showToast(data.message || 'OTP sent successfully!', 'success');
          } else {
            showToast(data.message || 'Registration failed', 'danger');
          }
        }).catch(() => {
          showToast('An error occurred. Please try again.', 'danger');
        }).finally(() => {
          if (regBtn) {
            regBtn.disabled = false;
            regBtn.innerHTML = originalBtnHtml || 'Register';
          }
        });
      });
    }

    const otpForm = document.getElementById('otpForm');
    const otpInputsContainer = document.getElementById('otpInputs');
    const otpHidden = document.getElementById('otpHidden');
    if (otpInputsContainer) {
      const inputs = otpInputsContainer.querySelectorAll('.otp-input');
      inputs.forEach((input, idx) => {
        input.addEventListener('input', (e) => {
          e.target.value = e.target.value.replace(/\D/g, '');
          if (e.target.value.length === 1 && idx < inputs.length - 1) {
            inputs[idx + 1].focus();
          }
          otpHidden.value = Array.from(inputs).map(i => i.value || '').join('');
        });
        input.addEventListener('keydown', (e) => {
          if (e.key === 'Backspace' && !e.target.value && idx > 0) {
            inputs[idx - 1].focus();
          }
        });
        input.addEventListener('paste', (e) => {
          const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
          if (text.length === inputs.length) {
            e.preventDefault();
            for (let i = 0; i < inputs.length; i++) {
              inputs[i].value = text[i];
            }
            otpHidden.value = text;
          }
        });
      });
    }
    if(otpForm){
      otpForm.addEventListener('submit', function(e){
        e.preventDefault();
        if (otpInputsContainer) {
          const inputs = otpInputsContainer.querySelectorAll('.otp-input');
          otpHidden.value = Array.from(inputs).map(i => i.value || '').join('');
        }
        if (!otpHidden.value || otpHidden.value.length !== 6 || /\D/.test(otpHidden.value)) {
          showToast('Please enter a valid 6-digit code.', 'warning');
          return;
        }
        const formData = new FormData(otpForm);
        fetch(otpForm.action, {
          method: 'POST',
          body: formData,
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json' }
        }).then(r => r.json())
        .then(data => {
          if(data.success){
            showToast('Email verified successfully! Redirecting...', 'success');
            setTimeout(() => {
              window.location = data.redirect || productsUrl;
            }, 1500);
          } else {
            showToast(data.message || 'Invalid OTP', 'danger');
          }
        }).catch(() => {
          showToast('An error occurred. Please try again.', 'danger');
        });
      });
    }

    const resendLink = document.getElementById('resendOtpLink');
    if(resendLink){
      resendLink.addEventListener('click', function(e){
        e.preventDefault();
        fetch(otpResendUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}
        }).then(r => r.json())
        .then(data => {
          if(data.success){
            showToast('OTP has been resent to your email', 'success');
          } else {
            showToast(data.message || 'Failed to resend OTP', 'danger');
          }
        }).catch(() => {
          showToast('An error occurred. Please try again.', 'danger');
        });
      });
    }

    if (loginModalOnLoad) {
      var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
      loginModal.show();
    }
    if (registerModalOnLoad) {
      var registerModal = new bootstrap.Modal(document.getElementById('registerModal'));
      registerModal.show();
    }
    if (showOtpModalOnLoad) {
      document.getElementById('otpEmailDisplay').textContent = otpEmailValue || 'your email';
      var otpModal = new bootstrap.Modal(document.getElementById('otpModal'));
      otpModal.show();
    }

    try {
      if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.documentElement.classList.add('page-loaded');
      } else {
        window.requestAnimationFrame(function(){ document.documentElement.classList.add('page-loaded'); });
      }
    } catch (e) { document.documentElement.classList.add('page-loaded'); }

    try {
      document.querySelectorAll('.modal').forEach(function(m){
        if (m && m.parentElement && m.parentElement !== document.body) document.body.appendChild(m);
      });
    } catch(err) { }
  });
</script>

<div class="container page-fade">
  @yield('content')
</div>

<footer class="site-footer mt-5" role="contentinfo" style="background:#1c1d1e; color:#fff; border-top:1px solid #ffffff20;">
  <div class="container py-4">
    <div class="row gy-4">
      <div class="col-12 col-md-3 text-md-start">
        <div class="footer-brand">
          <div class="brand-title">METAVERSE RECORDS™</div>
          <div class="brand-motto">Secure the Metaverse.</div>
        </div>
      </div>

      <div class="col-12 col-md-3 text-md-center">
        <h6 class="text-uppercase small mb-3">Directives</h6>
        <ul class="list-unstyled footer-links mb-0">
          <li><a href="{{ route('landing', [], false) }}">Home</a></li>
          <li><a href="{{ route('products.list', [], false) }}">Products</a></li>
        </ul>
      </div>

      <div class="col-12 col-md-3 text-md-center">
        <h6 class="text-uppercase small mb-3 ">Currency</h6>
        <ul class="list-unstyled footer-links mb-0">
          <li><a href="#" data-currency-option="USD" class="footer-currency-link">USD</a></li>
          <li><a href="#" data-currency-option="PHP" class="footer-currency-link">PHP</a></li>
        </ul>
      </div>

      <div class="col-12 col-md-3 text-md-start">
        <h6 class="text-uppercase small mb-3">CREDITS &amp; INSPIRATION</h6>
        <p class="small mb-0 credits-text">Shirt imagery uses AI-generated images, and free mockup template from Envato and Züli.</p>
      </div>
    </div>
  </div>

  <div class="legal-bar" style="border-top:1px solid #ffffff10; background: rgba(255,255,255,0.01);">
    <div class="container py-3">
      <div class="row">
        <div class="col-12">
          <p class="small mb-2"><strong>FOR EDUCATIONAL PURPOSES ONLY.</strong> This website is a non-commercial dummy e-commerce project for a educational purposes only. <strong>NO ITEMS ARE FOR SALE.</strong> No transactions will be processed, and no physical goods will be shipped. This is a demonstration only.</p>

        </div>
      </div>
    </div>
  </div>
</footer>

<style>
.site-footer { font-family: inherit; color: #fff; font-size: 0.95rem; }
.site-footer * { font-size: inherit !important; }

.brand-title { font-weight: 700; letter-spacing: 0.03em; }
.brand-motto { opacity: 0.75; margin-top: 6px; color: rgba(255,255,255,0.90); }

.site-footer h6 { color: rgba(255,255,255,0.95); font-weight: 700; font-size: inherit; }
.footer-links li { margin-bottom: .45rem; }
.footer-links a, .credits-text { color: rgba(255,255,255,0.90); text-decoration: none; transition: color .18s ease, text-shadow .22s ease; }
.footer-currency-link.active {
  background: #1c1d1e;
  color: #ffffff !important;
  font-weight: 700;
  padding: 0.35rem 0.6rem;
  border-radius: 8px;
  text-shadow: 0 6px 18px rgba(0,229,255,0.04);
}
.footer-links a:hover, .footer-links a:focus { color: #e6ffff; text-shadow: 0 6px 18px rgba(0,229,255,0.04), 0 0 8px rgba(0,229,255,0.06); outline: none; }
.legal-bar { border-top: 1px solid #ffffff20; }
.legal-bar p { opacity: .95; font-size: inherit; margin-bottom: 0; }
@media (max-width: 767.98px) {
  .site-footer { text-align: center; }
  .site-footer .d-flex { flex-direction: column !important; gap: .5rem; }
}
</style>

<script>
(function(){
  function updateFooterCurrencyActive(code){
    document.querySelectorAll('.footer-currency-link').forEach(a=>{
      var val = (a.dataset.currencyOption || a.textContent || '').trim();
      if (val === (code || 'USD')) a.classList.add('active'); else a.classList.remove('active');
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    var current = (window.cartifyCurrency && typeof cartifyCurrency.getCurrency === 'function') ? cartifyCurrency.getCurrency() : (localStorage.getItem('cartifyCurrency')||'USD');
    updateFooterCurrencyActive(current);

    document.querySelectorAll('.footer-currency-link').forEach(a=>{
      a.addEventListener('click', function(e){
        e.preventDefault();
        var code = this.dataset.currencyOption || this.textContent.trim();
        if (typeof setCurrency === 'function') setCurrency(code);
        updateFooterCurrencyActive(code);
      });
    });

    if (window.cartifyCurrency && typeof window.cartifyCurrency.setCurrency === 'function'){
      const orig = window.cartifyCurrency.setCurrency.bind(window.cartifyCurrency);
      window.cartifyCurrency.setCurrency = async function(code){
        const result = await orig(code);
        try{ updateFooterCurrencyActive(window.cartifyCurrency.getCurrency()); }catch(e){}
        return result;
      }
    }

    window.addEventListener('storage', function(evt){ if (evt.key === 'cartifyCurrency') updateFooterCurrencyActive(evt.newValue||'USD'); });
  });
})();
</script>

<style>
  html, body {
    height: 100%;
  }
  body {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }
  .container {
    flex: 1 0 auto;
  }
  .site-footer {
    flex-shrink: 0;
  }
</style>

</body>
</html>
