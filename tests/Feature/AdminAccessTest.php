<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_admin(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/admin');

        $response->assertOk();
        $response->assertViewIs('admin.index');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }
}
