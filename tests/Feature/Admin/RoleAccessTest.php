<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_access_orders(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)->get(route('admin.orders.index'))->assertOk();
    }

    public function test_staff_can_access_their_own_profile(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)->get(route('admin.profile.edit'))->assertOk();
    }

    public function test_staff_gets_403_on_users(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_staff_gets_403_on_settings(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)->get(route('admin.settings.edit'))->assertForbidden();
    }



    public function test_staff_gets_403_on_dashboard(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)->get(route('admin.insights'))->assertForbidden();
    }

    public function test_admin_can_access_every_section(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('admin.orders.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk();
        $this->actingAs($admin)->get(route('admin.listings.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.insights'))->assertOk();
        
    }

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' User',
            'email' => $role.'-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
