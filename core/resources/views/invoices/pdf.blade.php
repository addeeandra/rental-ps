<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        
        .container {
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            display: table;
            width: 100%;
            margin-bottom: 1rem;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }
        
        .header-right {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: top;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-info {
            color: #666;
            font-size: 10px;
            line-height: 1.5;
        }
        
        .logo {
            max-width: 150px;
            max-height: 80px;
            margin-bottom: 10px;
        }
        
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        
        .invoice-number {
            font-size: 14px;
            color: #666;
        }
        
        .info-section {
            display: table;
            width: 100%;
        }
        
        .info-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }
        
        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }
        
        .info-box {
            background: #f9f9f9;
            padding: 12px;
            border-radius: 4px;
            font-size: 11px;
            margin-bottom: 15px;
        }
        
        .info-box strong {
            display: block;
            font-size: 12px;
        }
        
        .rental-info {
            background: #fff3cd;
            padding: 12px;
            font-size: 11px;
        }
        
        .rental-info strong {
            display: block;
            font-size: 12px;
        }

        .invoice-status {
            color: #55f;
        }
        
        table.invoice-info {
            padding-left: 80px;
            width: 100%;
            border-collapse: collapse;
        }
        
        table.invoice-info td {
            font-size: 10px;
        }
        
        table.line-items {
            width: 100%;
            border-collapse: collapse;
        }
        
        table.line-items th {
            background: #333;
            color: white;
            padding: 0.5rem;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
        }
        
        table.line-items td {
            padding: 0.5rem;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }
        
        table.line-items tr:last-child td {
            border-bottom: none;
        }
        
        table.line-items th:last-child,
        table.line-items td:last-child {
            text-align: right;
        }
        
        table.line-items th:nth-child(2),
        table.line-items td:nth-child(2) {
            text-align: center;
        }
        
        .totals-section {
            margin-top: 30px;
            float: right;
            width: 300px;
        }
        
        .totals-row {
            display: table;
            width: 100%;
        }
        
        .totals-label {
            display: table-cell;
            text-align: left;
            font-size: 12px;
            padding-right: 20px;
        }
        
        .totals-value {
            display: table-cell;
            text-align: right;
            font-size: 12px;
        }
        
        .totals-row.grand-total {
            border-top: 2px solid #333;
            padding-top: 12px;
            margin-top: 10px;
        }
        
        .totals-row.grand-total .totals-label,
        .totals-row.grand-total .totals-value {
            font-size: 16px;
            font-weight: bold;
        }
        
        .footer-section {
            clear: both;
            margin-top: 40px;
            padding-top: 20px;
        }
        
        .notes-section,
        .terms-section {
            margin-bottom: 20px;
        }
        
        .notes-section strong,
        .terms-section strong {
            display: block;
            font-size: 13px;
            margin-bottom: 5px;
        }
        
        .notes-content,
        .terms-content {
            background: #f9f9f9;
            padding: 12px;
            border-left: 3px solid #666;
            font-size: 11px;
            line-height: 1.5;
            white-space: pre-wrap;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .status-unpaid {
            background: #ffc107;
            color: #000;
        }
        
        .status-partial {
            background: #17a2b8;
            color: white;
        }
        
        .status-paid {
            background: #28a745;
            color: white;
        }
        
        .status-void {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                @if(config('invoice.logo_path'))
                    <img src="{{ public_path(config('invoice.logo_path')) }}" alt="Company Logo" class="logo">
                @endif
                <div class="company-name">{{ config('invoice.company_name') }}</div>
                <div class="company-info">
                    {{ config('invoice.address') }}<br>
                    {{ config('invoice.city') }} {{ config('invoice.postal_code') }}<br>
                    Phone: {{ config('invoice.phone') }}<br>
                    Email: {{ config('invoice.email') }}
                    @if(config('invoice.website'))
                        <br>{{ config('invoice.website') }}
                    @endif
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">INVOICE</div>
                <table class="invoice-info">
                    <tr>
                        <td>No. Tagihan</td>
                        <td class="text-right">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td>Tanggal</td>
                        <td class="text-right">{{ $invoice->invoice_date->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td>Tgl. Jatuh Tempo</td>
                        <td class="text-right">{{ $invoice->due_date->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td class="text-right invoice-status">{{ $invoice->status->label() }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Invoice Info and Customer -->
        <div class="info-section">
            <div class="info-left">
                <div class="section-title">Tagihan Kepada</div>
                <div class="info-box">
                    <strong>{{ $invoice->partner->name }}</strong>
                    @if($invoice->partner->address_line_1)
                        {{ $invoice->partner->address_line_1 }}<br>
                    @endif
                    @if($invoice->partner->address_line_2)
                        {{ $invoice->partner->address_line_2 }}<br>
                    @endif
                    @if($invoice->partner->city || $invoice->partner->postal_code)
                        {{ $invoice->partner->city }} {{ $invoice->partner->postal_code }}<br>
                    @endif
                    @if($invoice->partner->phone)
                        Phone: {{ $invoice->partner->phone }}<br>
                    @endif
                    @if($invoice->partner->email)
                        Email: {{ $invoice->partner->email }}
                    @endif
                </div>
            </div>
        
            <!-- Rental Information (if rental order) -->
            @if($invoice->order_type->value === 'rental' && $invoice->rental_start_date)
                <div class="info-right">
                    <div class="section-title">Informasi Sewa</div>
                    <div class="rental-info">
                        <div>
                            <strong>Periode Sewa:</strong> {{ $invoice->rental_start_date->format('d M Y') }} - {{ $invoice->rental_end_date->format('d M Y') }}
                        </div>
                        <div>
                            <strong>Jml. Hari Sewa:</strong> {{ $invoice->rental_days }} hari
                        </div>
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Line Items -->
        <table class="line-items">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="45%">Description</th>
                    <th width="10%">Qty</th>
                    <th width="15%">Unit Price</th>
                    @if($invoice->order_type->value === 'rental')
                        <th width="10%">Days</th>
                    @endif
                    <th width="15%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->invoiceItems as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->display_description }}</td>
                        <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                        <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        @if($invoice->order_type->value === 'rental')
                            <td class="text-center">{{ $invoice->rental_days ?? '-' }}</td>
                        @endif
                        <td class="text-right">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-row">
                <div class="totals-label">Subtotal:</div>
                <div class="totals-value">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</div>
            </div>
            
            @if($invoice->discount_amount > 0)
                <div class="totals-row">
                    <div class="totals-label">Discount:</div>
                    <div class="totals-value">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</div>
                </div>
            @endif
            
            @if($invoice->tax_amount > 0)
                <div class="totals-row">
                    <div class="totals-label">Tax:</div>
                    <div class="totals-value">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</div>
                </div>
            @endif
            
            @if($invoice->shipping_fee > 0)
                <div class="totals-row">
                    <div class="totals-label">Shipping Fee:</div>
                    <div class="totals-value">Rp {{ number_format($invoice->shipping_fee, 0, ',', '.') }}</div>
                </div>
            @endif
            
            <div class="totals-row grand-total">
                <div class="totals-label">Total:</div>
                <div class="totals-value">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</div>
            </div>
            
            <div class="totals-row">
                <div class="totals-label">Paid Amount:</div>
                <div class="totals-value">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</div>
            </div>
            
            <div class="totals-row">
                <div class="totals-label">Balance Due:</div>
                <div class="totals-value">Rp {{ number_format($invoice->balance, 0, ',', '.') }}</div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer-section">
            @if($invoice->notes)
                <div class="notes-section">
                    <strong>Notes:</strong>
                    <div class="notes-content">{{ $invoice->notes }}</div>
                </div>
            @endif
            
            @if($invoice->terms)
                <div class="terms-section">
                    <strong>Terms & Conditions:</strong>
                    <div class="terms-content">{{ $invoice->terms }}</div>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
