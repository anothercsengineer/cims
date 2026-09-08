<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Transfer Stock Between Warehouses') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            
            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    
                    <form method="POST" action="{{ route('stock.transfer.execute') }}">
                        @csrf
                        
                        <div class="space-y-6">
                            <div>
                                <x-input-label for="product_id" :value="__('Select Product')" />
                                <select id="product_id" name="product_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                                    <option value="">Choose a Product...</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} (SKU: {{ $product->sku }})</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="from_warehouse_id" :value="__('From Warehouse (Source)')" />
                                    <select id="from_warehouse_id" name="from_warehouse_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                                        <option value="">Ship From...</option>
                                        @foreach($warehouses as $wh)
                                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('from_warehouse_id')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="to_warehouse_id" :value="__('To Warehouse (Destination)')" />
                                    <select id="to_warehouse_id" name="to_warehouse_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                                        <option value="">Receive At...</option>
                                        @foreach($warehouses as $wh)
                                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('to_warehouse_id')" class="mt-2" />
                                </div>
                            </div>

                            <div>
                                <x-input-label for="quantity" :value="__('Quantity to Transfer')" />
                                <x-text-input id="quantity" class="block mt-1 w-full" type="number" min="1" name="quantity" :value="old('quantity', 1)" required />
                                <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('products.index') }}" class="text-gray-600 hover:text-gray-900 mr-4 font-medium text-sm">Cancel</a>
                            <x-primary-button>
                                {{ __('Execute Transfer') }}
                            </x-primary-button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
