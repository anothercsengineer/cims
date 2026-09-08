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

            // 5. Trigger Low Stock Email Alert if necessary
            if ($quantityDelta < 0 && $stock->product->isLowStock()) {
                $admins = \App\Models\User::where('role', 'admin')->get();
                $productName = $stock->product->name;
                $currentTotal = $stock->product->totalStock();
                
                foreach ($admins as $admin) {
                    try {
                        \Illuminate\Support\Facades\Mail::raw(
                            "Alert: {$productName} has dropped to {$currentTotal} units, which is at or below its reorder point of {$stock->product->reorder_point}.",
                            function ($message) use ($admin, $productName) {
                                $message->to($admin->email)
                                        ->subject("Low Stock Alert: {$productName}");
                            }
                        );
                    } catch (\Exception $e) {
                        // Fail silently if mail server is not configured in local environment
                        \Illuminate\Support\Facades\Log::error("Failed to send low stock alert for {$productName}: " . $e->getMessage());
                    }
                }
            }

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
