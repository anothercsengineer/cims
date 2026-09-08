<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category', 'stockLevels')
            ->orderBy('name')
            ->paginate(15);
            
        $warehouses = \App\Models\Warehouse::where('is_active', true)->orderBy('name')->get();
            
        return view('products.index', compact('products', 'warehouses'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50', 'unique:products'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'unit_of_measure' => ['required', 'string', 'max:20'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'reorder_point' => ['required', 'integer', 'min:0'],
            'reorder_quantity' => ['required', 'integer', 'min:0'],
            'is_archived' => ['boolean'],
        ]);

        $validated['is_archived'] = $request->has('is_archived');

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50', Rule::unique('products')->ignore($product->id)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'unit_of_measure' => ['required', 'string', 'max:20'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'reorder_point' => ['required', 'integer', 'min:0'],
            'reorder_quantity' => ['required', 'integer', 'min:0'],
            'is_archived' => ['boolean'],
        ]);

        $validated['is_archived'] = $request->has('is_archived');

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }
}
