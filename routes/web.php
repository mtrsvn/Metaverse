<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffOrdersApiController;
use App\Http\Controllers\StaffProductsApiController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'show'])->name('landing');
Route::get('/products', [ProductController::class, 'index'])->name('products.list');
Route::get('/landing', [LandingController::class, 'show'])->name('landing');
Route::match(['get', 'post'], '/cart', [CartController::class, 'handle'])->name('cart');

Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
Route::post('/auth/admin-login', [AuthController::class, 'adminLogin'])->name('auth.adminLogin');
Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/auth/otp/verify', [AuthController::class, 'verifyOtp'])->name('auth.otp.verify');
Route::match(['get', 'post'], '/auth/otp', [AuthController::class, 'verifyOtpPage'])->name('auth.otp.page');
Route::post('/auth/otp/resend', [AuthController::class, 'resendOtp'])->name('auth.otp.resend');
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('auth.logout');

Route::get('/admin/login', [AdminController::class, 'loginPage'])->name('admin.login');
Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
Route::get('/admin/stats', [AdminController::class, 'getStats'])->name('admin.stats');
Route::get('/admin/audit-log', [AdminController::class, 'auditLog'])->name('admin.audit');
Route::get('/admin/purchase-records', [AdminController::class, 'purchaseRecords'])->name('admin.purchase-records');
Route::match(['get', 'post'], '/admin/staff-users', [AdminController::class, 'usersManage'])->name('admin.users-manage');
Route::match(['get', 'post'], '/admin/users', [AdminController::class, 'users'])->name('admin.users');

Route::match(['get', 'post'], '/staff/orders', [StaffController::class, 'approve'])->name('staff.orders');
Route::get('/staff/products', [StaffController::class, 'productsManage'])->name('staff.products');

Route::match(['get', 'post'], '/api/staff/products', [StaffProductsApiController::class, 'handle'])->name('staff.products.api');
Route::match(['get', 'post'], '/api/staff/orders', [StaffOrdersApiController::class, 'handle'])->name('staff.orders.api');

Route::redirect('/index.php', '/');
Route::redirect('/products.php', '/');
Route::redirect('/products/products.php', '/');
Route::redirect('/landing.php', '/landing');
Route::redirect('/home.php', '/landing');
Route::redirect('/products/cart.php', '/cart');
Route::redirect('/auth/verify_otp.php', '/auth/otp');
Route::redirect('/auth/logout.php', '/logout');
Route::redirect('/admin.php', '/admin/login');
Route::redirect('/admin/dashboard.php', '/admin/dashboard');
Route::redirect('/admin/get_stats.php', '/admin/stats');
Route::redirect('/admin/audit_log.php', '/admin/audit-log');
Route::redirect('/admin/purchase_records.php', '/admin/purchase-records');
Route::redirect('/admin/users_manage.php', '/admin/staff-users');
Route::redirect('/admin/users.php', '/admin/users');
Route::redirect('/staff/approve.php', '/staff/orders');
Route::redirect('/staff/products_manage.php', '/staff/products');
Route::redirect('/staff/products_api.php', '/api/staff/products');
Route::redirect('/staff/orders_api.php', '/api/staff/orders');
