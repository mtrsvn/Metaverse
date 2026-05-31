<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Support\ProductSizeInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function loginPage()
    {
        return view('admin.login');
    }

    public function dashboard()
    {
        if (! $this->isStaff()) {
            return view('empty');
        }

        return view('admin.dashboard');
    }

    public function getStats()
    {
        if (! $this->isStaff()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized']);
        }

        if (! in_array(session('role'), ['staff_user', 'administrator'], true)) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        try {
            $totalProducts = (int) DB::table('products')->count();
            $pendingOrders = (int) DB::table('purchase_orders')->where('status', 'pending')->count();
            $approvedOrders = (int) DB::table('purchase_orders')->where('status', 'approved')->count();
            $totalUsers = (int) DB::table('users')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_products' => $totalProducts,
                    'pending_orders' => $pendingOrders,
                    'approved_orders' => $approvedOrders,
                    'total_users' => $totalUsers,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching stats: ' . $e->getMessage(),
            ]);
        }
    }

    public function auditLog(Request $request)
    {
        $perPage = 10;
        $page = max(1, (int) $request->query('page', 1));

        $total = (int) DB::table('audit_log')->count();
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;

        $rows = DB::table('audit_log')
            ->leftJoin('users', 'audit_log.user_id', '=', 'users.id')
            ->orderByDesc('audit_log.log_time')
            ->offset($offset)
            ->limit($perPage)
            ->get([
                'audit_log.*',
                'users.username',
            ]);

        return view('admin.audit_log', [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        ]);
    }

    public function purchaseRecords(Request $request)
    {
        if (! $this->isAdminSec()) {
            return view('empty');
        }

        ProductSizeInventory::ensureSchema();
        \App\Support\ProductPreorder::ensureSchema();

        $status = (string) $request->query('status', 'all');
        $allowed = ['all', 'pending', 'approved', 'rejected'];
        if (! in_array($status, $allowed, true)) {
            $status = 'all';
        }

        $statusMap = [
            'pending' => 0,
            'approved' => 1,
            'rejected' => 2,
        ];

        $query = DB::table('purchases as p')
            ->join('users as u', 'p.user_id', '=', 'u.id')
            ->leftJoin('products as prod', 'p.product_id', '=', 'prod.id')
            ->select([
                'u.id as user_id',
                'u.username',
                'u.email',
                DB::raw("DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i:%s') AS order_time"),
                DB::raw('p.approved'),
                DB::raw('SUM(p.product_price * p.quantity) AS total_amount'),
                DB::raw('SUM(p.quantity) AS total_qty'),
                DB::raw("GROUP_CONCAT(CONCAT(COALESCE(prod.title, p.product_name), IF(p.product_size IS NOT NULL AND p.product_size <> '', CONCAT(' [', UPPER(p.product_size), ']'), ''), IF(COALESCE(p.is_preorder, 0) = 1, ' (Pre-order)', ''), ' x', p.quantity) ORDER BY p.id SEPARATOR ', ') AS items"),
            ])
            ->groupBy('u.id', 'u.username', 'u.email', 'order_time', 'p.approved')
            ->orderByDesc('order_time');

        if ($status !== 'all') {
            $query->where('p.approved', '=', $statusMap[$status]);
        }

        $records = $query->get();

        return view('admin.purchase_records', [
            'records' => $records,
            'status' => $status,
        ]);
    }

    public function usersManage(Request $request)
    {
        if (! $this->isAdminSec()) {
            return view('empty');
        }

        $messages = [];
        $errors = [];

        $action = (string) $request->input('action', '');

        if ($request->isMethod('post')) {
            if ($action === 'create') {
                $username = trim((string) $request->input('username', ''));
                $email = trim((string) $request->input('email', ''));
                $password = (string) $request->input('password', '');
                $confirm = (string) $request->input('confirm_password', '');

                if ($username === '' || $email === '' || $password === '') {
                    $errors[] = 'Username, email, and password are required.';
                }
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Please provide a valid email address.';
                }
                if ($password !== $confirm) {
                    $errors[] = 'Password and confirmation do not match.';
                }
                if ($password !== '') {
                    $msg = '';
                    if (! $this->validatePasswordRules($password, $msg)) {
                        $errors[] = $msg;
                    }
                }

                if (empty($errors)) {
                    $duplicate = DB::table('users')
                        ->where('username', $username)
                        ->orWhere('email', $email)
                        ->exists();
                    if ($duplicate) {
                        $errors[] = 'Duplicate account found.';
                    }
                }

                if (empty($errors)) {
                    $inserted = DB::table('users')->insert([
                        'username' => $username,
                        'email' => $email,
                        'password_hash' => Hash::make($password),
                        'role' => 'staff_user',
                    ]);

                    if ($inserted) {
                        $messages[] = 'Staff user created successfully.';
                        AuditLogger::logAction((int) session('user_id'), 'Created staff user: ' . $username);
                    } else {
                        $errors[] = 'Failed to create user. The username or email might already be in use.';
                    }
                }
            }

            if ($action === 'update') {
                $userId = (int) $request->input('user_id', 0);
                $username = trim((string) $request->input('username', ''));
                $email = trim((string) $request->input('email', ''));
                $newPassword = (string) $request->input('new_password', '');

                if ($userId <= 0) {
                    $errors[] = 'Invalid user ID.';
                }

                $current = null;
                if (empty($errors)) {
                    $current = DB::table('users')
                        ->where('id', $userId)
                        ->where('role', 'staff_user')
                        ->first(['id', 'username', 'email']);
                    if (! $current) {
                        $errors[] = 'Staff user not found.';
                    }
                }

                if ($username === '' || $email === '') {
                    $errors[] = 'Username and email are required.';
                }
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Please provide a valid email address.';
                }

                if ($newPassword !== '') {
                    $msg = '';
                    if (! $this->validatePasswordRules($newPassword, $msg)) {
                        $errors[] = $msg;
                    }
                }

                if (empty($errors)) {
                    $duplicate = DB::table('users')
                        ->where(function ($q) use ($username, $email) {
                            $q->where('username', $username)->orWhere('email', $email);
                        })
                        ->where('id', '<>', $userId)
                        ->exists();
                    if ($duplicate) {
                        $errors[] = 'Duplicate account found.';
                    }
                }

                if (empty($errors)) {
                    $update = [
                        'username' => $username,
                        'email' => $email,
                    ];
                    if ($newPassword !== '') {
                        $update['password_hash'] = Hash::make($newPassword);
                    }

                    $updated = DB::table('users')
                        ->where('id', $userId)
                        ->where('role', 'staff_user')
                        ->update($update);

                    if ($updated) {
                        $messages[] = 'Staff user updated successfully.';
                        AuditLogger::logAction((int) session('user_id'), 'Updated staff user: ' . $username);
                    } else {
                        $errors[] = 'Failed to update user.';
                    }
                }
            }

            if ($action === 'delete') {
                $userId = (int) $request->input('user_id', 0);
                if ($userId <= 0) {
                    $errors[] = 'Invalid user ID.';
                } else {
                    $deleted = DB::table('users')
                        ->where('id', $userId)
                        ->where('role', 'staff_user')
                        ->delete();
                    if ($deleted) {
                        $messages[] = 'Staff user deleted successfully.';
                        AuditLogger::logAction((int) session('user_id'), 'Deleted staff user ID: ' . $userId);
                    } else {
                        $errors[] = 'Failed to delete user. The account may not exist or cannot be removed.';
                    }
                }
            }
        }

        $staffUsers = DB::table('users')
            ->where('role', 'staff_user')
            ->orderByDesc('id')
            ->get(['id', 'username', 'email', 'role', 'lockout_until', 'failed_logins']);

        return view('admin.users_manage', [
            'messages' => $messages,
            'errors' => $errors,
            'staffUsers' => $staffUsers,
        ]);
    }

    public function users(Request $request)
    {
        if (! $this->isAdminSec()) {
            return view('empty');
        }

        $resetMessage = '';
        $standardReset = 'Password123!';

        if ($request->isMethod('post') && $request->has('reset_id')) {
            $resetId = (int) $request->input('reset_id', 0);
            if ($resetId > 0) {
                DB::table('users')->where('id', $resetId)->update([
                    'password_hash' => Hash::make($standardReset),
                ]);
                $resetMessage = "Password has been reset to: <strong>{$standardReset}</strong>";
            }
        }

        $users = DB::table('users')->get(['id', 'username', 'email', 'role']);

        return view('admin.users', [
            'users' => $users,
            'standardReset' => $standardReset,
            'resetMessage' => $resetMessage,
        ]);
    }

    private function validatePasswordRules(string $password, string &$error): bool
    {
        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
            return false;
        }
        if (! preg_match('/[A-Z]/', $password)) {
            $error = 'Password must contain at least one uppercase letter.';
            return false;
        }
        if (! preg_match('/[a-z]/', $password)) {
            $error = 'Password must contain at least one lowercase letter.';
            return false;
        }
        if (! preg_match('/[0-9]/', $password)) {
            $error = 'Password must contain at least one number.';
            return false;
        }
        if (! preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $error = 'Password must contain at least one special character.';
            return false;
        }
        return true;
    }

    private function isAdminSec(): bool
    {
        return session('role') === 'admin_sec';
    }

    private function isStaff(): bool
    {
        return in_array(session('role'), ['staff_user', 'administrator', 'admin_sec'], true);
    }
}
