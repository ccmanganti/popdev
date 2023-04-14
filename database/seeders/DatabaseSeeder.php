<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        $user = \App\Models\User::factory()->create([
            'name' => 'Popcom Popdev',
            'email' => 'popcom@gov.com.ph',
        ]);

        $user->assignRole(Role::create(['name' => 'Superadmin']));

        Role::create(['name' => 'LGU']);
        Role::create(['name' => 'Barangay']);
        Role::create(['name' => 'Enumerator']);
        
    }
}
