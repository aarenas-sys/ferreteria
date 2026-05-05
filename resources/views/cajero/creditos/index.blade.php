<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Pagos de Crédito') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Créditos Pendientes de Pago</h1>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Gestiona los pagos de crédito de tus clientes</p>
                        </div>
                        <a href="{{ route('cajero') }}" class="bg-gray-600 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-800 text-white px-4 py-2 rounded-lg font-semibold">Volver al Dashboard</a>
                    </div>

                    @if(session('success'))
                        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-700 dark:text-green-300 px-6 py-4 rounded-lg mb-6 shadow-md">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->has('error'))
                        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 px-6 py-4 rounded-lg mb-6">
                            {{ $errors->first('error') }}
                        </div>
                    @endif

                    @if($creditos->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-md">
                                <thead class="bg-gradient-to-r from-yellow-500 dark:from-yellow-700 to-yellow-600 dark:to-yellow-800 text-white">
                                    <tr>
                                        <th class="px-6 py-4 text-left font-semibold">Cliente</th>
                                        <th class="px-6 py-4 text-left font-semibold">Venta ID</th>
                                        <th class="px-6 py-4 text-right font-semibold">Monto Total</th>
                                        <th class="px-6 py-4 text-right font-semibold">Saldo Pendiente</th>
                                        <th class="px-6 py-4 text-center font-semibold">Fecha Vencimiento</th>
                                        <th class="px-6 py-4 text-center font-semibold">Estado</th>
                                        <th class="px-6 py-4 text-center font-semibold">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($creditos as $credito)
                                    <tr class="hover:bg-yellow-50 dark:hover:bg-gray-700/50 transition duration-150">
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100">
                                            {{ $credito->cliente->nombre_completo }}
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-blue-600 dark:text-blue-400">
                                            <a href="{{ route('cajero.ventas.show', $credito->venta) }}" class="hover:underline">
                                                #{{ $credito->venta->id }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 text-right font-semibold text-green-600 dark:text-green-400">
                                            ${{ number_format($credito->monto_total, 2) }}
                                        </td>
                                        <td class="px-6 py-4 text-right font-bold text-red-600 dark:text-red-400">
                                            ${{ number_format($credito->saldo_pendiente, 2) }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @php
                                                $vencido = \Carbon\Carbon::parse($credito->fecha_vencimiento)->isPast();
                                                $class = $vencido ? 'text-red-600 dark:text-red-400 font-bold' : 'text-gray-700 dark:text-gray-300';
                                            @endphp
                                            <span class="{{ $class }}">
                                                {{ \Carbon\Carbon::parse($credito->fecha_vencimiento)->format('d/m/Y') }}
                                                @if($vencido)
                                                    <span class="text-xs bg-red-500 dark:bg-red-600 text-white px-2 py-1 rounded ml-2">VENCIDO</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-3 py-1 rounded-full text-sm font-bold bg-yellow-500 dark:bg-yellow-700 text-white">
                                                Pendiente
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <button type="button" class="bg-blue-600 dark:bg-blue-700 hover:bg-blue-700 dark:hover:bg-blue-800 text-white px-4 py-2 rounded-md font-semibold transition duration-200" onclick="togglePayForm({{ $credito->id }})">
                                                Pagar
                                            </button>
                                        </td>
                                    </tr>
                                    <tr id="pay-form-{{ $credito->id }}" class="hidden bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
                                        <td colspan="7" class="px-6 py-6">
                                            <form action="{{ route('cajero.ventas.pagar', $credito->venta) }}" method="POST" class="flex flex-col md:flex-row gap-4 md:items-end">
                                                @csrf
                                                @method('PATCH')
                                                <div class="flex-1 max-w-xs">
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Monto a pagar:</label>
                                                    <input type="number" name="monto" step="0.01" min="0.01" max="{{ $credito->saldo_pendiente }}" value="{{ $credito->saldo_pendiente }}" class="w-full px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none">
                                                </div>
                                                <div class="flex gap-2">
                                                    <button type="submit" class="bg-green-600 dark:bg-green-700 hover:bg-green-700 dark:hover:bg-green-800 text-white px-5 py-2 rounded-md font-semibold transition duration-200">
                                                        Confirmar Pago
                                                    </button>
                                                    <button type="button" class="bg-gray-500 dark:bg-gray-600 hover:bg-gray-600 dark:hover:bg-gray-700 text-white px-5 py-2 rounded-md font-semibold transition duration-200" onclick="togglePayForm({{ $credito->id }})">
                                                        Cancelar
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6">
                            {{ $creditos->links() }}
                        </div>
                    @else
                        <div class="text-center py-16 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">
                            <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">No hay créditos pendientes</h3>
                            <p class="text-gray-600 dark:text-gray-400">¡Todos los créditos están pagados!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePayForm(creditoId) {
            const form = document.getElementById('pay-form-' + creditoId);
            form.classList.toggle('hidden');
        }
    </script>
</x-app-layout>
