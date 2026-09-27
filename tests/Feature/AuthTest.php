<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_register_pages_are_displayed(): void
    {
        $this->get('/login')->assertOk()->assertViewIs('auth.login');
        $this->get('/register')->assertOk()->assertViewIs('auth.register');
    }

    public function test_register_redirects_to_admin(): void
    {
        $response = $this->post('/register', [
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
    }

    public function test_register_validation_messages(): void
    {
        $this->post('/register', [])->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
            'email' => 'メールアドレスを入力してください',
            'password' => 'パスワードを入力してください',
        ]);

        $this->post('/register', [
            'name' => '管理者',
            'email' => 'invalid',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors([
            'email' => 'メールアドレスはメール形式で入力してください',
            'password' => 'パスワードは8文字以上で入力してください',
        ]);

        $this->post('/register', [
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ])->assertSessionHasErrors(['password' => 'パスワードと一致しません']);

        $this->assertGuest();
    }

    public function test_login_redirects_to_admin(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com', 'password' => 'password']);

        $response = $this->post('/login', ['email' => 'test@example.com', 'password' => 'password']);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_validation_messages(): void
    {
        User::factory()->create(['email' => 'test@example.com', 'password' => 'password']);

        $this->post('/login', [])->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
            'password' => 'パスワードを入力してください',
        ]);

        $this->post('/login', ['email' => 'test@example.com', 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);

        $this->assertGuest();
    }

    public function test_logout_ends_session(): void
    {
        $response = $this->actingAs(User::factory()->create())->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
