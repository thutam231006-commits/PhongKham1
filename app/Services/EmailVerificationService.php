<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * EmailVerificationService — xác thực email bằng mã 6 số (OTP).
 *
 * Ngoài phương án bấm link trong email, khách có thể nhập mã 6 số gửi kèm
 * email. Mã được lưu dạng băm SHA-256 nên không đọc lại được từ database,
 * tự hết hạn sau vài phút và chỉ dùng được một lần.
 */
class EmailVerificationService
{
    public function __construct(
        private readonly MailHealthService $mailHealth,
    ) {}

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
            $user->notify(new EmailVerificationCodeNotification($code, $purpose));
        } catch (\Throwable $exception) {
            Log::error('Không gửi được mã xác thực email.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'purpose' => $purpose,
                'exception' => $exception->getMessage(),
            ]);

            return ['ok' => false, 'message' => 'Không gửi được mã xác thực: '.$this->mailHealth->describeException($exception)];
        }

        return ['ok' => true, 'message' => 'Mã xác thực mới đã được gửi tới '.$user->email.'.'];
    }

    /**
     * Gửi một email xác thực duy nhất chứa cả link kích hoạt và mã 6 số.
     *
     * @return array{ok:bool, message:string}
     */
    public function sendVerificationMail(User $user): array
    {
        $code = $this->issueCode($user, self::PURPOSE_VERIFICATION);

        try {
            $user->notify(new EmailVerificationNotification($code, $this->verificationUrl($user)));
        } catch (\Throwable $exception) {
            Log::error('Không gửi được email xác thực.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'exception' => $exception->getMessage(),
            ]);

            return ['ok' => false, 'message' => 'Không gửi được email xác thực: '.$this->mailHealth->describeException($exception)];
        }

        return ['ok' => true, 'message' => 'Email xác thực đã được gửi tới '.$user->email.'.'];
    }

    /**
     * Link kích hoạt có chữ ký và hạn dùng.
     */
    private function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes((int) config('auth.verification.expire', 60)),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())],
        );
    }
}
