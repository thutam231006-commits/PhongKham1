<?php

namespace Tests\Feature;

use Dotenv\Dotenv;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * mail:configure — thiết lập tài khoản gửi email trong file .env.
 *
 * Lệnh này từng ghi thẳng giá trị vào .env nên "Mật khẩu ứng dụng" của Gmail
 * (có khoảng trắng) làm hỏng file .env và ứng dụng không khởi động được.
 */
class ConfigureMailCommandTest extends TestCase
{
    private string $envDirectory;

    private string $envPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->envDirectory = sys_get_temp_dir().'/mail-configure-'.uniqid();
        mkdir($this->envDirectory);

        $this->envPath = $this->envDirectory.'/.env';
        file_put_contents($this->envPath, "APP_NAME=Laravel\nMAIL_USERNAME=cu@gmail.com\nMAIL_PASSWORD=cu\n");

        $this->app->useEnvironmentPath($this->envDirectory);
    }

    protected function tearDown(): void
    {
        @unlink($this->envPath);
        @rmdir($this->envDirectory);

        parent::tearDown();
    }

    public function test_app_password_with_spaces_keeps_the_env_file_loadable(): void
    {
        $this->artisan('mail:configure', [
            '--email' => 'khachhang@example.com',
            '--password' => 'oavd yovd ngnr sksj',
            '--name' => 'Mind Guide',
        ])->assertSuccessful();

        $parsed = Dotenv::parse((string) file_get_contents($this->envPath));

        $this->assertSame('oavd yovd ngnr sksj', $parsed['MAIL_PASSWORD']);
        $this->assertSame('khachhang@example.com', $parsed['MAIL_USERNAME']);
    }

    public function test_command_reports_the_app_password_without_exposing_it(): void
    {
        $exitCode = Artisan::call('mail:configure', [
            '--email' => 'khachhang@example.com',
            '--password' => 'oavd yovd ngnr sksj',
            '--name' => 'Mind Guide',
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringNotContainsString('oavd yovd ngnr sksj', $output);
        $this->assertStringContainsString('******', $output);
    }

    public function test_command_writes_the_smtp_host_and_port(): void
    {
        $this->artisan('mail:configure', [
            '--email' => 'khachhang@example.com',
            '--password' => 'oavd yovd ngnr sksj',
        ])->assertSuccessful();

        $parsed = Dotenv::parse((string) file_get_contents($this->envPath));

        $this->assertSame('smtp', $parsed['MAIL_MAILER']);
        $this->assertSame('smtp.gmail.com', $parsed['MAIL_HOST']);
        $this->assertSame('587', $parsed['MAIL_PORT']);
    }

    public function test_command_keeps_the_other_variables_untouched(): void
    {
        $this->artisan('mail:configure', [
            '--email' => 'khachhang@example.com',
            '--password' => 'oavd yovd ngnr sksj',
        ])->assertSuccessful();

        $parsed = Dotenv::parse((string) file_get_contents($this->envPath));

        $this->assertSame('Laravel', $parsed['APP_NAME']);
    }
}
