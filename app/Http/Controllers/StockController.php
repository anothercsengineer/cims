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
            'quantity_delta' => 'required|integer|not_in:0',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $stockService->adjustStock(
                $validated['product_id'],
                $validated['warehouse_id'],
                $validated['quantity_delta'],
                'adjustment',
                auth()->id(),
                $validated['reason'] ?? 'Manual adjustment'
            );

            return back()->with('success', 'Stock adjusted successfully.');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to adjust stock: ' . $e->getMessage());
        }
    }

    public function showTransferForm()
    {
        $products = \App\Models\Product::where('is_archived', false)->get();
        $warehouses = \App\Models\Warehouse::where('is_active', true)->get();
        
        return view('stock.transfer', compact('products', 'warehouses'));
    }

    public function executeTransfer(Request $request, StockService $stockService)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_warehouse_id' => 'required|exists:warehouses,id|different:to_warehouse_id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $stockService->transferStock(
                $validated['product_id'],
                $validated['from_warehouse_id'],
                $validated['to_warehouse_id'],
                $validated['quantity'],
                auth()->id()
            );

            return redirect()->route('products.index')->with('success', 'Stock transferred successfully.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Transfer failed: ' . $e->getMessage());
        }
    }
}
