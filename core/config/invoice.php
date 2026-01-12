<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Company Information
    |--------------------------------------------------------------------------
    |
    | This information will be displayed on invoices and other documents.
    |
    */

    'company_name' => env('INVOICE_COMPANY_NAME', 'XGamePS'),
    'address' => env('INVOICE_COMPANY_ADDRESS', 'Shoji Land Blok AA-15 Candi'),
    'city' => env('INVOICE_COMPANY_CITY', 'Sidoarjo, Jawa Timur'),
    'postal_code' => env('INVOICE_COMPANY_POSTAL', '61271'),
    'phone' => env('INVOICE_COMPANY_PHONE', '+62 851 7303 0093'),
    'email' => env('INVOICE_COMPANY_EMAIL', 'xgameps.sda@gmail.com'),
    'website' => env('INVOICE_COMPANY_WEBSITE', 'https://xgameps.com'),
    
    /*
    |--------------------------------------------------------------------------
    | Company Logo
    |--------------------------------------------------------------------------
    |
    | Path to the company logo (relative to public folder).
    | Set to null if no logo is available.
    |
    */

    'logo_path' => env('INVOICE_LOGO_PATH', null),

    /*
    |--------------------------------------------------------------------------
    | Default Due Days
    |--------------------------------------------------------------------------
    |
    | Default number of days until invoice is due.
    | This will be added to the invoice date to calculate the due date.
    |
    */

    'due_days' => env('INVOICE_DUE_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Invoice Number Format
    |--------------------------------------------------------------------------
    |
    | Format for invoice numbers. The system uses INV-YYYY-#### format
    | where YYYY is the year and #### is a 4-digit sequence number.
    |
    */

    'number_prefix' => env('INVOICE_NUMBER_PREFIX', 'INV'),
    'number_digits' => env('INVOICE_NUMBER_DIGITS', 4),
];
