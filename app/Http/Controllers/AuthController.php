<?php

namespace App\Http\Controllers;

use App\Models\CustomerProfile;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * AuthController — Quản lý các màn hình xác thực công khai
 *
 * Căn cứ ThietKeGiaoDien.md:
 *  - SCR-PUB-06: Đăng nhập
 *  - SCR-PUB-07: Đăng ký
 *  - SCR-PUB-08: Quên mật khẩu
 *  - SCR-PUB-09: Đặt lại mật khẩu
 *  - SCR-PUB-10: Xác thực email
 */
class AuthController extends Controller
{
    /**
     * GET /dang-nhap (login) — SCR-PUB-06
     */
    public function loginForm(Request $request): View
    {
        $intended = $request->query('intended', '');

        return view('public.login', compact('intended'));
    }

    /**
     * POST /dang-nhap (xử lý đăng nhập)
     */
    public function login(Request $request, AuditService $audit): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email' => 'Email không đúng định dạng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $user = User::query()->whereRaw('LOWER(email) = ?', [strtolower($credentials['email'])])->first();
        if ($user?->locked_until?->isFuture()) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Tài khoản tạm khóa do đăng nhập sai nhiều lần. Vui lòng thử lại sau 15 phút.']);
        }
        if ($user && $user->status !== 'ACTIVE') {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Tài khoản đã bị vô hiệu hóa. Vui lòng liên hệ phòng khám.']);
        }
        if ($user && SystemSetting::valueOf('email_verification_required', false) && ! $user->hasVerifiedEmail()) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Vui lòng xác thực email trước khi đăng nhập.']);
        }

        if ($user && Hash::check($credentials['password'], $user->password)) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            $user->forceFill(['last_login_at' => now(), 'failed_login_count' => 0, 'locked_until' => null])->saveQuietly();
            $audit->record('auth.login_succeeded', $user, actor: $user);

            $intended = $request->string('intended')->toString();
            if ($intended !== '' && str_starts_with($intended, '/') && ! str_starts_with($intended, '//')) {
                return redirect()->to($intended);
            }

            return redirect()->intended(match (strtolower((string) $user->role)) {
                'admin' => route('admin.dashboard'),
                'staff' => route('staff.dashboard'),
                'specialist' => route('specialist.dashboard'),
                default => route('customer.dashboard'),
            });
        }

        if ($user) {
            $failedCount = $user->failed_login_count + 1;
            $user->forceFill(['failed_login_count' => $failedCount, 'locked_until' => $failedCount >= 5 ? now()->addMinutes(15) : null])->saveQuietly();
            $audit->record('auth.login_failed', $user, new: ['failed_login_count' => $failedCount]);
        }

        // Thông báo lỗi trung tính theo §1.7 NFR-PRV
        return back()->withInput($request->only('email'))->withErrors([
            'email' => 'Email hoặc mật khẩu không đúng.',
        ]);
    }

    /**
     * GET /dang-ky (register) — SCR-PUB-07
     */
    public function registerForm(): View
    {
        return view('public.register');
    }

    /**
     * POST /dang-ky (xử lý đăng ký)
     */
    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'dob' => 'required|date|before:-18 years',
            'phone' => 'nullable|string|regex:/^[0-9+\s\-]{9,15}$/',
            'agree_terms' => 'accepted',
        ], [
            'name.required' => 'Vui lòng nhập họ và tên (2-100 ký tự).',
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Địa chỉ email này đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải chứa ít nhất 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'dob.required' => 'Vui lòng chọn ngày sinh.',
            'dob.before' => 'Hiện tại phòng khám chỉ hỗ trợ khách hàng từ 18 tuổi trở lên.',
            'agree_terms.accepted' => 'Bạn cần tích chọn đồng ý với chính sách quyền riêng tư để tiếp tục.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'customer',
            'phone' => $request->phone,
            'status' => 'ACTIVE',
            'email_verified_at' => SystemSetting::valueOf('email_verification_required', false) ? null : now(),
        ]);
        CustomerProfile::query()->create(['user_id' => $user->id, 'customer_code' => 'KH-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT), 'date_of_birth' => $request->date('dob'), 'privacy_policy_version' => '2026-01', 'consented_at' => now()]);

        if (SystemSetting::valueOf('email_verification_required', false)) {
            $user->sendEmailVerificationNotification();

            return redirect()->route('verification.notice')->with('success', 'Vui lòng kiểm tra email để xác thực tài khoản.');
        }

        Auth::login($user);

        return redirect()->route('customer.dashboard')->with('success', 'Đăng ký tài khoản thành công! Chào mừng bạn đến với Mind Guide.');
    }

    /**
     * GET /quen-mat-khau (password.request) — SCR-PUB-08
     */
    public function forgotForm(): View
    {
        return view('public.forgot-password');
    }

    /**
     * POST /quen-mat-khau
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        PasswordBroker::sendResetLink($request->only('email'));

        return back()->with('status', 'Nếu email tồn tại trong hệ thống, chúng tôi đã gửi hướng dẫn đặt lại mật khẩu.');
    }

    /**
     * GET /dat-lai-mat-khau/{token} (password.reset) — SCR-PUB-09
     */
    public function resetForm(Request $request, string $token): View
    {
        $email = $request->query('email', '');

        return view('public.reset-password', compact('token', 'email'));
    }

    /**
     * POST /dat-lai-mat-khau
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = PasswordBroker::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password): void {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60), 'failed_login_count' => 0, 'locked_until' => null])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            event(new PasswordReset($user));
        });

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'Liên kết không hợp lệ hoặc đã hết hạn.']);
        }

        return redirect()->route('login')->with('success', 'Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập bằng mật khẩu mới.');
    }

    /**
     * GET /xac-thuc-email (verification.notice) — SCR-PUB-10
     */
    public function verifyNotice(): View
    {
        return view('public.verify-email');
    }

    public function verifyEmail(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::query()->findOrFail($id);
        abort_unless(hash_equals($hash, sha1($user->getEmailForVerification())), 403);
        $user->forceFill(['email_verified_at' => now()])->saveQuietly();

        return redirect()->route('login')->with('success', 'Email đã được xác thực thành công.');
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $user = User::query()->where('email', strtolower($data['email']))->first();
        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('success', 'Nếu tài khoản cần xác thực, email hướng dẫn đã được gửi.');
    }
}
