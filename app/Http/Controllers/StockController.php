<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StockService;
use Exception;

class StockController extends Controller
{
    /**
     * Handle manual stock adjustments from the UI.
     */
    public function adjust(Request $request, StockService $stockService)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity_delta' => 'required|integer',
            'reason' => 'required|string|in:damage,count_correction,other',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            $reasonText = ucfirst(str_replace('_', ' ', $validated['reason']));
            if (!empty($validated['note'])) {
                $reasonText .= ': ' . $validated['note'];
            }

            $stockService->adjustStock(
                $validated['product_id'],
                $validated['warehouse_id'],
                $validated['quantity_delta'],
                'adjustment', 
                auth()->id(),
                $reasonText
            );

            return back()->with('success', 'Stock adjusted successfully.');
            
        } catch (Exception $e) {
            return back()->with('error', 'Failed to adjust stock: ' . $e->getMessage());
        }
    }
}
