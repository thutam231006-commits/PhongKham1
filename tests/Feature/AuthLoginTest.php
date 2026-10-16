<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('roleRedirectProvider')]
    public function test_demo_accounts_can_login_and_redirect_by_role(string $role, string $route): void
    {
        $user = User::factory()->create([
            'email' => $role.'@example.test',
            'role' => $role,
            'password' => 'password',
            'status' => 'ACTIVE',
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route($route));
        $this->assertAuthenticatedAs($user);
    }

    /** @return array<string, array{0:string, 1:string}> */
    public static function roleRedirectProvider(): array
    {
        return [
            'customer' => ['customer', 'customer.dashboard'],
            'specialist' => ['specialist', 'specialist.dashboard'],
            'staff' => ['staff', 'staff.dashboard'],
            'admin' => ['admin', 'admin.dashboard'],
        ];
    }

    public function test_invalid_password_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'login@example.test', 'password' => 'password']);

        $this->post(route('login.post'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email')
            ->assertRedirect();
    }
}
