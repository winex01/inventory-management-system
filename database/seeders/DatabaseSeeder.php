<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Artisan::call('shield:generate', [
            '--all'   => true,
            '--panel' => 'app',
            '--quiet' => true,
        ]);

        $superAdminRole  = config('filament-shield.super_admin.name', 'super_admin');
        $roleModel       = config('permission.models.role');
        $permissionModel = config('permission.models.permission');

        // Create super admin user
        $winnie = User::factory()->create([
            'name'  => 'winnie',
            'email' => 'winnie131212592@gmail.com',
        ]);
        $winnie->assignRole($superAdminRole);

        // Create admin role with all permissions
        $adminRole = $roleModel::create(['name' => 'admin']);
        $adminRole->givePermissionTo($permissionModel::all());

        // Create admin user and assign admin role
        $admin = User::factory()->create([
            'name'  => 'admin',
            'email' => 'admin@admin.com',
        ]);
        $admin->assignRole($adminRole);

        User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@test.com',
        ]);
    }
}
