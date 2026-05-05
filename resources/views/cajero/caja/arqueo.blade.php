<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Arqueo de caja</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8 space-y-6 text-gray-900 dark:text-gray-100">
                @if($errors->any())
                    <div class="p-4 rounded-lg bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Resumen de la caja -->
                <div class="border border-gray-300 dark:border-gray-700 rounded-lg p-8 bg-gray-50 dark:bg-gray-700/50">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Resumen de movimientos</h3>
                    <div class="grid grid-cols-2 gap-6 md:grid-cols-5">
                        <div class="space-y-2">
                            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Monto Inicial</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">${{ number_format($totales['monto_inicial'], 2) }}</p>
                        </div>
                        <div class="space-y-2">
                            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Ventas Contado</p>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">+${{ number_format($totales['ventas_contado'], 2) }}</p>
                        </div>
                        <div class="space-y-2">
                            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Pagos Crédito</p>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">+${{ number_format($totales['pagos_credito'], 2) }}</p>
                        </div>
                        <div class="space-y-2">
                            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Devoluciones</p>
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">-${{ number_format($totales['devoluciones'], 2) }}</p>
                        </div>
                        <div class="col-span-2 md:col-span-1 border-l-4 border-blue-600 dark:border-blue-400 pl-4 space-y-2 -ml-6">
                            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Total Sistema</p>
                            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">${{ number_format($totales['total_sistema'], 2) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Formulario de arqueo -->
                <form action="{{ route('cajero.caja.arqueo') }}" method="POST" class="space-y-6">
                    @csrf

                    <div class="border-t border-gray-300 dark:border-gray-700 pt-8">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3" for="monto_real">Ingrese el monto real en caja</label>
                        <input id="monto_real" name="monto_real" type="number" step="0.01" min="0" 
                               value="{{ old('monto_real') }}"
                               class="block w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('monto_real') border-red-500 @enderror" 
                               placeholder="0.00" required>
                        @error('monto_real')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="resultado" class="hidden border-t border-gray-300 dark:border-gray-700 pt-8 rounded-lg border border-gray-300 dark:border-gray-700 p-8 bg-gray-50 dark:bg-gray-700/50">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="space-y-2">
                                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Total Sistema</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">${{ number_format($totales['total_sistema'], 2) }}</p>
                            </div>
                            <div class="space-y-2">
                                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Monto Real</p>
                                <p id="monto_real_display" class="text-3xl font-bold text-gray-900 dark:text-gray-100">$0.00</p>
                            </div>
                            <div class="space-y-2">
                                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Diferencia</p>
                                <p id="diferencia_display" class="text-3xl font-bold text-gray-900 dark:text-gray-100">$0.00</p>
                                <p id="diferencia_status" class="text-sm mt-3 font-medium"></p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row items-center justify-between pt-8 border-t border-gray-300 dark:border-gray-700 gap-4">
                        <a href="{{ route('cajero.caja.index') }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 px-4 py-2">Volver</a>
                        <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center px-8 py-3 bg-blue-600 dark:bg-blue-700 hover:bg-blue-700 dark:hover:bg-blue-800 text-white rounded-lg font-semibold transition duration-200">
                            Registrar arqueo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const montoRealInput = document.getElementById('monto_real');
        const resultadoDiv = document.getElementById('resultado');
        const montoRealDisplay = document.getElementById('monto_real_display');
        const diferenciDisplay = document.getElementById('diferencia_display');
        const diferenciStatus = document.getElementById('diferencia_status');
        const totalSistema = {{ $totales['total_sistema'] }};

        montoRealInput.addEventListener('input', function() {
            const montoReal = parseFloat(this.value) || 0;
            const diferencia = montoReal - totalSistema;

            if (this.value) {
                resultadoDiv.classList.remove('hidden');
                montoRealDisplay.textContent = '$' + montoReal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                diferenciDisplay.textContent = '$' + Math.abs(diferencia).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

                if (diferencia === 0) {
                    diferenciDisplay.className = 'text-3xl font-bold text-green-600 dark:text-green-400';
                    diferenciStatus.textContent = '✓ Caja cuadrada (coincide perfectamente)';
                    diferenciStatus.className = 'text-sm mt-3 text-green-600 dark:text-green-400 font-medium';
                } else if (diferencia > 0) {
                    diferenciDisplay.className = 'text-3xl font-bold text-blue-600 dark:text-blue-400';
                    diferenciStatus.textContent = 'Sobrante en caja (hay más dinero del esperado)';
                    diferenciStatus.className = 'text-sm mt-3 text-blue-600 dark:text-blue-400 font-medium';
                } else {
                    diferenciDisplay.className = 'text-3xl font-bold text-red-600 dark:text-red-400';
                    diferenciStatus.textContent = 'Faltante en caja (hay menos dinero del esperado)';
                    diferenciStatus.className = 'text-sm mt-3 text-red-600 dark:text-red-400 font-medium';
                }
            } else {
                resultadoDiv.classList.add('hidden');
            }
        });
    </script>
</x-app-layout>
