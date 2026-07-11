<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        Vendor::create([
            'company_name' => 'TechEvents Dubai',
            'contact_person' => 'Ahmed Hassan',
            'email' => 'vendor@example.com',
            'phone' => '+971501234567',
            'address' => 'Dubai Internet City, Building 3',
            'website' => 'https://techevents.ae',
            'kyc_status' => 'verified',
            'bank_name' => 'Emirates NBD',
            'account_holder_name' => 'TechEvents Dubai LLC',
            'account_number' => '1234567890',
            'iban' => 'AE1234567890123456789012',
            'swift_code' => 'EBILAEAD',
            'is_active' => true,
        ]);

        Vendor::create([
            'company_name' => 'Concert Masters',
            'contact_person' => 'Sara Al-Rashid',
            'email' => 'concerts@example.com',
            'phone' => '+971509876543',
            'address' => 'Dubai Marina, Tower 5',
            'website' => 'https://concertmasters.ae',
            'kyc_status' => 'pending',
            'is_active' => true,
        ]);

        $this->command->info('Vendors seeded successfully.');
    }
}
