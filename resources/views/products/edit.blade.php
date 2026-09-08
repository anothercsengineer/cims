<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Product: ') }} {{ $product->sku }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    
                    <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Info -->
                            <div class="space-y-4">
                                <div>
                                    <x-input-label for="sku" :value="__('SKU')" />
                                    <x-text-input id="sku" class="block mt-1 w-full bg-gray-100" type="text" name="sku" :value="old('sku', $product->sku)" readonly />
                                    <p class="text-xs text-gray-500 mt-1">SKU cannot be changed after creation.</p>
                                    <x-input-error :messages="$errors->get('sku')" class="mt-2" />
                                </div>
                                
                                <div>
                                    <x-input-label for="name" :value="__('Product Name')" />
                                    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $product->name)" required />
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="description" :value="__('Description')" />
                                    <textarea id="description" name="description" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="3">{{ old('description', $product->description) }}</textarea>
                                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                </div>
                                
                                <div>
                                    <x-input-label for="category_id" :value="__('Category')" />
                                    <select id="category_id" name="category_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="">None</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                                </div>
                            </div>
                            
                            <!-- Pricing & Inventory Settings -->
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="cost_price" :value="__('Cost Price')" />
                                        <x-text-input id="cost_price" class="block mt-1 w-full" type="number" step="0.01" name="cost_price" :value="old('cost_price', $product->cost_price)" required />
                                    </div>
                                    <div>
                                        <x-input-label for="sale_price" :value="__('Sale Price')" />
                                        <x-text-input id="sale_price" class="block mt-1 w-full" type="number" step="0.01" name="sale_price" :value="old('sale_price', $product->sale_price)" required />
                                    </div>
                                </div>

                                <div>
                                    <x-input-label for="unit_of_measure" :value="__('Unit of Measure')" />
                                    <x-text-input id="unit_of_measure" class="block mt-1 w-full" type="text" name="unit_of_measure" :value="old('unit_of_measure', $product->unit_of_measure)" required />
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="reorder_point" :value="__('Reorder Point (Low Stock Alert)')" />
                                        <x-text-input id="reorder_point" class="block mt-1 w-full" type="number" name="reorder_point" :value="old('reorder_point', $product->reorder_point)" required />
                                    </div>
                                    <div>
                                        <x-input-label for="reorder_quantity" :value="__('Reorder Quantity')" />
                                        <x-text-input id="reorder_quantity" class="block mt-1 w-full" type="number" name="reorder_quantity" :value="old('reorder_quantity', $product->reorder_quantity)" required />
                                        <x-input-error :messages="$errors->get('reorder_quantity')" class="mt-2" />
                                    </div>
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="image" :value="__('Product Image')" />
                                    @if($product->image_url)
                                        <div class="mt-2 mb-4">
                                            <img src="{{ Storage::url($product->image_url) }}" alt="{{ $product->name }}" class="w-32 h-32 object-cover rounded-md border">
                                        </div>
                                    @endif
                                    <input id="image" class="block mt-1 w-full border-gray-300 shadow-sm" type="file" name="image" accept="image/*" />
                                    <x-input-error :messages="$errors->get('image')" class="mt-2" />
                                </div>
                                
                                <div class="block mt-6">
                                    <label for="is_archived" class="inline-flex items-center">
                                        <input id="is_archived" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm" name="is_archived" value="1" {{ old('is_archived', $product->is_archived) ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-600">{{ __('Archived') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-8 border-t pt-4">
                            <a href="{{ route('products.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</a>
                            <x-primary-button>
                                {{ __('Update Product') }}
                            </x-primary-button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
