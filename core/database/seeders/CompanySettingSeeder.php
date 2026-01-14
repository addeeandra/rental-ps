<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CompanySetting::firstOrCreate(
            ['id' => 1],
            [
                'company_name' => config('company.company_name'),
                'address' => config('company.address'),
                'city' => config('company.city'),
                'postal_code' => config('company.postal_code'),
                'phone' => config('company.phone'),
                'email' => config('company.email'),
                'website' => config('company.website'),
                'tax_number' => config('company.tax_number'),
                'invoice_number_prefix' => config('company.invoice_number_prefix'),
                'invoice_default_terms' => config('company.invoice_default_terms'),
                'invoice_default_notes' => config('company.invoice_default_notes'),
            ]
        );
    }
}
