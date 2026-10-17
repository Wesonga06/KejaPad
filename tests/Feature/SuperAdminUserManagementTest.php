<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    public function test_index_lists_and_filters_users_by_role(): void
    {
        $admin = $this->admin();
        User::factory()->landlord()->create(['name' => 'Landlord One']);
        User::factory()->tenant()->create(['name' => 'Tenant Two']);

        $this->actingAs($admin)->get(route('super_admin.users.index'))
            ->assertOk()
            ->assertSee('Landlord One')
            ->assertSee('Tenant Two');

        $this->actingAs($admin)->get(route('super_admin.users.index', ['role' => 'landlord']))
            ->assertOk()
            ->assertSee('Landlord One')
            ->assertDontSee('Tenant Two');
    }

    public function test_index_search_matches_phone_number(): void
    {
        $admin = $this->admin();
        User::factory()->tenant()->create(['name' => 'Alice', 'phone' => '0700111222']);
        User::factory()->tenant()->create(['name' => 'Bob', 'phone' => '0799999999']);

        $this->actingAs($admin)->get(route('super_admin.users.index', ['q' => '0700111']))
            ->assertOk()
            ->assertSee('Alice')
            ->assertDontSee('Bob');
    }

    public function test_show_renders_user_detail(): void
    {
        $admin = $this->admin();
        $user = User::factory()->tenant()->create(['email' => 'detail@example.com']);

        $this->actingAs($admin)->get(route('super_admin.users.show', $user))
            ->assertOk()
            ->assertSee('detail@example.com');
    }

    public function test_edit_view_renders(): void
    {
        $admin = $this->admin();
        $user = User::factory()->tenant()->create();

        $this->actingAs($admin)->get(route('super_admin.users.edit', $user))
            ->assertOk()
            ->assertSee('Edit User');
    }

    public function test_user_can_be_created_with_phone_and_is_audited(): void
    {
        $this->actingAs($this->admin())->post(route('super_admin.users.store'), [
            'name' => 'New Person',
            'email' => 'new@example.com',
            'phone' => '0712345678',
            'role' => 'caretaker',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('super_admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'phone' => '0712345678']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created']);
    }

    public function test_admin_cannot_change_own_role_or_deactivate_self(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('super_admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'landlord',
        ])->assertSessionHasErrors('role');

        $this->actingAs($admin)->put(route('super_admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'super_admin',
            'is_active' => '0',
        ])->assertSessionHasErrors('is_active');

        $this->assertSame('super_admin', $admin->fresh()->role);
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_last_active_super_admin_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $victim = User::factory()->superAdmin()->create(['is_active' => false]);

        $this->actingAs($admin)->delete(route('super_admin.users.destroy', $victim))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $victim->id]);
    }

    public function test_landlord_cannot_access_user_management(): void
    {
        $landlord = User::factory()->landlord()->create();

        $this->actingAs($landlord)->get(route('super_admin.users.index'))->assertForbidden();
        $this->actingAs($landlord)->get(route('super_admin.users.create'))->assertForbidden();
    }
}
