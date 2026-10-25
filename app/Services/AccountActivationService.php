<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AccountActivationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * AccountActivationService — sinh link kích hoạt và gửi email cho khách hàng.
 *
 * Trạng thái gửi email được ghi lại trên chính tài khoản khách
 * (`activation_email_status`) để lễ tân thấy rõ nguyên nhân khách chưa nhận
 * được email thay vì phải suy đoán.
 */
class AccountActivationService
{
    /** Email đã gửi thành công. */
    public const STATUS_SENT = 'SENT';

    /** Chưa gửi email kích hoạt. */
    public const STATUS_NOT_SENT = 'NOT_SENT';

    /** Gửi thất bại do lỗi hệ thống (SMTP, cấu hình…). */
    public const STATUS_FAILED = 'FAILED';

    /** Gửi thất bại do cấu hình mail chưa hoàn chỉnh — cần can thiệp của quản trị viên. */
    public const STATUS_MISCONFIGURED = 'MISCONFIGURED';

    public function __construct(
        private readonly MailHealthService $mailHealth,
        private readonly EmailVerificationService $verification,
    ) {}

    /**
     * Sinh token kích hoạt và gửi email cho khách hàng.
     *
     * Không ném exception khi gửi email thất bại: lễ tân vẫn cần tạo được hồ sơ
     * và ghi nhận lỗi để xử lý, thay vì mất toàn bộ thao tác.
     */
    public function createAndSend(User $user): void
    {
        $plainToken = $this->issueToken($user);

        $this->dispatch($user, $plainToken);
    }

    /**
     * Tạo token kích hoạt (mã hoá trong DB, bản rõ gửi qua email).
     */
    private function issueToken(User $user): string
    {
        $plainToken = Str::random(64);

        DB::table('account_activation_tokens')
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->delete();

        DB::table('account_activation_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addHours(24),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $plainToken;
    }

    /**
     * Gửi email kích hoạt và ghi nhận kết quả (thành công / thất bại + lý do).
     */
    private function dispatch(User $user, string $plainToken): void
    {
        $configuration = $this->mailHealth->inspectConfiguration();

        // Chỉ chặn khi chắc chắn gửi sẽ thất bại (thiếu thông tin xác thực SMTP).
        // Mailer "array"/"log" vẫn cho phép gửi để môi trường test/dev hoạt động.
        if ($configuration['blocking']) {
            Log::error('Cấu hình mail chưa hoàn chỉnh nên không gửi được email kích hoạt.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'reason' => $configuration['message'],
            ]);

            $user->forceFill([
                'activation_email_status' => self::STATUS_MISCONFIGURED,
                'activation_email_sent_at' => null,
                'activation_email_error' => $configuration['message'],
            ])->save();

            return;
        }

        // Mã 6 số đi kèm email: phương án thay thế khi khách không bấm được link.
        $code = $this->verification->issueCode($user, EmailVerificationService::PURPOSE_ACTIVATION);

        try {
            $user->notify(new AccountActivationNotification($plainToken, $code));

            $user->forceFill([
                'activation_email_status' => self::STATUS_SENT,
                'activation_email_sent_at' => now(),
                'activation_email_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            Log::error('Không gửi được email kích hoạt tài khoản.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'exception' => $exception->getMessage(),
            ]);

            $user->forceFill([
                'activation_email_status' => self::STATUS_FAILED,
                'activation_email_sent_at' => null,
                'activation_email_error' => $this->mailHealth->describeException($exception),
            ])->save();
        }
    }

    /**
     * Kích hoạt tài khoản bằng token thuần và đặt mật khẩu mới.
     */
    public function activate(string $plainToken, string $password): User
    {
        return DB::transaction(function () use ($plainToken, $password): User {
            $record = DB::table('account_activation_tokens')->where('token_hash', hash('sha256', $plainToken))->whereNull('used_at')->where('expires_at', '>', now())->lockForUpdate()->first();
            abort_unless($record, 422, 'Liên kết kích hoạt không hợp lệ hoặc đã hết hạn.');
            $user = User::query()->lockForUpdate()->findOrFail($record->user_id);
            $user->forceFill(['password' => $password, 'status' => 'ACTIVE', 'email_verified_at' => $user->email_verified_at ?? now(), 'email_verified_via' => 'LINK'])->save();
            DB::table('account_activation_tokens')->where('id', $record->id)->update(['used_at' => now(), 'updated_at' => now()]);

            return $user;
        });
    }

    /**
     * Kích hoạt tài khoản bằng mã 6 số và đặt mật khẩu mới.
     */
    public function activateWithCode(User $user, string $plainCode, string $password): User
    {
        return DB::transaction(function () use ($user, $plainCode, $password): User {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            abort_unless(
                $this->verification->verifyCode($locked, EmailVerificationService::PURPOSE_ACTIVATION, $plainCode),
                422,
                'Mã xác thực không đúng hoặc đã hết hạn. Vui lòng yêu cầu gửi lại mã.',
            );

            $locked->forceFill([
                'password' => $password,
                'status' => 'ACTIVE',
                'email_verified_at' => $locked->email_verified_at ?? now(),
                'email_verified_via' => 'CODE',
            ])->save();

            // Vô hiệu hoá các link kích hoạt còn lại để không dùng lại được.
            DB::table('account_activation_tokens')
                ->where('user_id', $locked->id)
                ->whereNull('used_at')
                ->update(['used_at' => now(), 'updated_at' => now()]);

            return $locked;
        });
    }

    /**
     * Lễ tân xác nhận email trực tiếp tại quầy (khi khách không nhận được email).
     */
    public function activateManually(User $user, string $password): User
    {
        return DB::transaction(function () use ($user, $password): User {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            $locked->forceFill([
                'password' => $password,
                'status' => 'ACTIVE',
                'email_verified_at' => $locked->email_verified_at ?? now(),
                'email_verified_via' => 'STAFF',
            ])->save();

            DB::table('account_activation_tokens')
                ->where('user_id', $locked->id)
                ->whereNull('used_at')
                ->update(['used_at' => now(), 'updated_at' => now()]);

            DB::table('email_verification_codes')
                ->where('user_id', $locked->id)
                ->whereNull('used_at')
                ->update(['used_at' => now(), 'updated_at' => now()]);

            return $locked;
        });
    }
}
