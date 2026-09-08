<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['supplier', 'warehouse'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return view('purchase_orders.index', compact('purchaseOrders'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_archived', false)->orderBy('name')->get();

        return view('purchase_orders.create', compact('suppliers', 'warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                // Generate a unique PO Number
                $poNumber = 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

                $totalAmount = 0;
                
                $po = PurchaseOrder::create([
                    'po_number' => $poNumber,
                    'supplier_id' => $validated['supplier_id'],
                    'warehouse_id' => $validated['warehouse_id'],
                    'status' => 'ordered', // Default to ordered
                    'expected_delivery_date' => $validated['expected_delivery_date'],
                    'total_amount' => 0, // Will update below
                    'notes' => $validated['notes'],
                ]);

                foreach ($validated['items'] as $item) {
                    $totalCost = $item['quantity'] * $item['unit_cost'];
                    $totalAmount += $totalCost;

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_cost' => $item['unit_cost'],
                        'total_cost' => $totalCost,
                    ]);
                }

                $po->update(['total_amount' => $totalAmount]);
            });

            return redirect()->route('purchase-orders.index')->with('success', 'Purchase Order created successfully.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Error creating PO: ' . $e->getMessage());
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'warehouse', 'items.product']);
        return view('purchase_orders.show', compact('purchaseOrder'));
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder, StockService $stockService)
    {
        if ($purchaseOrder->status === 'received') {
            return back()->with('error', 'This purchase order has already been received.');
        }

        try {
            DB::transaction(function () use ($purchaseOrder, $stockService) {
                // 1. Mark PO as received
                $purchaseOrder->update(['status' => 'received']);

                // 2. Add inventory to warehouse for each item
                foreach ($purchaseOrder->items as $item) {
                    $stockService->adjustStock(
                        $item->product_id,
                        $purchaseOrder->warehouse_id,
                        $item->quantity,
                        'po_receipt',
                        auth()->id(),
                        'Received via ' . $purchaseOrder->po_number,
                        $purchaseOrder->id
                    );
                }
            });

            return back()->with('success', 'Purchase order received! Inventory has been updated.');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to receive PO: ' . $e->getMessage());
        }
    }
}
