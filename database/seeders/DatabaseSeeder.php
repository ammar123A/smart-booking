<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(LoyaltySeeder::class);
        $this->call(CatalogSeeder::class);

        // --- Admin User ---
        $admin = User::firstOrCreate(
            ['email' => 'admin@smartbooking.com'],
            [
                'name'     => 'Admin User',
                'password' => Hash::make('password'),
                'locale'   => 'en',
                'loyalty_points' => 0,
            ]
        );
        $admin->assignRole('admin');

        // --- Customer Users ---
        $customers = [
            [
                'name'           => 'Alice Tan',
                'email'          => 'alice@example.com',
                'password'       => Hash::make('password'),
                'locale'         => 'en',
                'loyalty_points' => 1800,
            ],
            [
                'name'           => 'Bob Lim',
                'email'          => 'bob@example.com',
                'password'       => Hash::make('password'),
                'locale'         => 'en',
                'loyalty_points' => 600,
            ],
            [
                'name'           => 'Charlie Wong',
                'email'          => 'charlie@example.com',
                'password'       => Hash::make('password'),
                'locale'         => 'ms',
                'loyalty_points' => 120,
            ],
            [
                'name'           => 'Diana Ng',
                'email'          => 'diana@example.com',
                'password'       => Hash::make('password'),
                'locale'         => 'en',
                'loyalty_points' => 3200,
            ],
        ];

        foreach ($customers as $data) {
            $user = User::firstOrCreate(['email' => $data['email']], $data);
            $user->assignRole('customer');
        }

        $this->call(BookingsSeeder::class);
    }
}
