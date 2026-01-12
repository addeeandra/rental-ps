<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create or find test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User']
        );

        // Seed invoices with items
        if (Partner::count() > 0 && Product::count() > 0) {
            Invoice::factory(20)->create()->each(function ($invoice) {
                // Create 1-5 line items for each invoice
                $itemCount = rand(1, 5);
                $products = Product::inRandomOrder()->take($itemCount)->get();
                
                foreach ($products as $index => $product) {
                    $quantity = rand(1, 10);
                    $unitPrice = $invoice->order_type === 'sales' 
                        ? $product->sales_price 
                        : $product->rental_price;
                    
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $product->id,
                        'description' => $product->name,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total' => $quantity * $unitPrice,
                        'sort_order' => $index,
                    ]);
                }
                
                // Recalculate totals
                $invoice->calculateTotals();
                $invoice->updateStatus();
                $invoice->save();
            });
        }
    }
}
