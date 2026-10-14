<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin Watoon',
            'email' => 'admin@watoon.com',
            'role' => Role::Admin,
        ]);

        User::factory()->create([
            'name' => 'Kreator Watoon',
            'email' => 'kreator@watoon.com',
            'role' => Role::Kreator,
        ]);

        User::factory()->create([
            'name' => 'User Watoon',
            'email' => 'user@watoon.com',
            'role' => Role::User,
        ]);
    }
}
