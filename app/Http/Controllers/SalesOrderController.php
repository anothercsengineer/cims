<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class SalesOrderController extends Controller
{
    public function index()
    {
        $salesOrders = SalesOrder::with(['customer', 'warehouse'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return view('sales_orders.index', compact('salesOrders'));
    }

    public function create()
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        // Eager load stock levels to optionally show available stock in the UI
        $products = Product::where('is_archived', false)->with('stockLevels')->orderBy('name')->get();

        return view('sales_orders.create', compact('customers', 'warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                // Generate a unique SO Number
                $soNumber = 'SO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

                $totalAmount = 0;
                
                $so = SalesOrder::create([
                    'so_number' => $soNumber,
                    'customer_id' => $validated['customer_id'],
                    'warehouse_id' => $validated['warehouse_id'],
                    'status' => 'pending', // Default to pending
                    'order_date' => $validated['order_date'],
                    'total_amount' => 0, // Will update below
                    'notes' => $validated['notes'],
                ]);

                foreach ($validated['items'] as $item) {
                    $totalPrice = $item['quantity'] * $item['unit_price'];
                    $totalAmount += $totalPrice;

                    SalesOrderItem::create([
                        'sales_order_id' => $so->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $totalPrice,
                    ]);
                }

                $so->update(['total_amount' => $totalAmount]);
            });

            return redirect()->route('sales-orders.index')->with('success', 'Sales Order created successfully.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Error creating SO: ' . $e->getMessage());
        }
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load(['customer', 'warehouse', 'items.product']);
        return view('sales_orders.show', compact('salesOrder'));
    }

    public function fulfill(Request $request, SalesOrder $salesOrder, StockService $stockService)
    {
        if ($salesOrder->status === 'fulfilled') {
            return back()->with('error', 'This sales order has already been fulfilled.');
        }

        try {
            DB::transaction(function () use ($salesOrder, $stockService) {
                // 1. Mark SO as fulfilled
                $salesOrder->update(['status' => 'fulfilled']);

                // 2. Deduct inventory from warehouse for each item (Notice the negative quantity)
                foreach ($salesOrder->items as $item) {
                    $stockService->adjustStock(
                        $item->product_id,
                        $salesOrder->warehouse_id,
                        -($item->quantity), // Negative adjustment
                        'so_fulfillment',
                        auth()->id(),
                        'Fulfilled SO ' . $salesOrder->so_number,
                        $salesOrder->id
                    );
                }
            });

            return back()->with('success', 'Sales order fulfilled! Inventory has been successfully deducted.');
        } catch (Exception $e) {
            // This catches the "Insufficient stock" exception from our StockService
            return back()->with('error', 'Failed to fulfill SO: ' . $e->getMessage());
        }
    }
}
