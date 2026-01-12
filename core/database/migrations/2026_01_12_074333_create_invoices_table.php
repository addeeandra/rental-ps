<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->string('reference_number')->nullable();
            $table->foreignId('partner_id')->constrained()->restrictOnDelete();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->enum('order_type', ['sales', 'rental'])->default('sales');
            $table->date('rental_start_date')->nullable();
            $table->date('rental_end_date')->nullable();
            $table->time('delivery_time')->nullable();
            $table->time('return_time')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->enum('status', ['unpaid', 'partial', 'paid', 'void'])->default('unpaid');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('shipping_fee', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('invoice_number');
            $table->index('partner_id');
            $table->index('invoice_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
