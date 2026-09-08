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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->integer('quantity_delta');
            $table->enum('movement_type', ['adjustment', 'transfer_in', 'transfer_out', 'po_receipt', 'so_fulfillment']);
            $table->unsignedBigInteger('reference_id')->nullable(); // PO or SO id
            $table->string('reason', 255)->nullable();
            $table->foreignId('user_id')->constrained(); // Who made the change
            $table->timestamps();
            
            // Index for fast reporting queries
            $table->index(['product_id', 'warehouse_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
