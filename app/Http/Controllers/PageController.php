<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PageController — Quản lý các trang thông tin công khai
 *
 * Bao gồm:
 *  - Giới thiệu (about.blade.php)
 *  - Liên hệ (contact.blade.php)
 *  - Câu hỏi thường gặp (faq.blade.php)
 *  - Chính sách quyền riêng tư (privacy.blade.php)
 *  - Điều khoản sử dụng (terms.blade.php)
 */
class PageController extends Controller
{
    /**
     * GET /gioi-thieu (about)
     */
    public function about(): View
    {
        return view('public.about');
    }

    /**
     * GET /lien-he (contact)
     */
    public function contact(): View
    {
        return view('public.contact');
    }

    /**
     * GET /hoi-dap (faq)
     */
    public function faq(): View
    {
        return view('public.faq');
    }

    /**
     * GET /chinh-sach-quyen-rieng-tu (privacy)
     */
    public function privacy(): View
    {
        return view('public.privacy');
    }

    /**
     * GET /dieu-khoan-su-dung (terms)
     */
    public function terms(): View
    {
        return view('public.terms');
    }

    /**
     * POST /lien-he (gửi form liên hệ)
     */
    public function sendContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'phone' => 'nullable|string|regex:/^[0-9+\s\-]{9,15}$/',
            'email' => 'required|email|max:255',
            'message' => 'required|string|min:10|max:1000',
        ], [
            'name.required' => 'Vui lòng nhập họ và tên của bạn.',
            'email.required' => 'Vui lòng cung cấp địa chỉ email liên hệ.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
            'message.required' => 'Vui lòng nhập nội dung câu hỏi hoặc yêu cầu tư vấn.',
            'message.min' => 'Nội dung tin nhắn tối thiểu 10 ký tự.',
        ]);

        return back()->with('success', 'Cảm ơn bạn đã gửi tin nhắn. Đội ngũ điều phối viên Mind Guide sẽ phản hồi trong vòng 24 giờ làm việc.');
    }
}
