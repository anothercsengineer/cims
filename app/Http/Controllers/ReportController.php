<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Export the current inventory snapshot as a CSV.
     */
    public function exportInventory()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inventory_snapshot_' . date('Ymd_His') . '.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, ['SKU', 'Product Name', 'Category', 'Total Stock', 'Unit Cost', 'Total Value ($)', 'Status']);

            // Process in chunks to prevent out-of-memory errors on massive databases
            Product::with(['category', 'stockLevels'])->chunk(100, function ($products) use ($file) {
                foreach ($products as $product) {
                    $totalStock = $product->totalStock();
                    $totalValue = $totalStock * $product->cost_price;
                    
                    fputcsv($file, [
                        $product->sku,
                        $product->name,
                        $product->category->name ?? 'Uncategorized',
                        $totalStock,
                        $product->cost_price,
                        number_format($totalValue, 2, '.', ''),
                        $product->isLowStock() ? 'Low Stock' : 'Healthy'
                    ]);
                }
            });
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export the full immutable stock ledger as a CSV.
     */
    public function exportLedger()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="stock_ledger_' . date('Ymd_His') . '.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, ['Date', 'Movement Type', 'Product SKU', 'Product Name', 'Warehouse', 'Quantity Delta', 'User', 'Reference ID', 'Reason']);

            // Process in chunks
            StockMovement::with(['product', 'warehouse', 'user'])
                ->orderBy('created_at', 'desc')
                ->chunk(250, function ($movements) use ($file) {
                    foreach ($movements as $movement) {
                        fputcsv($file, [
                            $movement->created_at->format('Y-m-d H:i:s'),
                            $movement->movement_type,
                            $movement->product->sku ?? 'N/A',
                            $movement->product->name ?? 'Deleted Product',
                            $movement->warehouse->name ?? 'Deleted Warehouse',
                            $movement->quantity_delta,
                            $movement->user->name ?? 'System',
                            $movement->reference_id,
                            $movement->reason
                        ]);
                    }
                });
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
