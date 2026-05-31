<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\LegacyMailer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        if (! $request->isMethod('post')) {
            return response()->json(['success' => false, 'message' => 'Invalid request method.']);
        }

        $username = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');

        $user = DB::table('users')->where('username', $username)->first();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Wrong credentials.']);
        }

        if ($this->isLockedOut($user)) {
            $minutes = (int) ceil((strtotime($user->lockout_until) - time()) / 60);
            return response()->json(['success' => false, 'message' => "This account is locked. Try again in {$minutes} minutes."]);
        }

        if (Hash::check($password, $user->password_hash)) {
            if ($user->role === 'guest_user' && $user->otp_code !== null) {
                session()->put('otp_user_id', $user->id);
                session()->put('otp_email', $user->email);
                return response()->json([
                    'success' => false,
                    'message' => 'Please verify your email first.',
                    'require_otp' => true,
                ]);
            }// reset

            DB::table('users')->where('id', $user->id)->update([
                'failed_logins' => 0,
                'lockout_until' => null,
            ]);

            session()->put('user_id', $user->id);
            session()->put('role', $user->role);
            session()->put('username', $user->username);

            AuditLogger::logAction((int) $user->id, 'User logged in');

            $redirect = route('products.list', [], false);
            if (in_array($user->role, ['staff_user', 'admin_sec'], true)) {
                $redirect = route('staff.orders', [], false);
            }

            return response()->json(['success' => true, 'redirect' => $redirect]);
        }

        $failed = (int) ($user->failed_logins ?? 0) + 1;
        if ($failed >= 3) {
            $lockout = Carbon::now()->addMinutes(15)->format('Y-m-d H:i:s');
            DB::table('users')->where('id', $user->id)->update([
                'failed_logins' => $failed,
                'lockout_until' => $lockout,
            ]);
            return response()->json(['success' => false, 'message' => 'This account has been locked for 15 minutes due to 3 failed login attempts.']);
        }

        DB::table('users')->where('id', $user->id)->update([
            'failed_logins' => $failed,
        ]);

        return response()->json(['success' => false, 'message' => "Wrong password. Failed attempt {$failed} of 3."]);
    }

    public function adminLogin(Request $request)
    {
        if (! $request->isMethod('post')) {
            return response()->json(['success' => false, 'message' => 'Invalid request method.']);
        }

        $username = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');

        $user = DB::table('users')->where('username', $username)->first();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials.']);
        }

        if ($this->isLockedOut($user)) {
            $minutes = (int) ceil((strtotime($user->lockout_until) - time()) / 60);
            return response()->json(['success' => false, 'message' => "This account is locked. Try again in {$minutes} minutes."]);
        }

        if (Hash::check($password, $user->password_hash)) {
            if (! in_array($user->role, ['staff_user', 'administrator', 'admin_sec'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only staff members and administrators can access this portal.',
                ]);
            }

            DB::table('users')->where('id', $user->id)->update([
                'failed_logins' => 0,
                'lockout_until' => null,
            ]);

            session()->put('user_id', $user->id);
            session()->put('role', $user->role);
            session()->put('username', $user->username);

            AuditLogger::logAction((int) $user->id, 'Admin login - ' . $user->role);

            $redirect = route('admin.dashboard', [], false);
            if (in_array($user->role, ['staff_user', 'admin_sec'], true)) {
                $redirect = route('staff.orders', [], false);
            }

            return response()->json(['success' => true, 'redirect' => $redirect]);
        }

        $failed = (int) ($user->failed_logins ?? 0) + 1;
        if ($failed >= 3) {
            $lockout = Carbon::now()->addMinutes(15)->format('Y-m-d H:i:s');
            DB::table('users')->where('id', $user->id)->update([
                'failed_logins' => $failed,
                'lockout_until' => $lockout,
            ]);
            return response()->json(['success' => false, 'message' => 'This account has been locked for 15 minutes due to 3 failed login attempts.']);
        }

        DB::table('users')->where('id', $user->id)->update([
            'failed_logins' => $failed,
        ]);

        return response()->json(['success' => false, 'message' => "Invalid password. Failed attempt {$failed} of 3."]);
    }

    public function register(Request $request)
    {
        if (! $request->isMethod('post')) {
            return response()->json(['success' => false, 'message' => 'Invalid request method.']);
        }

        $username = trim((string) $request->input('username', ''));
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $confirm = (string) $request->input('confirm_password', '');

        if ($password !== $confirm) {
            return response()->json(['success' => false, 'message' => 'Passwords do not match.']);
        }
        if (strlen($password) < 8) {
            return response()->json(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
        }
        if (! preg_match('/[A-Z]/', $password)) {
            return response()->json(['success' => false, 'message' => 'Password must contain at least one uppercase letter.']);
        }
        if (! preg_match('/[a-z]/', $password)) {
            return response()->json(['success' => false, 'message' => 'Password must contain at least one lowercase letter.']);
        }
        if (! preg_match('/[0-9]/', $password)) {
            return response()->json(['success' => false, 'message' => 'Password must contain at least one number.']);
        }
        if (! preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            return response()->json(['success' => false, 'message' => 'Password must contain at least one special character.']);
        }

        $otp = random_int(100000, 999999);
        $otpExpires = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');

        try {
            $userId = DB::table('users')->insertGetId([
                'username' => $username,
                'email' => $email,
                'password_hash' => Hash::make($password),
                'role' => 'guest_user',
                'otp_code' => (string) $otp,
                'otp_expires' => $otpExpires,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Could not register. Username or email may already be taken.']);
        }

        $send = LegacyMailer::sendOtpEmail($email, $username, (string) $otp);
        if (! ($send['success'] ?? false)) {
            DB::table('users')->where('id', $userId)->delete();
            session()->forget(['otp_user_id', 'otp_email']);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP email. Please try again.',
            ]);
        }

        session()->put('otp_user_id', $userId);
        session()->put('otp_email', $email);

        return response()->json(['success' => true, 'message' => 'A verification code has been sent to your email.', 'email' => $email]);
    }

    public function resendOtp(Request $request)
    {
        if (! $request->isMethod('post')) {
            return response()->json(['success' => false, 'message' => 'Invalid request method.']);
        }

        $userId = (int) session('otp_user_id');
        if ($userId <= 0) {
            return response()->json(['success' => false, 'message' => 'Session error. Please register again.']);
        }

        $user = DB::table('users')->where('id', $userId)->first(['username', 'email']);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not found.']);
        }

        $otp = random_int(100000, 999999);
        $otpExpires = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');

        DB::table('users')->where('id', $userId)->update([
            'otp_code' => (string) $otp,
            'otp_expires' => $otpExpires,
        ]);

        $send = LegacyMailer::sendOtpEmail($user->email, $user->username, (string) $otp);
        if ($send['success'] ?? false) {
            return response()->json(['success' => true, 'message' => 'A new OTP has been sent to your email.']);
        }

        return response()->json(['success' => false, 'message' => 'Failed to send OTP: ' . htmlspecialchars($send['error'] ?? 'unknown')]);
    }

    public function verifyOtp(Request $request)
    {
        if (! $request->isMethod('post')) {
            return response()->json(['success' => false, 'message' => 'Invalid request method.']);
        }

        $userId = (int) session('otp_user_id');
        $code = preg_replace('/\D/', '', (string) $request->input('otp_code', ''));

        if (strlen($code) !== 6) {
            return response()->json(['success' => false, 'message' => 'OTP must be exactly 6 digits.']);
        }

        if ($userId <= 0) {
            return response()->json(['success' => false, 'message' => 'Session error. Please register again.']);
        }

        $user = DB::table('users')->where('id', $userId)->first(['otp_code', 'otp_expires', 'username', 'email']);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not found.']);
        }

        if ($user->otp_code && $user->otp_expires) {
            if ($user->otp_code === $code && strtotime($user->otp_expires) > time()) {
                DB::table('users')->where('id', $userId)->update([
                    'otp_code' => null,
                    'otp_expires' => null,
                    'role' => 'regular_user',
                ]);

                session()->put('user_id', $userId);
                session()->put('role', 'regular_user');
                session()->put('username', $user->username);
                session()->forget('otp_user_id');
                session()->forget('otp_email');

                AuditLogger::logAction($userId, 'OTP verified and user promoted to regular_user');

                return response()->json([
                    'success' => true,
                    'message' => 'Email verified successfully!',
                    'redirect' => route('products.list', [], false),
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Invalid or expired OTP code.']);
        }

        return response()->json(['success' => false, 'message' => 'No OTP to verify. Already verified?']);
    }

    public function verifyOtpPage(Request $request)
    {
        $message = '';

        if ($request->isMethod('post')) {
            $userId = (int) session('otp_user_id');
            $code = preg_replace('/\D/', '', (string) $request->input('otp_code', ''));

            if (strlen($code) !== 6) {
                $message = 'OTP must be exactly 6 digits.';
            } elseif ($userId > 0) {
                $user = DB::table('users')->where('id', $userId)->first(['otp_code', 'otp_expires', 'username']);

                if ($user) {
                    if ($user->otp_code && $user->otp_expires) {
                        if ($user->otp_code === $code && strtotime($user->otp_expires) > time()) {
                            DB::table('users')->where('id', $userId)->update([
                                'otp_code' => null,
                                'otp_expires' => null,
                                'role' => 'regular_user',
                            ]);

                            session()->put('user_id', $userId);
                            session()->put('role', 'regular_user');
                            session()->put('username', $user->username);
                            session()->forget('otp_user_id');
                            session()->forget('otp_email');

                            AuditLogger::logAction($userId, 'OTP verified and user promoted to regular_user');

                            $message = "OTP verified! You can now <a href='" . route('products.list', [], false) . "'>continue shopping</a>.";
                        } else {
                            $message = 'Invalid or expired OTP.';
                        }
                    } else {
                        $message = 'No OTP to verify. Already verified?';
                    }
                } else {
                    $message = 'User not found.';
                }
            } else {
                $message = 'Session error. Please register again.';
            }
        }

        return view('auth.verify_otp', ['message' => $message]);
    }

    public function logout()
    {
        session()->invalidate();
        session()->regenerateToken();

        return redirect(route('products.list', [], false));
    }

    private function isLockedOut($user): bool
    {
        if (! empty($user->lockout_until) && strtotime($user->lockout_until) > time()) {
            return true;
        }
        return false;
    }
}
