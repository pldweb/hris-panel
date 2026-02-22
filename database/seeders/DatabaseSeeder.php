<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Disable Meilisearch Scout sync during seeding
        config(['scout.driver' => null]);

        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            SuperadminSeeder::class,
            ManagerSeeder::class,
            EmployeeSeeder::class,
            HrSeeder::class,
            FinanceSeeder::class,
            TeamSeeder::class,
        ]);
    }
}
