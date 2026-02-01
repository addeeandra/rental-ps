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
        Schema::create('invoice_item_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_item_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('inventory_item_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('warehouse_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('owner_id')
                ->constrained('partners')
                ->restrictOnDelete();
            $table->decimal('qty', 15, 3);
            $table->decimal('share_percent', 5, 2)->default(0);
            $table->decimal('share_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->index('invoice_item_id');
            $table->index('inventory_item_id');
            $table->index('owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_item_components');
    }
};
