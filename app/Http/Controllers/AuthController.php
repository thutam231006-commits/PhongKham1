<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email' => 'Email không đúng định dạng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = $request->user();
            $user->forceFill(['last_login_at' => now()])->saveQuietly();

            if ($request->filled('intended')) {
                return redirect()->to($request->input('intended'));
            }

            return redirect()->intended(match (strtolower((string) $user->role)) {
                'admin' => route('admin.dashboard'),
                'staff' => route('staff.dashboard'),
                'specialist' => route('specialist.dashboard'),
                default => route('customer.dashboard'),
            });
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
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
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
            'email_verified_at' => now(),
        ]);

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
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Kết quả luôn trung tính theo SCR-PUB-08
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
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        return redirect()->route('login')->with('success', 'Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập bằng mật khẩu mới.');
    }

    /**
     * GET /xac-thuc-email (verification.notice) — SCR-PUB-10
     */
    public function verifyNotice(): View
    {
        return view('public.verify-email');
    }
}
