<?php

namespace App\Console\Commands;

use App\Models\CompanySetting;
use Illuminate\Console\Command;

class SetupCompany extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup-company';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup company information for invoices and documents';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Company Setup');
        $this->info('-------------');
        $this->newLine();

        $settings = CompanySetting::current();

        $settings->company_name = $this->ask('Company Name', $settings->company_name ?? 'My Company');
        $settings->email = $this->ask('Email', $settings->email);
        $settings->phone = $this->ask('Phone', $settings->phone);
        $settings->address = $this->ask('Address', $settings->address);
        $settings->city = $this->ask('City', $settings->city);
        $settings->postal_code = $this->ask('Postal Code', $settings->postal_code);
        $settings->website = $this->ask('Website', $settings->website);
        $settings->tax_number = $this->ask('Tax Number (optional)', $settings->tax_number);
        $settings->invoice_number_prefix = $this->ask('Invoice Number Prefix', $settings->invoice_number_prefix ?? 'INV');
        $settings->invoice_default_terms = $this->ask('Default Invoice Terms (optional)', $settings->invoice_default_terms);
        $settings->invoice_default_notes = $this->ask('Default Invoice Notes (optional)', $settings->invoice_default_notes);

        $settings->save();

        $this->newLine();
        $this->info('✓ Company settings saved successfully!');
        $this->newLine();

        $this->table(
            ['Setting', 'Value'],
            [
                ['Company Name', $settings->company_name],
                ['Email', $settings->email ?? '-'],
                ['Phone', $settings->phone ?? '-'],
                ['Address', $settings->address ?? '-'],
                ['City', $settings->city ?? '-'],
                ['Postal Code', $settings->postal_code ?? '-'],
                ['Website', $settings->website ?? '-'],
                ['Tax Number', $settings->tax_number ?? '-'],
                ['Invoice Prefix', $settings->invoice_number_prefix],
            ]
        );

        return self::SUCCESS;
    }
}
