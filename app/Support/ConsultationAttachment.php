<?php

namespace App\Support;

/**
 * ConsultationAttachment — mô tả một tệp đính kèm của hồ sơ tư vấn.
 *
 * Tệp được lưu trên disk `local` (storage/app/private) để không công khai ra
 * ngoài; khách hàng và chuyên viên phụ trách tải qua route có kiểm tra quyền.
 *
 * @see \App\Models\ConsultationRecord::attachments
 */
final class ConsultationAttachment
{
    /**
     * @param  string  $name  Tên hiển thị cho người dùng.
     * @param  string  $path  Đường dẫn tương đối trên disk `local`.
     * @param  int  $size  Kích thước tệp (byte).
     * @param  string  $mime  MIME type của tệp.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly int $size,
        public readonly string $mime,
    ) {}

    /**
     * Dựng lại tệp đính kèm từ dữ liệu JSON đã lưu.
     *
     * @param  array<string, mixed>|null  $data
     */
    public static function fromArray(?array $data): ?self
    {
        if ($data === null) {
            return null;
        }

        $name = $data['name'] ?? null;
        $path = $data['path'] ?? null;

        if (! is_string($name) || ! is_string($path) || $name === '' || $path === '') {
            return null;
        }

        return new self(
            name: $name,
            path: $path,
            size: is_numeric($data['size'] ?? null) ? (int) $data['size'] : 0,
            mime: is_string($data['mime'] ?? null) ? $data['mime'] : 'application/octet-stream',
        );
    }

    /**
     * @return array{name: string, path: string, size: int, mime: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'path' => $this->path,
            'size' => $this->size,
            'mime' => $this->mime,
        ];
    }

    /**
     * Kích thước hiển thị cho người dùng (KB/MB).
     */
    public function humanSize(): string
    {
        if ($this->size >= 1_048_576) {
            return round($this->size / 1_048_576, 1).' MB';
        }

        if ($this->size >= 1024) {
            return round($this->size / 1024).' KB';
        }

        return $this->size.' byte';
    }
}
