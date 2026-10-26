<?php

namespace App\Support;

use Dotenv\Dotenv;
use Dotenv\Exception\ExceptionInterface as DotenvException;
use RuntimeException;

/**
 * EnvFileWriter — ghi giá trị vào file .env mà không làm hỏng cú pháp dotenv.
 *
 * "Mật khẩu ứng dụng" của Gmail có khoảng trắng (ví dụ `oavd yovd ngnr sksj`).
 * Ghi thẳng giá trị đó vào .env khiến phpdotenv báo
 * "Encountered unexpected whitespace" và toàn bộ ứng dụng không khởi động
 * được. Vì vậy giá trị phải được đặt trong dấu nháy, và file kết quả phải
 * được kiểm tra lại cú pháp trước khi ghi đè lên .env cũ.
 */
final class EnvFileWriter
{
    /**
     * Giá trị có thể ghi không dấu nháy mà không bị dotenv diễn giải sai.
     *
     * Bỏ qua `$` (dotenv sẽ nội suy biến) và `#` (bắt đầu chú thích).
     */
    private const UNQUOTED_PATTERN = '/^[A-Za-z0-9_.\-@+:\/]+$/';

    /**
     * Ghi các cặp key => value vào file .env, giữ nguyên phần còn lại.
     *
     * @param  array<string, string>  $values
     *
     * @throws RuntimeException khi nội dung kết quả không parse được.
     */
    public static function set(string $path, array $values): void
    {
        $contents = is_file($path) ? (string) file_get_contents($path) : '';

        foreach ($values as $key => $value) {
            $contents = self::apply($contents, $key, $value);
        }

        self::assertParsable($contents);

        file_put_contents($path, $contents);
    }

    /**
     * Thay (hoặc thêm) một biến trong nội dung .env.
     */
    public static function apply(string $contents, string $key, string $value): string
    {
        $line = $key.'='.self::format($value);
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        if (preg_match($pattern, $contents) === 1) {
            return (string) preg_replace_callback($pattern, static fn (): string => $line, $contents, 1);
        }

        return rtrim($contents, "\r\n")."\n".$line."\n";
    }

    /**
     * Định dạng giá trị để dotenv đọc lại đúng nguyên bản.
     */
    private static function format(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match(self::UNQUOTED_PATTERN, $value) === 1) {
            return $value;
        }

        // Dấu nháy đơn giữ giá trị nguyên văn: không nội suy `$`, không hiểu `\`.
        if (! str_contains($value, "'")) {
            return "'".$value."'";
        }

        return '"'.strtr($value, ['\\' => '\\\\', '"' => '\\"', '$' => '\\$']).'"';
    }

    /**
     * Bảo đảm nội dung .env vẫn parse được trước khi ghi đè.
     */
    private static function assertParsable(string $contents): void
    {
        try {
            Dotenv::parse($contents);
        } catch (DotenvException $exception) {
            throw new RuntimeException('Nội dung .env không hợp lệ: '.$exception->getMessage(), 0, $exception);
        }
    }
}
