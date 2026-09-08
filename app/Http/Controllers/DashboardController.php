<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Calculate Core Metrics
        $products = Product::with('stockLevels')->where('is_archived', false)->get();
        
        $totalProducts = $products->count();
        
        $lowStockCount = $products->filter(function($product) {
            return $product->isLowStock();
        })->count();

        $totalInventoryValue = $products->sum(function($product) {
            return $product->totalStock() * $product->cost_price;
        });

        // 2. Fetch Pending Orders
        $pendingPOs = PurchaseOrder::where('status', 'ordered')->count();
        $pendingSOs = SalesOrder::where('status', 'pending')->count();

        // 3. Fetch Recent Stock Movements (Live Ledger Feed)
        $recentMovements = StockMovement::with(['product', 'warehouse', 'user'])
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        return view('dashboard', compact(
            'totalProducts', 
            'lowStockCount', 
            'totalInventoryValue', 
            'pendingPOs', 
            'pendingSOs',
            'recentMovements'
        ));
    }
}
