<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_open_dashboard(): void
    {
        $user = User::create([
            'name' => 'Amina Wanjiku',
            'email' => 'amina@example.com',
            'role' => 'tenant',
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('KejaPad');
    }
}