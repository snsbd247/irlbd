<?php

namespace Tests\Feature;

use App\Models\ContactSubmission;
use App\Models\Product;
use App\Models\Role;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    public function test_support_role_cannot_manage_products(): void
    {
        $this->actingAs($this->support())->spa()
            ->postJson('/api/v1/admin/products', ['name' => 'Bottle', 'status' => 'draft'])
            ->assertForbidden();

        $this->actingAs($this->support())->spa()->getJson('/api/v1/admin/products')->assertForbidden();
    }

    public function test_support_role_can_manage_enquiries(): void
    {
        ContactSubmission::create(['name' => 'A', 'email' => 'a@example.com', 'subject' => 'Other', 'message' => 'Hello there!']);

        $this->actingAs($this->support())->spa()->getJson('/api/v1/admin/enquiries')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_editor_can_manage_products_but_not_settings_or_users(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)->spa()->postJson('/api/v1/admin/products', ['name' => 'Bottle', 'status' => 'draft'])->assertCreated();
        $this->actingAs($editor)->spa()->getJson('/api/v1/admin/settings')->assertForbidden();
        $this->actingAs($editor)->spa()->putJson('/api/v1/admin/settings', ['site_name' => 'Hacked'])->assertForbidden();
        $this->actingAs($editor)->spa()->getJson('/api/v1/admin/users')->assertForbidden();
        $this->actingAs($editor)->spa()->getJson('/api/v1/admin/enquiries')->assertForbidden();
    }

    public function test_user_without_role_is_rejected(): void
    {
        $user = $this->makeUser('super-admin');
        $user->forceFill(['role_id' => null])->save();

        $this->actingAs($user)->spa()->getJson('/api/v1/admin/me')->assertForbidden();
    }

    public function test_user_manager_cannot_escalate_to_super_admin(): void
    {
        $managerRole = Role::create(['name' => 'User Manager', 'slug' => 'user-manager']);
        $managerRole->permissions()->sync(\App\Models\Permission::where('slug', 'users.manage')->pluck('id'));
        $manager = $this->makeUser('user-manager');

        $this->actingAs($manager)->spa()->postJson('/api/v1/admin/users', [
            'name' => 'Sneaky', 'email' => 'sneaky@example.com', 'password' => 'Password12345',
            'role_id' => Role::where('slug', 'super-admin')->value('id'), 'is_active' => true,
        ])->assertStatus(422)->assertJsonValidationErrors('role_id');

        // ...and cannot modify an existing super admin.
        $super = $this->admin();
        $this->actingAs($manager)->spa()->putJson('/api/v1/admin/users/'.$super->id, [
            'name' => 'Changed', 'email' => $super->email, 'role_id' => $super->role_id, 'is_active' => false,
        ])->assertForbidden();
    }

    public function test_admin_cannot_delete_or_deactivate_self(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->spa()->deleteJson('/api/v1/admin/users/'.$admin->id)->assertForbidden();
        $this->actingAs($admin)->spa()->putJson('/api/v1/admin/users/'.$admin->id, [
            'name' => $admin->name, 'email' => $admin->email, 'role_id' => $admin->role_id, 'is_active' => false,
        ])->assertStatus(422);
    }

    public function test_super_admin_can_create_roles_with_permissions(): void
    {
        $response = $this->actingAs($this->admin())->spa()->postJson('/api/v1/admin/roles', [
            'name' => 'News Writer', 'permissions' => ['news.manage', 'media.manage'],
        ])->assertCreated();

        $this->assertEqualsCanonicalizing(['news.manage', 'media.manage'], $response->json('permissions'));
    }

    public function test_draft_product_is_visible_in_admin_only(): void
    {
        Product::create(['name' => 'Secret', 'status' => 'draft']);

        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($this->editor())->spa()->getJson('/api/v1/admin/products')->assertOk()->assertJsonCount(1, 'data');
    }
}
