<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * MailHealthService — chẩn đoán cấu hình gửi email.
 *
 * Lễ tân thường gặp tình huống "khách không nhận được email kích hoạt".
 * Nguyên nhân phổ biến nhất không phải lỗi ứng dụng mà là cấu hình
 * SMTP chưa được thiết lập (thiếu username/password) hoặc tài khoản
 * gửi chưa được xác thực.
 *
 * Service này kiểm tra cấu hình trước khi gửi để trả về thông báo
 * dễ hiểu cho lễ tân thay vì lỗi kỹ thuật thô.
 */
class MailHealthService
{
    /**
     * Kiểm tra nhanh cấu hình mail — KHÔNG gửi email thật.
     *
     * `ok` = false nghĩa là hệ thống hiện tại KHÔNG gửi được email tới khách
     * thật (thiếu thông tin xác thực SMTP, hoặc đang dùng mailer chỉ ghi log).
     * `blocking` = true nghĩa là lỗi chắc chắn xảy ra khi gửi, cần chặn thao
     * tác và báo cho lễ tân biết nguyên nhân.
     *
     * @return array{ok:bool, blocking:bool, mailer:string, message:string, details:array<string, string>}
     */
    public function inspectConfiguration(): array
    {
        $mailer = (string) config('mail.default');
        $details = [];

        if ($mailer === 'log' || $mailer === 'array') {
            return [
                'ok' => false,
                'blocking' => false,
                'mailer' => $mailer,
                'message' => 'Hệ thống đang dùng mailer "'.$mailer.'" nên email không được gửi ra ngoài — nội dung chỉ nằm trong log. Cần cấu hình MAIL_MAILER=smtp cùng MAIL_USERNAME/MAIL_PASSWORD hợp lệ.',
                'details' => $details,
            ];
        }

        if ($mailer !== 'smtp') {
            return [
                'ok' => true,
                'blocking' => false,
                'mailer' => $mailer,
                'message' => 'Đang dùng mailer "'.$mailer.'".',
                'details' => $details,
            ];
        }

        $host = (string) config('mail.mailers.smtp.host');
        $port = (string) config('mail.mailers.smtp.port');
        $username = config('mail.mailers.smtp.username');
        $password = config('mail.mailers.smtp.password');
        $fromAddress = (string) config('mail.from.address');

        $details['Máy chủ SMTP'] = $host.':'.$port;
        $details['Tài khoản gửi'] = filled($username) ? (string) $username : '(chưa cấu hình)';
        $details['Địa chỉ gửi (From)'] = $fromAddress;

        if (blank($username) || blank($password)) {
            return [
                'ok' => false,
                'blocking' => true,
                'mailer' => $mailer,
                'message' => 'Thiếu MAIL_USERNAME hoặc MAIL_PASSWORD trong file .env. Máy chủ SMTP sẽ từ chối kết nối với lỗi 530 Authentication Required. Cần điền tài khoản email gửi (với Gmail dùng Mật khẩu ứng dụng) rồi chạy `php artisan config:clear`.',
                'details' => $details,
            ];
        }

        return [
            'ok' => true,
            'blocking' => false,
            'mailer' => $mailer,
            'message' => 'Cấu hình SMTP cơ bản hợp lệ. Nếu khách vẫn không nhận được email, hãy kiểm tra hộp thư spam hoặc thử gửi lại.',
            'details' => $details,
        ];
    }

    /**
     * Kiểm tra kết nối thật tới máy chủ SMTP bằng cách gửi thử một email.
     *
     * @return array{ok:bool, message:string}
     */
    public function verifyConnection(string $testRecipient): array
    {
        $configuration = $this->inspectConfiguration();

        if (! $configuration['ok']) {
            return ['ok' => false, 'message' => $configuration['message']];
        }

        try {
            Mail::raw('Kiem tra kết nối SMTP - '.now()->format('d/m/Y H:i'), function ($message) use ($testRecipient): void {
                $message->to($testRecipient)->subject('Kiem tra kết nối SMTP');
            });

            return ['ok' => true, 'message' => 'Gửi email thử thành công tới '.$testRecipient.'.'];
        } catch (Throwable $exception) {
            Log::warning('Kiểm tra kết nối SMTP thất bại.', [
                'exception' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => $this->describeException($exception),
            ];
        }
    }

    /**
     * Dịch lỗi kỹ thuật SMTP sang thông báo dễ hiểu cho lễ tân.
     */
    public function describeException(Throwable $exception): string
    {
        $message = $exception->getMessage();

        return match (true) {
            str_contains($message, '530'), str_contains($message, '535'), str_contains($message, 'Authentication Required'), str_contains($message, 'Authentication failed'), str_contains($message, 'BadCredentials') => 'SMTP chưa xác thực được (lỗi 530/535 Authentication Required). Tài khoản gửi chưa được cấu hình hoặc sai mật khẩu — cần dùng Mật khẩu ứng dụng (App Password) cho Gmail, không phải mật khẩu tài khoản.',
            str_contains($message, 'Could not authenticate'), str_contains($message, 'Failed to authenticate') => 'SMTP không xác thực được. Kiểm tra lại MAIL_USERNAME và MAIL_PASSWORD.',
            str_contains($message, 'Connection refused'), str_contains($message, 'failed to connect') => 'Không kết nối được tới máy chủ SMTP. Kiểm tra MAIL_HOST/MAIL_PORT hoặc tường lửa/hạ tầng mạng.',
            str_contains($message, '550'), str_contains($message, '553') => 'Máy chủ SMTP từ chối địa chỉ gửi. MAIL_FROM_ADDRESS phải là địa chỉ đã được xác thực trong tài khoản gửi.',
            str_contains($message, '554') => 'Máy chủ SMTP từ chối nội dung email — có thể do địa chỉ người nhận không hợp lệ.',
            default => 'Gửi email thất bại: '.$message,
        };
    }
}
