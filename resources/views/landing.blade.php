@php
  $loginError = session()->pull('login_error');
  $registerError = session()->pull('register_error');
  $registerSuccess = session()->pull('register_success');
  $showOtpModal = session()->pull('show_otp_modal');
  $otpEmail = session('otp_email', 'your email');
@endphp
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Metaverse Records</title>
  <link rel="icon" href="{{ asset('assets/metaverse.png') }}" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}">
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
    }
      .navbar, .navbar * {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
      color: var(--dark) !important;
    }
    .navbar {
      background: transparent !important;
      border-bottom: none;
      padding: 1rem 0;
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      z-index: 2000;
    }
    .navbar-brand { display: inline-flex; align-items: center; padding: 0; }
    .navbar-brand img { height: 50px; width: auto; max-width: 100%; }
    body.landing-root .nav-link {
      color: rgba(255,255,255,0.92) !important;
      font-weight: 500;
      padding: 0.5rem 1rem !important;
      position: relative;
      transition: color 250ms cubic-bezier(.4,0,.2,1);
    }

    body.landing-root .nav-link::after { display: none !important; }
    .nav-text { position: relative; display: inline-block; }
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

    body.landing-root .nav-link:hover { color: #ffffff !important; }
    .nav-link.logout-link:hover { color: #ff6b6b !important; }

    body.landing-root .dropdown-menu {
      border: none !important;
    }
    body.landing-root .dropdown-menu .dropdown-item { position: relative; }
    body.landing-root .dropdown-menu .dropdown-item.active,
    body.landing-root .dropdown-menu .dropdown-item:active {
      background-color: #1c1d1e !important;
      color: #ffffff !important;
    }
    body.landing-root .dropdown-menu .dropdown-item.active .nav-text,
    body.landing-root .dropdown-menu .dropdown-item:active .nav-text {
      color: #ffffff !important;
    }
    body.landing-root .dropdown-menu .dropdown-item:hover,
    body.landing-root .dropdown-menu .dropdown-item:focus {
      background-color: rgba(255,255,255,0.06);
      color: #ffffff;
    }
    body.landing-root .dropdown-menu .dropdown-item .nav-text::after { display: none !important; }

    body.landing-root .navbar .nav-link,
    body.landing-root .navbar .nav-link .nav-text,
    body.landing-root #currencyDropdown,
    body.landing-root #currencyDropdown span[data-currency-label],
    body.landing-root .navbar .dropdown-toggle {
      color: #ffffff !important;
    }
    body.landing-root .dropdown-toggle::after {
      border-top-color: rgba(255,255,255,0.95) !important;
    }
    body.landing-root #currencyDropdown.dropdown-toggle::after { display: none !important; }
    .btn-primary {
      background: var(--primary);
      border: none;
      padding: 0.6rem 1.5rem;
      font-weight: 500;
      border-radius: 8px;
    }
    .btn-primary:hover { background: var(--primary-hover); }
    .btn-secondary {
      background: #fff;
      border: 1px solid var(--border);
      color: var(--dark);
      padding: 0.6rem 1.5rem;
      font-weight: 500;
      border-radius: 8px;
    }
    .btn-secondary:hover { background: var(--light); border-color: var(--gray); }

    .page-fade {
      opacity: 0;
      transform: translateY(10px);
      transition: opacity 420ms cubic-bezier(.2,.8,.2,1), transform 420ms cubic-bezier(.2,.8,.2,1);
      will-change: opacity, transform;
    }
    .page-loaded .page-fade { opacity: 1; transform: translateY(0); }

    .hero__content .stagger {
      opacity: 0;
      transform: translateY(10px) scale(0.998);
      transition: opacity 520ms cubic-bezier(.2,.8,.2,1), transform 520ms cubic-bezier(.2,.8,.2,1);
      will-change: opacity, transform;
    }
    .page-loaded .hero__content .stagger {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
    .hero__title.stagger { transition-delay: 120ms; }
    .hero__subtitle.stagger { transition-delay: 220ms; }
    .hero__ctas.stagger { transition-delay: 340ms; }
    .hero__ctas .hero__btn { transition-delay: 420ms; }

    .hero__bg, .hero__overlay {
      opacity: 0;
      transform: scale(1.02);
      transition: opacity 900ms cubic-bezier(.2,.8,.2,1), transform 900ms cubic-bezier(.2,.8,.2,1);
      will-change: opacity, transform;
    }
    .page-loaded .hero__bg, .page-loaded .hero__overlay { opacity: 1; transform: scale(1); }

    @media (prefers-reduced-motion: reduce) {
      .page-fade, .page-loaded .page-fade, .hero__bg, .hero__overlay, .hero__content .stagger { transition: none !important; transform: none !important; opacity: 1 !important; }
    }
  </style>
</head>
<body class="landing-root">
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="{{ route('products.list', [], false) }}">
      <img src="{{ asset('assets/metaversepngwhite.png') }}" alt="Metaverse Records">
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

  <main class="hero">
    <div class="hero__bg" style="background-image: url('{{ asset('assets/background.png') }}');" role="img" aria-label="Futuristic background"></div>
    <div class="hero__overlay"></div>

    <div class="hero__content container text-center page-fade" aria-hidden="false">
      <h1 class="hero__title stagger">WELCOME TO THE METAVERSE</h1>
      <p class="hero__subtitle stagger">Authorized gear for rift stabilization and dimensional auditing. Reimagining luxury streetwear through the lens of multiversal research.</p>
      <div class="hero__ctas stagger">
        <a href="{{ route('products.list', [], false) }}" class="hero__btn" id="enterBtn" role="button">ENTER TERMINAL</a>
      </div>
    </div>
  </main>

  <div id="globalToastContainer" aria-live="polite" aria-atomic="true"></div>

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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    (function(){
      function setActiveCurrencyLabel(code){
        document.querySelectorAll('[data-currency-label]').forEach(el => el.textContent = code);
        document.querySelectorAll('[data-currency-option]').forEach(item => {
          try { item.classList.toggle('active', (item.dataset.currencyOption || '') === code); } catch(e){}
        });

        const flagEl = document.querySelector('[data-currency-flag]');
        const emojiEl = document.querySelector('[data-currency-flag-emoji]');
        if (flagEl) {
          const cc = code === 'PHP' ? 'PH' : 'US';
          flagEl.src = `https://flagsapi.com/${cc}/flat/24.png`;
          flagEl.alt = cc.toUpperCase() + ' flag';
          if (emojiEl) {
            emojiEl.textContent = code === 'PHP' ? '🇵🇭' : '🇺🇸';
            emojiEl.style.display = 'none';
          }
        }
      }

      var saved = localStorage.getItem('cartifyCurrency') || 'USD';
      setActiveCurrencyLabel(saved);

      document.querySelectorAll('[data-currency-option]').forEach(function(el){
        el.addEventListener('click', function(evt){
          evt.preventDefault();
          var code = this.dataset.currencyOption || 'USD';
          localStorage.setItem('cartifyCurrency', code);
          setActiveCurrencyLabel(code);
        });
      });
    })();

    (function(){
      try{
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
          document.documentElement.classList.add('page-loaded');
        } else {
          window.addEventListener('load', function(){ window.requestAnimationFrame(function(){ document.documentElement.classList.add('page-loaded'); }); });
        }
      } catch(e){ document.documentElement.classList.add('page-loaded'); }
    })();
  </script>

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
        timerProgressBar: true
      });
    }

    function togglePassword(inputId, iconElement) {
      const input = document.getElementById(inputId);
      const icon = iconElement.querySelector('i');
      if (!icon || !input) return;
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

    document.addEventListener('DOMContentLoaded', function(){
      const productsUrl = @json(route('products.list', [], false));
      const otpResendUrl = @json(route('auth.otp.resend', [], false));
      const loginModalOnLoad = @json((bool) $loginError);
      const registerModalOnLoad = @json((bool) $registerError);
      const showOtpModalOnLoad = @json((bool) $showOtpModal);
      const otpEmailValue = @json($otpEmail);
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

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
    });
  </script>
</body>
</html>
