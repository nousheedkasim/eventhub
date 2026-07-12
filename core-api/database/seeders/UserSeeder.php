<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create the Admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'type' => 'admin',
        ]);

        // 2. Create the Attendee User
        User::create([
            'name' => 'Attendee User',
            'email' => 'attendee@example.com',
            'password' => Hash::make('password123'),
            'type' => 'attendee',
        ]);

         User::create([
            'name' => 'Vendor User',
            'email' => 'vendor@example.com',
            'password' => Hash::make('password123'),
            'type' => 'vendor',
        ]);

        // 3. Print the success message to the terminal screen
        $this->command->info('--------------------------------------------------');
        $this->command->info(' Seeded successfully! Use these for your REST API:');
        $this->command->info(' Admin:     admin@example.com / password123');
        $this->command->info(' Attendee:  attendee@example.com / password123');
        $this->command->info(' Vendor:    vendor@example.com / password123');
        $this->command->info('--------------------------------------------------');
    }
}