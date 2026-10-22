<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AccountActivationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountActivationService
{
    public function createAndSend(User $user): void
    {
        $plainToken = Str::random(64);
        DB::table('account_activation_tokens')->where('user_id', $user->id)->whereNull('used_at')->delete();
        DB::table('account_activation_tokens')->insert(['user_id' => $user->id, 'token_hash' => hash('sha256', $plainToken), 'expires_at' => now()->addHours(24), 'created_at' => now(), 'updated_at' => now()]);
        $user->notify(new AccountActivationNotification($plainToken));
    }

    public function activate(string $plainToken, string $password): User
    {
        return DB::transaction(function () use ($plainToken, $password): User {
            $record = DB::table('account_activation_tokens')->where('token_hash', hash('sha256', $plainToken))->whereNull('used_at')->where('expires_at', '>', now())->lockForUpdate()->first();
            abort_unless($record, 422, 'Liên kết kích hoạt không hợp lệ hoặc đã hết hạn.');
            $user = User::query()->lockForUpdate()->findOrFail($record->user_id);
            $user->forceFill(['password' => $password, 'status' => 'ACTIVE', 'email_verified_at' => $user->email_verified_at ?? now()])->save();
            DB::table('account_activation_tokens')->where('id', $record->id)->update(['used_at' => now(), 'updated_at' => now()]);

            return $user;
        });
    }
}
