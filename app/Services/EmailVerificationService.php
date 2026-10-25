<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * EmailVerificationService — xác thực email bằng mã 6 số (OTP).
 *
 * Ngoài phương án bấm link trong email, khách có thể nhập mã 6 số gửi kèm
 * email. Mã được lưu dạng băm SHA-256 nên không đọc lại được từ database,
 * tự hết hạn sau vài phút và chỉ dùng được một lần.
 */
class EmailVerificationService
{
    /** Mã xác thực dùng để kích hoạt tài khoản tạo bởi lễ tân. */
    public const PURPOSE_ACTIVATION = 'ACTIVATION';

    /** Mã xác thực email cho khách đăng ký tự do. */
    public const PURPOSE_VERIFICATION = 'VERIFICATION';

    /** Số lần nhập sai tối đa cho một mã. */
    public const MAX_ATTEMPTS = 5;

    /**
     * Sinh mã 6 số, lưu băm và trả về mã rõ để gửi qua email.
     */
    public function issueCode(User $user, string $purpose): string
    {
        $plainCode = (string) random_int(100000, 999999);

        $this->clearExisting($user, $purpose);

        DB::table('email_verification_codes')->insert([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'code_hash' => hash('sha256', $plainCode),
            'attempts' => 0,
            'expires_at' => now()->addMinutes((int) config('auth.verification.otp_expire', 15)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $plainCode;
    }

    /**
     * Kiểm tra mã người dùng nhập.
     *
     * @return bool true khi mã hợp lệ (đã đánh dấu sử dụng)
     */
    public function verifyCode(User $user, string $purpose, string $plainCode): bool
    {
        $plainCode = trim($plainCode);

        if ($plainCode === '') {
            return false;
        }

        $record = DB::table('email_verification_codes')
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if (! $record) {
            return false;
        }

        if ((int) $record->attempts >= self::MAX_ATTEMPTS) {
            $this->clearExisting($user, $purpose);

            return false;
        }

        if (! hash_equals((string) $record->code_hash, hash('sha256', $plainCode))) {
            DB::table('email_verification_codes')->where('id', $record->id)->update([
                'attempts' => (int) $record->attempts + 1,
                'updated_at' => now(),
            ]);

            return false;
        }

        DB::table('email_verification_codes')->where('id', $record->id)->update([
            'used_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    /**
     * Còn mã nào đang hiệu lực cho tài khoản này không.
     */
    public function hasActiveCode(User $user, string $purpose): bool
    {
        return DB::table('email_verification_codes')
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Xoá các mã cũ chưa dùng trước khi phát mã mới.
     */
    private function clearExisting(User $user, string $purpose): void
    {
        DB::table('email_verification_codes')
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->delete();
    }

    /**
     * Phát mã mới và gửi email kèm mã.
     *
     * @return array{ok:bool, message:string}
     */
    public function resend(User $user, string $purpose): array
    {
        $code = $this->issueCode($user, $purpose);

        try {
            $user->notify(new \App\Notifications\EmailVerificationCodeNotification($code, $purpose));
        } catch (\Throwable $exception) {
            Log::error('Không gửi được mã xác thực email.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'purpose' => $purpose,
                'exception' => $exception->getMessage(),
            ]);

            return ['ok' => false, 'message' => 'Không gửi được mã xác thực: '.$exception->getMessage()];
        }

        return ['ok' => true, 'message' => 'Mã xác thực mới đã được gửi tới '.$user->email.'.'];
    }
}
