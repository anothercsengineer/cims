<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New Purchase Order') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="poForm()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    
                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>- {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('purchase-orders.store') }}">
                        @csrf
                        
                        <!-- Order Details Header -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 border-b pb-6">
                            <div>
                                <x-input-label for="supplier_id" :value="__('Supplier')" />
                                <select id="supplier_id" name="supplier_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                                    <option value="">Select a Supplier...</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="warehouse_id" :value="__('Destination Warehouse')" />
                                <select id="warehouse_id" name="warehouse_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                                    <option value="">Select a Warehouse...</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="expected_delivery_date" :value="__('Expected Delivery Date')" />
                                <x-text-input id="expected_delivery_date" class="block mt-1 w-full" type="date" name="expected_delivery_date" :value="old('expected_delivery_date')" />
                            </div>
                        </div>

                        <!-- Dynamic Items Section -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Order Items</h3>
                            
                            <div class="space-y-4">
                                <template x-for="(item, index) in items" :key="index">
                                    <div class="flex flex-wrap md:flex-nowrap items-end gap-4 p-4 border rounded-md bg-gray-50">
                                        
                                        <div class="w-full md:w-1/2">
                                            <x-input-label x-bind:for="'items_' + index + '_product_id'" value="Product" />
                                            <select x-model="item.product_id" x-bind:name="'items[' + index + '][product_id]'" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                                                <option value="">Select a Product...</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}">{{ $product->name }} (SKU: {{ $product->sku }})</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="w-full md:w-1/6">
                                            <x-input-label x-bind:for="'items_' + index + '_quantity'" value="Quantity" />
                                            <x-text-input x-model="item.quantity" x-bind:name="'items[' + index + '][quantity]'" type="number" min="1" class="block mt-1 w-full" required />
                                        </div>

                                        <div class="w-full md:w-1/6">
                                            <x-input-label x-bind:for="'items_' + index + '_unit_cost'" value="Unit Cost ($)" />
                                            <x-text-input x-model="item.unit_cost" x-bind:name="'items[' + index + '][unit_cost]'" type="number" step="0.01" min="0" class="block mt-1 w-full" required />
                                        </div>

                                        <div class="w-full md:w-1/6 pb-2 font-semibold text-gray-700">
                                            $<span x-text="((item.quantity || 0) * (item.unit_cost || 0)).toFixed(2)"></span>
                                        </div>

                                        <div class="w-full md:w-auto pb-2">
                                            <button type="button" @click="removeItem(index)" class="text-red-600 hover:text-red-800 font-medium text-sm" x-show="items.length > 1">
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-4">
                                <button type="button" @click="addItem()" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300">
                                    + Add Another Product
                                </button>
                            </div>
                        </div>
                        
                        <!-- Totals & Notes -->
                        <div class="border-t pt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="notes" :value="__('Order Notes')" />
                                <textarea id="notes" name="notes" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" rows="3">{{ old('notes') }}</textarea>
                            </div>
                            
                            <div class="flex flex-col items-end justify-center bg-gray-50 p-4 rounded-md">
                                <span class="text-gray-500 text-sm uppercase font-semibold">Total PO Amount</span>
                                <span class="text-3xl font-bold text-gray-900">$<span x-text="calculateTotal()"></span></span>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-end mt-8 pt-4 border-t">
                            <a href="{{ route('purchase-orders.index') }}" class="text-gray-600 hover:text-gray-900 mr-4 font-medium text-sm">Cancel</a>
                            <x-primary-button>
                                {{ __('Create Purchase Order') }}
                            </x-primary-button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- Alpine.js logic for dynamic form items -->
    <script>
        function poForm() {
            return {
                items: [
                    { product_id: '', quantity: 1, unit_cost: 0.00 }
                ],
                addItem() {
                    this.items.push({ product_id: '', quantity: 1, unit_cost: 0.00 });
                },
                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                },
                calculateTotal() {
                    let total = 0;
                    this.items.forEach(item => {
                        let q = parseFloat(item.quantity) || 0;
                        let c = parseFloat(item.unit_cost) || 0;
                        total += (q * c);
                    });
                    return total.toFixed(2);
                }
            }
        }
    </script>
</x-app-layout>
