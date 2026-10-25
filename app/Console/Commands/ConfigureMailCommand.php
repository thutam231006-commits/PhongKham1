<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Thiết lập tài khoản email gửi đi (SMTP) trong file .env mà không lộ mật khẩu
 * vào lịch sử phiên làm việc.
 *
 * Với Gmail cần dùng "Mật khẩu ứng dụng" (App Password), không phải mật khẩu
 * tài khoản: Google bắt buộc xác thực 2 lớp cho ứng dụng gửi mail qua SMTP.
 */
class ConfigureMailCommand extends Command
{
    protected $signature = 'mail:configure
                            {--email= : Địa chỉ email gửi đi (ví dụ: ban@congty.vn)}
                            {--password= : Mật khẩu ứng dụng SMTP}
                            {--host=smtp.gmail.com : Máy chủ SMTP}
                            {--port=587 : Cổng SMTP}
                            {--name= : Tên hiển thị trong email (From name)}
                            {--force : Ghi đè cấu hình hiện tại}';

    protected $description = 'Thiết lập tài khoản email gửi đi trong .env (không lộ mật khẩu vào log)';

    public function handle(): int
    {
        $email = (string) ($this->option('email') ?: '');

        if ($email === '' && ! $this->option('force')) {
            $this->components->error('Cần truyền --email= địa chỉ gửi đi, ví dụ: php artisan mail:configure --email=ban@congty.vn');

            return self::FAILURE;
        }

        $envPath = base_path('.env');

        if (! is_file($envPath)) {
            $this->components->error('Không tìm thấy file .env tại '.$envPath);

            return self::FAILURE;
        }

        $contents = (string) file_get_contents($envPath);

        $values = [
            'MAIL_MAILER' => 'smtp',
            'MAIL_HOST' => (string) $this->option('host'),
            'MAIL_PORT' => (string) $this->option('port'),
        ];

        if ($email !== '') {
            $values['MAIL_USERNAME'] = $email;
            $values['MAIL_FROM_ADDRESS'] = $email;
        }

        if (filled($this->option('password'))) {
            $values['MAIL_PASSWORD'] = (string) $this->option('password');
        }

        if (filled($this->option('name'))) {
            $values['MAIL_FROM_NAME'] = '"'.(string) $this->option('name').'"';
        }

        foreach ($values as $key => $value) {
            $contents = $this->setEnvValue($contents, $key, $value);
        }

        file_put_contents($envPath, $contents);

        $this->components->info('Đã cập nhật cấu hình mail trong .env.');

        foreach ($values as $key => $value) {
            $this->components->twoColumnDetail($key, $key === 'MAIL_PASSWORD' ? '******' : $value);
        }

        $this->newLine();
        $this->components->info('Đang xoá cache cấu hình…');
        $this->call('config:clear');

        $this->newLine();
        $this->components->info('Kiểm tra kết nối: php artisan mail:diagnose --to=dia-chi-nhan@email.com');

        return self::SUCCESS;
    }

    /**
     * Gán (hoặc thay thế) một biến trong nội dung .env, giữ nguyên phần còn lại.
     */
    private function setEnvValue(string $contents, string $key, string $value): string
    {
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        if (preg_match($pattern, $contents) === 1) {
            return (string) preg_replace($pattern, $key.'='.$value, $contents, 1);
        }

        return rtrim($contents, "\r\n")."\n".$key.'='.$value."\n";
    }
}
