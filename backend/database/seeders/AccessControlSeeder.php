<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('cms.permission_modules') as $module => $label) {
            Permission::updateOrCreate(['slug' => $module.'.manage'], ['label' => 'Manage '.lcfirst($label), 'group' => $module]);
        }

        Role::updateOrCreate(['slug' => 'super-admin'], [
            'name' => 'Super Admin',
            'description' => 'Full access to everything, including users and roles.',
            'is_super' => true,
        ]);

        $editor = Role::updateOrCreate(['slug' => 'content-editor'], [
            'name' => 'Content Editor',
            'description' => 'Manages website content, products and news.',
            'is_super' => false,
        ]);
        $editor->permissions()->sync(Permission::whereIn('group', [
            'pages', 'menus', 'products', 'services', 'clients', 'gallery', 'team', 'news', 'history', 'quality', 'contact', 'media',
        ])->pluck('id'));

        $support = Role::updateOrCreate(['slug' => 'support'], [
            'name' => 'Customer Support',
            'description' => 'Handles contact enquiries and newsletter subscribers.',
            'is_super' => false,
        ]);
        $support->permissions()->sync(Permission::whereIn('group', ['enquiries', 'subscribers'])->pluck('id'));
    }
}
