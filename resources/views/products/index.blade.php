<x-app-layout>
    <div x-data="{ adjustModalOpen: false, productId: '', productName: '' }">
        <x-slot name="header">
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Products & Inventory') }}
                </h2>
                <div class="flex space-x-3">
                    <a href="{{ route('stock.transfer') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        Transfer Stock
                    </a>
                    <a href="{{ route('products.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        Add Product
                    </a>
                </div>
            </div>
        </x-slot>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                @if (session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif
                
                @if (session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                <!-- CSV Import Form -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center space-x-4">
                            @csrf
                            <div>
                                <label for="csv_file" class="block text-sm font-medium text-gray-700">Bulk Import Products (CSV)</label>
                                <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" required>
                            </div>
                            <div class="pt-6">
                                <x-primary-button type="submit">Import</x-primary-button>
                            </div>
                        </form>
                        <p class="text-xs text-gray-500 mt-2">Format: SKU, Name, Description, Category ID, UoM, Cost, Sale, Reorder Point, Reorder Qty</p>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Stock</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($products as $product)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $product->sku }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product->category ? $product->category->name : 'None' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold {{ $product->isLowStock() ? 'text-red-600' : 'text-gray-900' }}">
                                                {{ $product->totalStock() }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($product->is_archived)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Archived</span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                                <button @click="adjustModalOpen = true; productId = '{{ $product->id }}'; productName = '{{ addslashes($product->name) }}'" class="text-green-600 hover:text-green-900">Adjust Stock</button>
                                                <span class="text-gray-300">|</span>
                                                <a href="{{ route('products.edit', $product) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $products->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Adjustment Modal -->
        <div x-show="adjustModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                
                <div x-show="adjustModalOpen" x-transition.opacity class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="adjustModalOpen = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="adjustModalOpen" x-transition class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form method="POST" action="{{ route('stock.adjust') }}">
                        @csrf
                        <input type="hidden" name="product_id" x-model="productId">
                        
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Adjust Stock: <span x-text="productName" class="font-bold text-indigo-600"></span>
                                    </h3>
                                    
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <x-input-label for="warehouse_id" :value="__('Warehouse')" />
                                            <select id="warehouse_id" name="warehouse_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                                                @foreach($warehouses as $wh)
                                                    <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <x-input-label for="quantity_delta" :value="__('Quantity Change (+ or -)')" />
                                            <x-text-input id="quantity_delta" class="block mt-1 w-full" type="number" name="quantity_delta" required placeholder="e.g. 5 or -2" />
                                            <p class="text-xs text-gray-500 mt-1">Use a negative number to reduce stock.</p>
                                        </div>

                                        <div>
                                            <x-input-label for="reason" :value="__('Reason')" />
                                            <select id="reason" name="reason" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                                                <option value="count_correction">Count Correction (Inventory Audit)</option>
                                                <option value="damage">Damage / Spoilage</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>

                                        <div>
                                            <x-input-label for="note" :value="__('Note (Optional)')" />
                                            <x-text-input id="note" class="block mt-1 w-full" type="text" name="note" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 flex items-center justify-end">
                            <button type="button" @click="adjustModalOpen = false" class="text-gray-600 hover:text-gray-900 mr-4 font-medium text-sm">
                                Cancel
                            </button>
                            <x-primary-button>
                                {{ __('Save Adjustment') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
