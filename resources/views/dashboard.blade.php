<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Inventory Dashboard') }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('reports.inventory') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                    Export Inventory
                </a>
                <a href="{{ route('reports.ledger') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Export Ledger
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Key Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Value -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 truncate uppercase tracking-wide">Total Inventory Value</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">${{ number_format($totalInventoryValue, 2) }}</div>
                    </div>
                </div>

                <!-- Low Stock Alerts -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-b-4 {{ $lowStockCount > 0 ? 'border-red-500' : 'border-green-500' }}">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 truncate uppercase tracking-wide">Low Stock Alerts</div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <div class="text-3xl font-bold {{ $lowStockCount > 0 ? 'text-red-600' : 'text-gray-900' }}">
                                {{ $lowStockCount }}
                            </div>
                            <div class="text-sm text-gray-500">products</div>
                        </div>
                    </div>
                </div>

                <!-- Pending Purchase Orders -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 truncate uppercase tracking-wide">Incoming (POs)</div>
                        <div class="mt-2 text-3xl font-bold text-indigo-600">{{ $pendingPOs }} <span class="text-sm font-normal text-gray-500">Pending</span></div>
                    </div>
                </div>

                <!-- Pending Sales Orders -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 truncate uppercase tracking-wide">Outgoing (SOs)</div>
                        <div class="mt-2 text-3xl font-bold text-blue-600">{{ $pendingSOs }} <span class="text-sm font-normal text-gray-500">To Fulfill</span></div>
                    </div>
                </div>
            </div>

            <!-- Recent Ledger Activity -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Stock Ledger Activity</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Warehouse</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Delta</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User / Reason</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($recentMovements as $movement)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $movement->created_at->format('M d, g:i A') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($movement->movement_type === 'initial')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Initial Setup</span>
                                            @elseif($movement->movement_type === 'adjustment')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Manual Adj</span>
                                            @elseif($movement->movement_type === 'po_receipt')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">PO Receipt</span>
                                            @elseif($movement->movement_type === 'so_fulfillment')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">SO Fulfillment</span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ $movement->movement_type }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $movement->product->name ?? 'Unknown' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $movement->warehouse->name ?? 'Unknown' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-right {{ $movement->quantity_delta > 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $movement->quantity_delta > 0 ? '+' : '' }}{{ $movement->quantity_delta }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <div>{{ $movement->user->name ?? 'System' }}</div>
                                            @if($movement->reason)
                                                <div class="text-xs text-gray-400 mt-1">{{ Str::limit($movement->reason, 30) }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No stock movements recorded yet. Make an adjustment or fulfill an order to see activity here!
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
