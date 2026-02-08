<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_returns_200(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->post(route('login.post'), [
            'username' => 'nonexistent',
            'password' => 'wrong',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_login_succeeds_with_valid_staff(): void
    {
        $user = User::factory()->create([
            'username' => 'admin',
            'password' => 'password',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
        $response = $this->post(route('login.post'), [
            'username' => 'admin',
            'password' => 'password',
        ]);
        $response->assertRedirect(route('dashboard'));
        $this->assertNotNull(session('admin_user_id'));
    }
}
