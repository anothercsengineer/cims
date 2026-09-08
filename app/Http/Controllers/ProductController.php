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
            'image' => ['nullable', 'image', 'max:2048'],
            'is_archived' => ['boolean'],
        ]);

        $validated['is_archived'] = $request->has('is_archived');

        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')->store('products', 'public');
        }

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
            'image' => ['nullable', 'image', 'max:2048'],
            'is_archived' => ['boolean'],
        ]);

        $validated['is_archived'] = $request->has('is_archived');

        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function import(Request $request)
    {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt']);
        
        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        fgetcsv($handle); // Skip header row
        
        while (($row = fgetcsv($handle)) !== false) {
            Product::updateOrCreate(
                ['sku' => $row[0]],
                [
                    'name' => $row[1],
                    'description' => $row[2],
                    'category_id' => !empty($row[3]) ? $row[3] : null,
                    'unit_of_measure' => $row[4] ?? 'ea',
                    'cost_price' => $row[5] ?? 0,
                    'sale_price' => $row[6] ?? 0,
                    'reorder_point' => $row[7] ?? 0,
                    'reorder_quantity' => $row[8] ?? 0,
                ]
            );
        }
        fclose($handle);
        
        return back()->with('success', 'Products bulk imported successfully!');
    }
}
