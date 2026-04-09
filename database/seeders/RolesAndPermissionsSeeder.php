<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Очистка кэша прав
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Создание разрешений
        $permissions = [
            'view events',
            'create events',
            'edit events',
            'delete events',
            'view posts',
            'create posts',
            'edit posts',
            'delete posts',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Создание ролей и назначение разрешений
        $role = Role::create(['name' => 'user']);
        $role->givePermissionTo(['view events', 'view posts']);

        $role = Role::create(['name' => 'editor']);
        $role->givePermissionTo([
            'view events', 'create events', 'edit events',
            'view posts', 'create posts', 'edit posts'
        ]);

        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo(Permission::all()); // Все права
        
        $role = Role::create(['name' => 'super_admin']); // Особая роль
    }
}