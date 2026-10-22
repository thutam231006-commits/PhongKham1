<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function notifications(): View
    {
        $notifications = collect();

        return view('public.notifications', compact('notifications'));
    }

    public function changePassword(): View
    {
        return view('public.change-password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Mật khẩu đã được cập nhật.');
    }
}
