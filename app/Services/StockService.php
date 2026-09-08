<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Exception;

class StockService
{
    /**
     * Adjust stock for a product at a warehouse.
     * This is the ONLY way stock should ever be modified to ensure integrity.
     */
    public function adjustStock(
        int $productId,
        int $warehouseId,
        int $quantityDelta,
        string $movementType,
        int $userId,
        ?string $reason = null,
        ?int $referenceId = null
    ): StockMovement {
        return DB::transaction(function () use (
            $productId, $warehouseId, $quantityDelta,
            $movementType, $userId, $reason, $referenceId
        ) {
            // 1. Lock the stock row for update (or create it if it doesn't exist)
            // This prevents race conditions during concurrent adjustments
            $stock = Stock::firstOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $warehouseId],
                ['quantity' => 0]
            );

            // Re-fetch with lockForUpdate to ensure we have the row locked
            $stock = Stock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            // 2. Calculate new quantity
            $newQuantity = $stock->quantity + $quantityDelta;

            // Prevent negative stock for fulfillments/transfers
            if ($newQuantity < 0 && in_array($movementType, ['so_fulfillment', 'transfer_out'])) {
                throw new Exception("Insufficient stock. Attempted to reduce stock below 0.");
            }

            // 3. Update stock quantity
            $stock->quantity = $newQuantity;
            $stock->save();

            // 4. Log the movement in the same transaction
            $movement = StockMovement::create([
                'product_id'     => $productId,
                'warehouse_id'   => $warehouseId,
                'quantity_delta' => $quantityDelta,
                'movement_type'  => $movementType,
                'reference_id'   => $referenceId,
                'reason'         => $reason,
                'user_id'        => $userId,
            ]);

            // Note: In the future, we can dispatch a LowStockDetected event here 
            // if ($stock->product->isLowStock()) { ... }

            return $movement;
        });
    }

    /**
     * Transfer stock between two warehouses atomically.
     */
    public function transferStock(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $quantity,
        int $userId
    ): void {
        if ($quantity <= 0) {
            throw new Exception("Transfer quantity must be greater than 0.");
        }

        DB::transaction(function () use (
            $productId, $fromWarehouseId, $toWarehouseId, $quantity, $userId
        ) {
            // Deduct from source
            $this->adjustStock(
                $productId, 
                $fromWarehouseId, 
                -$quantity,
                'transfer_out', 
                $userId, 
                "Transfer to warehouse #{$toWarehouseId}"
            );

            // Add to destination
            $this->adjustStock(
                $productId, 
                $toWarehouseId, 
                $quantity,
                'transfer_in', 
                $userId, 
                "Transfer from warehouse #{$fromWarehouseId}"
            );
        });
    }
}
