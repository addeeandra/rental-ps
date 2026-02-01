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
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->foreignId('inventory_item_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('warehouse_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->decimal('qty_on_hand', 15, 3)->default(0);
            $table->decimal('qty_reserved', 15, 3)->default(0);
            $table->decimal('min_threshold', 15, 3)->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->primary(['inventory_item_id', 'warehouse_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_levels');
    }
};
