<?php

namespace Tests\Unit;

use App\Support\EnvFileWriter;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

/**
 * EnvFileWriter — ghi giá trị vào .env mà không làm hỏng cú pháp dotenv.
 *
 * "Mật khẩu ứng dụng" của Gmail có khoảng trắng; ghi thẳng vào .env khiến
 * phpdotenv báo lỗi "unexpected whitespace" và ứng dụng không khởi động được.
 */
class EnvFileWriterTest extends TestCase
{
    public function test_app_password_with_spaces_is_written_as_a_quoted_value(): void
    {
        $path = $this->tempEnvPath();

        EnvFileWriter::set($path, ['MAIL_PASSWORD' => 'oavd yovd ngnr sksj']);

        $contents = (string) file_get_contents($path);

        $this->assertStringContainsString("MAIL_PASSWORD='oavd yovd ngnr sksj'", $contents);
    }

    public function test_written_app_password_is_read_back_unchanged(): void
    {
        $path = $this->tempEnvPath();

        EnvFileWriter::set($path, ['MAIL_PASSWORD' => 'oavd yovd ngnr sksj']);

        $parsed = Dotenv::parse((string) file_get_contents($path));

        $this->assertSame('oavd yovd ngnr sksj', $parsed['MAIL_PASSWORD']);
    }

    public function test_existing_value_is_replaced_without_touching_other_variables(): void
    {
        $path = $this->tempEnvPath("MAIL_USERNAME=cu@gmail.com\nMAIL_PASSWORD=cu\nAPP_NAME=Laravel\n");

        EnvFileWriter::set($path, ['MAIL_PASSWORD' => 'oavd yovd ngnr sksj']);

        $contents = (string) file_get_contents($path);

        $this->assertStringContainsString('MAIL_USERNAME=cu@gmail.com', $contents);
        $this->assertStringContainsString('APP_NAME=Laravel', $contents);
        $this->assertStringNotContainsString('MAIL_PASSWORD=cu', $contents);
    }

    public function test_plain_value_is_written_without_quotes(): void
    {
        $path = $this->tempEnvPath();

        EnvFileWriter::set($path, ['MAIL_USERNAME' => 'khachhang@example.com']);

        $contents = (string) file_get_contents($path);

        $this->assertStringContainsString('MAIL_USERNAME=khachhang@example.com', $contents);
    }

    public function test_value_containing_a_hash_is_quoted_so_it_is_not_treated_as_a_comment(): void
    {
        $path = $this->tempEnvPath();

        EnvFileWriter::set($path, ['MAIL_PASSWORD' => 'abc#def']);

        $parsed = Dotenv::parse((string) file_get_contents($path));

        $this->assertSame('abc#def', $parsed['MAIL_PASSWORD']);
    }

    public function test_value_containing_a_dollar_sign_is_quoted_so_it_is_not_expanded(): void
    {
        $path = $this->tempEnvPath();

        EnvFileWriter::set($path, ['MAIL_PASSWORD' => 'pa$$word']);

        $parsed = Dotenv::parse((string) file_get_contents($path));

        $this->assertSame('pa$$word', $parsed['MAIL_PASSWORD']);
    }

    public function test_missing_file_is_created_with_the_written_variable(): void
    {
        $path = $this->tempEnvPath();
        unlink($path);

        EnvFileWriter::set($path, ['MAIL_MAILER' => 'smtp']);

        $this->assertStringContainsString('MAIL_MAILER=smtp', (string) file_get_contents($path));
    }

    /**
     * @return string Đường dẫn file .env tạm, tự xoá sau khi test.
     */
    private function tempEnvPath(string $contents = ''): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'envtest');
        file_put_contents($path, $contents);

        register_shutdown_function(static function () use ($path): void {
            @unlink($path);
        });

        return $path;
    }
}
