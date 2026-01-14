<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Company Information
    |--------------------------------------------------------------------------
    |
    | This information will be used as default values when seeding the
    | company_settings table. These can be changed via the admin UI.
    |
    */

    'company_name' => env('COMPANY_NAME', 'XGamePS'),
    'address' => env('COMPANY_ADDRESS', 'Shoji Land Blok AA-15 Candi'),
    'city' => env('COMPANY_CITY', 'Sidoarjo, Jawa Timur'),
    'postal_code' => env('COMPANY_POSTAL', '61271'),
    'phone' => env('COMPANY_PHONE', '+62 851 7303 0093'),
    'email' => env('COMPANY_EMAIL', 'xgameps.sda@gmail.com'),
    'website' => env('COMPANY_WEBSITE', 'https://xgameps.com'),
    'tax_number' => env('COMPANY_TAX_NUMBER', null),
    
    /*
    |--------------------------------------------------------------------------
    | Invoice Configuration
    |--------------------------------------------------------------------------
    |
    | Default configuration for invoice generation.
    |
    */

    'invoice_number_prefix' => env('INVOICE_NUMBER_PREFIX', 'INV'),
    'invoice_default_terms' => env('INVOICE_DEFAULT_TERMS', null),
    'invoice_default_notes' => env('INVOICE_DEFAULT_NOTES', null),
    
    /*
    |--------------------------------------------------------------------------
    | Other Invoice Settings
    |--------------------------------------------------------------------------
    |
    | These settings remain in config as they are technical settings,
    | not business information.
    |
    */

    'due_days' => env('INVOICE_DUE_DAYS', 30),
    'number_digits' => env('INVOICE_NUMBER_DIGITS', 4),
];
