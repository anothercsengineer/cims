<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                PO: {{ $purchaseOrder->po_number }}
            </h2>
            <div class="flex space-x-4 items-center">
                @if($purchaseOrder->status === 'ordered')
                    <form method="POST" action="{{ route('purchase-orders.receive', $purchaseOrder) }}">
                        @csrf
                        <button type="submit" onclick="return confirm('Are you sure you want to mark this PO as received? This will permanently add the items to the warehouse inventory.');" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                            Mark as Received
                        </button>
                    </form>
                @else
                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
                        Status: Received
                    </span>
                @endif
                <a href="{{ route('purchase-orders.index') }}" class="text-gray-600 hover:text-gray-900 font-medium text-sm">Back to List</a>
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white">
                    
                    <!-- Top Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 border-b pb-6">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Supplier Details</h3>
                            <p class="text-lg font-bold text-gray-900">{{ $purchaseOrder->supplier->name ?? 'Deleted Supplier' }}</p>
                            @if($purchaseOrder->supplier)
                                <p class="text-gray-600">{{ $purchaseOrder->supplier->email }}</p>
                                <p class="text-gray-600">{{ $purchaseOrder->supplier->phone }}</p>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Order Details</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-gray-500 text-sm">Destination</p>
                                    <p class="font-medium">{{ $purchaseOrder->warehouse->name ?? 'Unknown' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-sm">Expected Delivery</p>
                                    <p class="font-medium">{{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('M d, Y') : 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-sm">Created On</p>
                                    <p class="font-medium">{{ $purchaseOrder->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Items</h3>
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Cost</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($purchaseOrder->items as $item)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $item->product->name ?? 'Deleted Product' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                            {{ $item->quantity }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                            ${{ number_format($item->unit_cost, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">
                                            ${{ number_format($item->total_cost, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-right text-sm font-bold text-gray-900">Total Amount:</td>
                                    <td class="px-6 py-4 text-right text-lg font-bold text-indigo-600">${{ number_format($purchaseOrder->total_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if($purchaseOrder->notes)
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Notes</h3>
                            <p class="text-gray-700 bg-gray-50 p-4 rounded-md border">{{ $purchaseOrder->notes }}</p>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
