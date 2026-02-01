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
        Schema::create('product_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('inventory_item_id')
                ->constrained()
                ->restrictOnDelete();
            $table->unsignedTinyInteger('slot')->comment('1 or 2');
            $table->decimal('qty_per_product', 10, 3)->default(1);
            $table->timestamps();

            $table->unique(['product_id', 'slot']);
            $table->index('inventory_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_components');
    }
};
