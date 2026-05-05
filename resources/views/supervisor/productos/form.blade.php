@php
    $producto = $producto ?? null;
@endphp

<div class="grid gap-6">
    <div>
        <label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
        <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $producto->nombre ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100" required>
        @error('nombre')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="codigo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Código</label>
        <input id="codigo" name="codigo" type="text" value="{{ old('codigo', $producto->codigo ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100" required>
        @error('codigo')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="categoria_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categoría</label>
        <select id="categoria_id" name="categoria_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100">
            <option value="">Sin categoría</option>
            @php
                $categoriaId = old('categoria_id', $producto?->categoria_id ?? null);
            @endphp
            @foreach($categorias ?? [] as $categoria)
                <option value="{{ $categoria->id }}" @selected((string)$categoriaId === (string)$categoria->id)>
                    {{ $categoria->nombre }}
                </option>
            @endforeach
        </select>
        @error('categoria_id')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="descripcion" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100">{{ old('descripcion', $producto->descripcion ?? '') }}</textarea>
        @error('descripcion')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="imagen" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Imagen del producto</label>
        <input id="imagen" name="imagen" type="file" accept=".jpg,.jpeg,.png,.svg" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100">
        <p class="mt-1 text-sm text-gray-500">Formatos permitidos: JPG, JPEG, PNG, SVG. Tamaño máximo: 10MB.</p>
        @error('imagen')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
        @if(isset($producto) && $producto->imagen)
            <div class="mt-2">
                <p class="text-sm text-gray-600 dark:text-gray-400">Imagen actual:</p>
                <img src="{{ asset('storage/' . $producto->imagen) }}" alt="Imagen del producto" class="mt-1 h-20 w-20 object-cover rounded">
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label for="precio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Precio</label>
            <input id="precio" name="precio" type="number" step="0.01" min="0" value="{{ old('precio', $producto->precio ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100" required>
            @error('precio')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="stock" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stock</label>
            <input id="stock" name="stock" type="number" min="0" value="{{ old('stock', $producto->stock ?? 0) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100" required>
            @error('stock')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="stock_minimo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stock mínimo</label>
            <input id="stock_minimo" name="stock_minimo" type="number" min="0" value="{{ old('stock_minimo', $producto->stock_minimo ?? 20) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100" required>
            @error('stock_minimo')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="rounded-lg bg-blue-50 border border-blue-200 p-4 text-sm text-blue-800">
        El producto se asociará automáticamente con la sucursal <strong>{{ auth()->user()->branch->name ?? 'asociada' }}</strong>.
    </div>
</div>
