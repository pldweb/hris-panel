<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        config(['scout.driver' => null]);

        $superadmin = User::firstOrCreate([
            'email' => 'admin@hris.it',
        ], [
            'name' => 'Superadmin',
            'password' => Hash::make('password'),
            'profile_photo' => 'profile-pictures/male/3.avif',
        ]);

        if (!$superadmin->hasRole('superadmin')) {
            $superadmin->assignRole('superadmin');
        }

        if (!$superadmin->employeeProfile) {
            $employeeProfile = $superadmin->employeeProfile()->create([
                'code' => 'SUP001',
                'identity_number' => '9999999999',
                'phone' => '080000000000',
                'date_of_birth' => '1990-01-01',
                'gender' => 'male',
                'place_of_birth' => 'Admin City',
                'address' => 'Superadmin HQ',
                'city' => 'Jakarta',
                'postal_code' => '10000',
            ]);

            $employeeProfile->jobInformation()->create([
                'employee_id' => $employeeProfile->id,
                'job_title' => 'System Administrator',
                'years_experience' => 10,
                'status' => 'active',
                'employment_type' => 'full_time',
                'work_location' => 'remote',
                'start_date' => '2020-01-01',
                'monthly_salary' => 50000000,
                'skill_level' => 'expert',
            ]);

            $employeeProfile->bankInformation()->create([
                'employee_id' => $employeeProfile->id,
                'bank_name' => 'BCA',
                'account_number' => '0000000000',
                'account_holder_name' => 'Superadmin',
                'account_type' => 'saving',
            ]);
        }
    }
}
